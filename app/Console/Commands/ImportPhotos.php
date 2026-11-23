<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportPhotos extends Command
{
    protected $signature = 'photos:import
        {file : CSV with the columns email and profile_photo}
        {--dir=storage/app/old-photos : Folder that holds the old photo files}
        {--overwrite : Replace photos that users already have}
        {--no-resize : Copy the files as they are}
        {--dry-run : Preview only, nothing is saved}';

    protected $description = 'Import profile photos from the old website';

    // Left = what the importer needs. Right = the column name in YOUR csv header.
    private array $columns = ['email' => 'email', 'photo' => 'profile_photo'];

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $csv       = $this->argument('file');
        $dry       = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');
        $resize    = ! $this->option('no-resize');

        $dir = (string) $this->option('dir');
        $dir = rtrim(preg_match('#^([A-Za-z]:)?[\\\\/]#', $dir) ? $dir : base_path($dir), '/\\');

        if (! is_file($csv)) {
            $this->error("File not found: {$csv}");
            return self::FAILURE;
        }

        if (! is_dir($dir)) {
            $this->error("Photo folder not found: {$dir}");
            return self::FAILURE;
        }

        // ---------- read the CSV header ----------
        $fh    = fopen($csv, 'r');
        $line1 = (string) fgets($fh);
        rewind($fh);
        $delim = substr_count($line1, ';') > substr_count($line1, ',') ? ';' : ',';

        $header = fgetcsv($fh, 0, $delim) ?: [];
        $header = array_map(fn ($h) => Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
        $idx    = array_flip($header);

        $ec = Str::lower($this->columns['email']);
        $pc = Str::lower($this->columns['photo']);

        if (! isset($idx[$ec]) || ! isset($idx[$pc])) {
            $this->error("Could not find the columns '{$this->columns['email']}' and '{$this->columns['photo']}'.");
            $this->line('Columns found in your file: ' . implode(', ', $header));
            return self::FAILURE;
        }

        // ---------- index the photo files ----------
        $index = ['rel' => [], 'base' => []];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }

            $rel = mb_strtolower(str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1)));
            $index['rel'][$rel] = $f->getPathname();
            $index['base'][mb_strtolower($f->getFilename())] = $index['base'][mb_strtolower($f->getFilename())] ?? $f->getPathname();
        }

        $this->line('Found ' . count($index['rel']) . ' files in the photo folder.');

        // ---------- match and import ----------
        $users = DB::table('users')->get(['id', 'email', 'photo'])->keyBy(fn ($u) => mb_strtolower($u->email));

        $done = 0; $already = 0; $noUser = 0; $noFile = 0; $bad = 0;
        $problems = [];
        $seen = [];
        $line = 1;

        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            $line++;

            $email = Str::lower(trim((string) ($row[$idx[$ec]] ?? '')));
            $value = trim((string) ($row[$idx[$pc]] ?? ''));

            if ($email === '' || $value === '' || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $user = $users->get($email);

            if (! $user) {
                $noUser++;
                $problems[] = [$line, $email, $value, 'No user with this email'];
                continue;
            }

            if ($user->photo && ! $overwrite) {
                $already++;
                continue;
            }

            $path = $this->locate($value, $index);

            if (! $path) {
                $noFile++;
                $problems[] = [$line, $email, $value, 'Photo file not found in the folder'];
                continue;
            }

            $info = @getimagesize($path);

            if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || filesize($path) > 15 * 1024 * 1024) {
                $bad++;
                $problems[] = [$line, $email, $value, 'Not a JPG, PNG or WebP image, or larger than 15 MB'];
                continue;
            }

            if (! $dry) {
                $new = $this->store($path, $info, $resize);

                DB::table('users')->where('id', $user->id)->update(['photo' => $new, 'updated_at' => now()]);

                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
            }

            $done++;
        }

        fclose($fh);

        // ---------- report ----------
        $this->newLine();
        $this->info($dry ? 'DRY RUN, nothing was saved.' : 'Import finished.');
        $this->table(['Result', 'Count'], [
            ['Photos imported' . ($dry ? ' (would be)' : ''), $done],
            ['Skipped: the user already has a photo', $already],
            ['Email not found in the new portal', $noUser],
            ['Photo file not found', $noFile],
            ['Not a valid image', $bad],
        ]);

        foreach (array_slice($problems, 0, 15) as [$l, $e, $v, $why]) {
            $this->line("  line {$l}: {$e} ({$v}) - {$why}");
        }

        if (! $dry && $problems) {
            $f = fopen('php://temp', 'w+');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Line', 'Email', 'Photo', 'Problem']);
            foreach ($problems as $p) {
                fputcsv($f, $p);
            }
            rewind($f);

            $name = 'imports/photo-problems-' . now()->format('Ymd-His') . '.csv';
            Storage::disk('local')->put($name, stream_get_contents($f));
            fclose($f);

            $this->line('Saved: ' . Storage::disk('local')->path($name));
        }

        return self::SUCCESS;
    }

    // ---------- helpers ----------

    // Finds the file for a value like "profile_photos/a.jpg", "a.jpg" or a full URL
    private function locate(string $value, array $index): ?string
    {
        $v = $value;

        if (preg_match('#^https?://#i', $v)) {
            $v = (string) parse_url($v, PHP_URL_PATH);
        }

        $v = mb_strtolower(ltrim(str_replace('\\', '/', rawurldecode($v)), '/'));

        // Remove folders the old site added in front: storage/, public/, app/public/
        $trim = $v;
        do {
            $before = $trim;
            foreach (['app/public/', 'storage/', 'public/'] as $p) {
                if (str_starts_with($trim, $p)) {
                    $trim = substr($trim, strlen($p));
                }
            }
        } while ($trim !== $before);

        foreach (array_unique([$v, $trim]) as $c) {
            if (isset($index['rel'][$c])) {
                return $index['rel'][$c];
            }
        }

        return $index['base'][basename($trim)] ?? null;
    }

    // Saves the photo into storage/app/public/avatars. Square, max 800px, when GD is available.
    private function store(string $path, array $info, bool $resize): string
    {
        $ext   = match ($info[2]) { IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', default => 'jpg' };
        $bytes = null;

        if ($resize && function_exists('imagecreatefromstring')) {
            $bytes = $this->squareJpeg($path, $info);

            if ($bytes) {
                $ext = 'jpg';
            }
        }

        $bytes ??= file_get_contents($path);
        $rel     = 'avatars/' . Str::random(40) . '.' . $ext;

        Storage::disk('public')->put($rel, $bytes);

        return $rel;
    }

    private function squareJpeg(string $path, array $info): ?string
    {
        $src = @imagecreatefromstring((string) file_get_contents($path));

        if (! $src) {
            return null;
        }

        // Phone photos can be turned sideways: fix that
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagerotate')) {
            $exif  = @exif_read_data($path);
            $angle = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };

            if ($angle && ($rot = imagerotate($src, $angle, 0))) {
                imagedestroy($src);
                $src = $rot;
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $s = min($w, $h);
        $o = min($s, 800);

        $dst = imagecreatetruecolor($o, $o);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // white behind transparent PNGs
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $s, 2), intdiv($h - $s, 2), $o, $o, $s, $s);

        ob_start();
        imagejpeg($dst, null, 85);
        $bytes = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $bytes ?: null;
    }
}