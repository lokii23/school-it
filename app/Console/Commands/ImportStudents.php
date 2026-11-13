<?php

namespace App\Console\Commands;

use App\Models\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportStudents extends Command
{
    protected $signature = 'students:import
        {file : Path to the CSV file}
        {--passwords=temp : temp, plain or hash}
        {--create-sections : Create sections that do not exist yet}
        {--dry-run : Preview only, nothing is saved}';

    protected $description = 'Import student accounts from a CSV exported from the old website';

    // Left = what the importer needs. Right = the column name in YOUR csv header.
    private array $columns = [
        'full_name'   => null,
        'first_name'  => 'first_name',
        'middle_name' => 'middle_name',
        'last_name'   => 'last_name',
        'email'       => 'email',
        'password'    => 'password',
        'section'     => 'section',
    ];

    private array $idx = [];

    public function handle(): int
    {
        $path = $this->argument('file');
        $mode = $this->option('passwords');
        $dry  = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if (! in_array($mode, ['temp', 'plain', 'hash'], true)) {
            $this->error('--passwords must be temp, plain or hash.');
            return self::FAILURE;
        }

        // ---------- read the header ----------
        $fh    = fopen($path, 'r');
        $line1 = (string) fgets($fh);
        rewind($fh);
        $delim = substr_count($line1, ';') > substr_count($line1, ',') ? ';' : ',';

        $header = fgetcsv($fh, 0, $delim) ?: [];
        $header = array_map(fn ($h) => Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
        $this->idx = array_flip($header);

        if (! $this->has('email') || (! $this->has('full_name') && ! ($this->has('first_name') && $this->has('last_name')))) {
            $this->error('Could not find the needed columns (email, and either full_name or first_name + last_name).');
            $this->line('Columns found in your file: ' . implode(', ', $header));
            return self::FAILURE;
        }

        // ---------- import ----------
        $existing = DB::table('users')->pluck('email')->map(fn ($e) => Str::lower($e))->flip();
        $sections = Section::all()->keyBy(fn ($s) => Str::lower($s->name));

        $imported = 0;
        $tempCount = 0;
        $rejected = $review = $temps = $unknownSections = [];
        $seen = [];
        $line = 1;
        $nextNumber   = \App\Models\User::nextStudentNumber();
        $firstNumber  = $nextNumber;
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                $line++;

                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;   // blank line
                }

                // email
                $email = Str::lower($this->get($row, 'email'));

                // Accepts school-style addresses without a dot, like student1@professians
                if (! preg_match('/^[^\s@,;<>()"]+@[^\s@,;<>()"]+$/', $email)) {
                    $rejected[] = [$line, $email, 'Missing or invalid email'];
                    continue;
                }
                if ($existing->has($email) || isset($seen[$email])) {
                    $rejected[] = [$line, $email, 'Email already exists'];
                    continue;
                }

                // name
                [$first, $middle, $last, $flag] = $this->names($row);

                if ($first === '') {
                    $rejected[] = [$line, $email, 'Missing name'];
                    continue;
                }
                if ($flag) {
                    $review[] = [$line, $email, trim("$first $middle $last"), $flag];
                }

                // section
                $sectionId = null;
                $secName   = $this->get($row, 'section');

                if ($secName !== '') {
                    $key = Str::lower($secName);
                    $sec = $sections->get($key);

                    if (! $sec && $this->option('create-sections')) {
                        $sec = Section::create(['name' => mb_substr($secName, 0, 50)]);
                        $sections->put($key, $sec);
                    }

                    if ($sec) {
                        $sectionId = $sec->id;
                    } else {
                        $unknownSections[$secName] = ($unknownSections[$secName] ?? 0) + 1;
                    }
                }

                // password
                $raw  = $this->get($row, 'password');
                $hash = null;

                if ($mode === 'hash' && preg_match('/^\$2[aby]\$\d{2}\$.{53}$/', $raw)) {
                    $hash = preg_replace('/^\$2[ab]\$/', '$2y$', $raw);
                } elseif ($mode === 'plain' && $raw !== '') {
                    $hash = Hash::make($raw);
                }

                if (! $hash) {
                    $temp = $this->tempPassword();
                    $hash = Hash::make($temp);
                    $temps[] = [$email, trim("$first $middle $last"), $temp];
                    $tempCount++;

                    if ($mode !== 'temp') {
                        $review[] = [$line, $email, trim("$first $middle $last"), 'Old password could not be reused, temporary password issued'];
                    }
                }

                DB::table('users')->insert([
                    'name'        => trim(implode(' ', array_filter([$first, $middle, $last]))),
                    'first_name'  => $first,
                    'middle_name' => $middle ?: null,
                    'last_name'   => $last ?: null,
                    'email'       => $email,
                    'student_number' => $nextNumber,
                    'password'    => $hash,
                    'role'        => 'student',
                    'section_id'  => $sectionId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $seen[$email] = true;
                $imported++;
                $nextNumber++;
            }

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed, nothing was saved: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($fh);
        }

        // ---------- report ----------
        $this->newLine();
        $this->info($dry ? 'DRY RUN, nothing was saved.' : 'Import finished.');
        $this->table(['Result', 'Count'], [
            ['Students imported' . ($dry ? ' (would be)' : ''), $imported],
            ['Student IDs assigned', $imported ? $firstNumber . ' to ' . ($nextNumber - 1) : '—'],
            ['Skipped (see reasons below)', count($rejected)],
            ['Need a quick look (name or password)', count($review)],
            ['Given a temporary password', $tempCount],
        ]);

        if ($unknownSections) {
            $this->warn('Sections not found, so those students have no section yet (use --create-sections to create them):');
            foreach ($unknownSections as $name => $n) {
                $this->line("  {$name} ({$n} students)");
            }
        }

        foreach (array_slice($rejected, 0, 15) as [$l, $e, $why]) {
            $this->line("  skipped line {$l}: {$e} - {$why}");
        }

        if (! $dry) {
            $stamp = now()->format('Ymd-His');

            if ($rejected) $this->save("imports/skipped-{$stamp}.csv", ['Line', 'Email', 'Reason'], $rejected);
            if ($review)   $this->save("imports/review-{$stamp}.csv", ['Line', 'Email', 'Name', 'Note'], $review);
            if ($temps) {
                $this->save("imports/temp-passwords-{$stamp}.csv", ['Email', 'Name', 'Temporary password'], $temps);
                $this->warn('Temporary passwords were saved. Hand them out, then DELETE that file.');
            }
        }

        return self::SUCCESS;
    }

    // ---------- helpers ----------

    private function has(string $field): bool
    {
        $col = $this->columns[$field] ?? null;

        return $col !== null && isset($this->idx[Str::lower($col)]);
    }

    private function get(array $row, string $field): string
    {
        if (! $this->has($field)) {
            return '';
        }

        return trim((string) ($row[$this->idx[Str::lower($this->columns[$field])]] ?? ''));
    }

    // Returns [first, middle, last, note-for-review]
    private function names(array $row): array
    {
        if ($this->has('first_name') && $this->has('last_name') && $this->get($row, 'first_name') !== '') {
            return [
                $this->tidy($this->get($row, 'first_name')),
                $this->tidy($this->get($row, 'middle_name')),
                $this->tidy($this->get($row, 'last_name')),
                null,
            ];
        }

        $full = preg_replace('/\s+/', ' ', $this->get($row, 'full_name'));

        if ($full === '') {
            return ['', '', '', null];
        }

        if (str_contains($full, ',')) {
            [$last, $rest] = array_map('trim', explode(',', $full, 2));
            $words = array_values(array_filter(explode(' ', $rest)));

            return [
                $this->tidy($words[0] ?? ''),
                $this->tidy(implode(' ', array_slice($words, 1))),
                $this->tidy($last),
                count($words) > 2 ? 'Check the first and middle name split' : null,
            ];
        }

        $words = explode(' ', $full);
        $n     = count($words);

        if ($n === 1) return [$this->tidy($words[0]), '', '', 'Only one name found'];
        if ($n === 2) return [$this->tidy($words[0]), '', $this->tidy($words[1]), null];

        return [
            $this->tidy($words[0]),
            $this->tidy(implode(' ', array_slice($words, 1, -1))),
            $this->tidy($words[$n - 1]),
            'Check the name split (more than 3 words)',
        ];
    }

    // JUAN or juan becomes Juan. Mixed case such as McDonald is left alone.
    private function tidy(string $s): string
    {
        $s = trim($s);

        if ($s !== '' && ($s === mb_strtoupper($s) || $s === mb_strtolower($s))) {
            return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
        }

        return $s;
    }

    private function tempPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out   = '';

        for ($i = 0; $i < 8; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $out;
    }

    private function save(string $file, array $header, array $rows): void
    {
        $f = fopen('php://temp', 'w+');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, $header);
        foreach ($rows as $r) {
            fputcsv($f, $r);
        }
        rewind($f);

        Storage::disk('local')->put($file, stream_get_contents($f));
        fclose($f);

        $this->line('Saved: ' . Storage::disk('local')->path($file));
    }
}