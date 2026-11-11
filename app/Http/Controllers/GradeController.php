<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use App\Support\Grading;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeController extends Controller
{
    // ===================== Teacher / Admin =====================

    public function index(Request $request)
    {
        $user     = $request->user();
        $sections = Section::orderBy('name')->get();
        $section  = $sections->firstWhere('id', (int) $request->section_id);
        $subject  = $section ? $this->canonicalSubject($this->cleanSubject($request->subject)) : null;

        $data = [
            'sections'     => $sections,
            'section'      => $section,
            'subject'      => $subject,
            'subjectHints' => $this->subjectHints($user),
            'periods'      => Grading::periods(),
            'passing'      => Grading::passing(),
            'best'         => Grading::best(),
            'worst'        => Grading::worst(),
            'decimals'     => Grading::decimals(),
            'transmute'    => config('grading.transmute'),
            'isAdmin'      => $user->role === 'admin',
        ];

        return view('grades.index', $data + ($section && $subject
            ? $this->sheetData($user, $section, $subject)
            : ['recent' => $this->recentSheets($user)]));
    }

    // Save the whole sheet at once
    public function save(Request $request)
    {
        $user = $request->user();
        $keys = array_keys(Grading::periods());

        $rules = [
            'section_id'     => ['required', 'exists:sections,id'],
            'subject'        => ['required', 'string', 'max:100'],
            'rows'           => ['sometimes', 'array'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];

        foreach ($keys as $k) {
            $rules["rows.*.{$k}"] = ['nullable', 'numeric', 'between:' . Grading::best() . ',' . Grading::worst()];
        }

        $data = $request->validate($rules, [
            'rows.*.*.numeric' => 'Grades must be numbers.',
            'rows.*.*.between' => 'Grades must be between ' . Grading::fmt(Grading::best()) . ' (highest) and ' . Grading::fmt(Grading::worst()) . ' (lowest).',
            'rows.*.*.max'     => 'Remarks can be up to 255 characters.',
        ]);

        $section = Section::findOrFail($data['section_id']);
        $subject = $this->canonicalSubject($this->cleanSubject($data['subject']));
        abort_if(! $subject, 422);

        $studentIds = User::where('role', 'student')->where('section_id', $section->id)->pluck('id')->all();
        $existing   = Grade::whereIn('student_id', $studentIds)
            ->whereRaw('lower(subject) = ?', [mb_strtolower($subject)])
            ->get()->keyBy('student_id');

        $saved = $removed = $skipped = 0;

        DB::transaction(function () use ($data, $keys, $user, $subject, $studentIds, $existing, &$saved, &$removed, &$skipped) {
            foreach (($data['rows'] ?? []) as $sid => $row) {
                $sid = (int) $sid;

                if (! in_array($sid, $studentIds, true)) {
                    continue;   // ignore anyone who isn't in this section
                }

                $g = $existing->get($sid);

                // Finalized rows, and rows owned by another teacher, can't be changed
                if ($g && ($g->locked || ($user->role !== 'admin' && $g->teacher_id !== $user->id))) {
                    $skipped++;
                    continue;
                }

                $values = [];
                foreach ($keys as $k) {
                    $v = $row[$k] ?? null;
                    $values[$k] = ($v === null || $v === '') ? null : round((float) $v, 2);
                }

                $remarks = isset($row['remarks']) ? trim($row['remarks']) : null;
                $remarks = $remarks === '' ? null : $remarks;

                $isEmpty = collect($values)->every(fn ($v) => $v === null) && $remarks === null;

                if ($isEmpty) {
                    if ($g) {
                        $g->delete();
                        $removed++;
                    }
                    continue;
                }

                if ($g) {
                    $g->fill($values + ['remarks' => $remarks]);

                    if ($g->isDirty()) {
                        $g->save();
                        $saved++;
                    }
                } else {
                    Grade::create($values + [
                        'remarks'    => $remarks,
                        'student_id' => $sid,
                        'teacher_id' => $user->id,
                        'subject'    => $subject,
                    ]);
                    $saved++;
                }
            }
        });

        $msg = $saved ? "Saved {$saved} " . Str::plural('student record', $saved) . '.' : 'No changes to save.';
        if ($removed) $msg .= " Cleared {$removed}.";
        if ($skipped) $msg .= " {$skipped} locked or not yours, skipped.";

        return redirect()
            ->route('grades.index', ['section_id' => $section->id, 'subject' => $subject])
            ->with('success', $msg);
    }

    // Admin: finalize (lock) or unlock a whole sheet
    public function lock(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'subject'    => ['required', 'string', 'max:100'],
            'action'     => ['required', 'in:lock,unlock'],
        ]);

        $section = Section::findOrFail($data['section_id']);
        $subject = $this->canonicalSubject($this->cleanSubject($data['subject']));

        $query = Grade::whereIn('student_id', User::where('role', 'student')->where('section_id', $section->id)->pluck('id'))
            ->whereRaw('lower(subject) = ?', [mb_strtolower($subject)]);

        if ($data['action'] === 'lock') {
            $n = (clone $query)->where('locked', false)->update([
                'locked' => true, 'locked_at' => now(), 'locked_by' => $request->user()->id,
            ]);
            $msg = "Finalized {$n} " . Str::plural('grade', $n) . '. Teachers can no longer edit them.';
        } else {
            $n = (clone $query)->where('locked', true)->update([
                'locked' => false, 'locked_at' => null, 'locked_by' => null,
            ]);
            $msg = "Unlocked {$n} " . Str::plural('grade', $n) . '.';
        }

        return redirect()
            ->route('grades.index', ['section_id' => $section->id, 'subject' => $subject])
            ->with('success', $msg);
    }

    public function export(Request $request): StreamedResponse
    {
        [$section, $subject, $students, $grades] = $this->loadSheet($request);
        $periods = Grading::periods();
        $name    = 'grades-' . Str::slug($section->name . '-' . $subject) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($students, $grades, $periods) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel reads names correctly

            fputcsv($out, array_merge(['Student', 'Email'], array_values($periods), ['Average', 'Status', 'Remarks']));

            foreach ($students as $s) {
                $g = $grades->get($s->id);

                fputcsv($out, array_map([$this, 'safeCell'], array_merge(
                    [$s->list_name, $s->email],
                    array_map(fn ($k) => ($g && $g->{$k} !== null) ? Grading::fmt($g->{$k}) : '', array_keys($periods)),
                    [
                        $g && $g->average !== null ? Grading::fmt($g->average) : '',
                        $this->statusLabel($g),
                        $g->remarks ?? '',
                    ]
                )));
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    // Printable class record
    public function printSheet(Request $request)
    {
        [$section, $subject, $students, $grades] = $this->loadSheet($request);

        return view('grades.print', [
            'section'  => $section,
            'subject'  => $subject,
            'students' => $students,
            'grades'   => $grades,
            'periods'  => Grading::periods(),
            'passing'  => Grading::passing(),
        ]);
    }

    // Printable report card for one student
    public function report(User $student)
    {
        abort_unless($student->role === 'student', 404);

        return $this->renderReports(collect([$student]), route('grades.index'));
    }

    // Printable report cards for a whole section (one per page)
    public function reportCards(Request $request)
    {
        $section  = Section::findOrFail((int) $request->section_id);
        $students = $this->studentsOf($section);

        abort_if($students->isEmpty(), 404);

        return $this->renderReports($students, route('grades.index', ['section_id' => $section->id]));
    }

    // ===================== Student =====================

    public function mine(Request $request)
    {
        $grades = Grade::with('teacher')
            ->where('student_id', $request->user()->id)
            ->orderBy('subject')
            ->get();

        $avgs    = $grades->map(fn ($g) => $g->average)->filter(fn ($a) => $a !== null);
        $general = $avgs->isNotEmpty() ? round($avgs->avg(), Grading::decimals()) : null;

        // Ring fill: 1.00 is a full ring, 4.00 is an empty one
        $range = max(0.01, Grading::worst() - Grading::best());
        $ring  = $general === null ? 0 : max(0, min(100, (int) round((Grading::worst() - $general) / $range * 100)));

        return view('grades.mine', [
            'grades'   => $grades,
            'periods'  => Grading::periods(),
            'passing'  => Grading::passing(),
            'general'  => $general,
            'ring'     => $ring,
            'allFinal' => $grades->isNotEmpty() && $grades->every(fn ($g) => $g->complete),
            'stats'    => [
                'count'    => $grades->count(),
                'complete' => $grades->filter(fn ($g) => $g->complete)->count(),
                'passed'   => $grades->filter(fn ($g) => $g->status === 'passed')->count(),
                'top'      => $avgs->min(),   // best = lowest number
                'low'      => $avgs->max(),   // weakest = highest number
            ],
        ]);
    }

    public function reportMine(Request $request)
    {
        return $this->renderReports(collect([$request->user()]), route('student.grades'));
    }

    // ===================== helpers =====================

    private function sheetData(User $user, Section $section, string $subject): array
    {
        $isAdmin  = $user->role === 'admin';
        $keys     = array_keys(Grading::periods());
        $students = $this->studentsOf($section);
        $grades   = $this->gradesFor($students, $subject);

        $rows = $meta = $editable = [];

        foreach ($students as $s) {
            $g   = $grades->get($s->id);
            $old = old("rows.{$s->id}");   // keeps typed values if validation failed

            $row = [];
            foreach ($keys as $k) {
                $row[$k] = $old[$k] ?? ($g?->{$k} ?? '');
            }
            $row['remarks'] = $old['remarks'] ?? ($g?->remarks ?? '');
            $rows[$s->id]   = $row;

            $locked = (bool) ($g?->locked);
            $owned  = ! $g || $isAdmin || $g->teacher_id === $user->id;

            $meta[$s->id] = [
                'editable' => $owned && ! $locked,
                'locked'   => $locked,
                'by'       => ($g && ! $owned) ? ($g->teacher->name ?? 'another teacher') : null,
            ];
            $editable[$s->id] = $meta[$s->id]['editable'];
        }

        // Exam results available to import into this sheet
        $exams = Exam::where('section_id', $section->id)
            ->when($user->role === 'teacher', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('results')
            ->latest()
            ->get(['id', 'title', 'type']);

        $results = ExamResult::whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('user_id', $students->pluck('id'))
            ->get()->groupBy('exam_id');

        // Each exam percentage is converted to a 1.00 to 4.00 grade
        $examScores = [];
        foreach ($exams as $e) {
            $examScores[$e->id] = [
                'title'  => $e->title . ' (' . ucfirst($e->type) . ')',
                'scores' => $results->get($e->id, collect())
                    ->mapWithKeys(fn ($r) => [
                        $r->user_id => $r->total ? Grading::transmute($r->score / $r->total * 100) : Grading::worst(),
                    ])
                    ->all(),
            ];
        }

        return [
            'students'    => $students,
            'rows'        => $rows,
            'meta'        => $meta,
            'editable'    => $editable,
            'examScores'  => $examScores,
            'gradedCount' => $grades->count(),
            'lockedCount' => $grades->where('locked', true)->count(),
            'anyEditable' => in_array(true, $editable, true),
        ];
    }

    private function loadSheet(Request $request): array
    {
        $section = Section::findOrFail((int) $request->section_id);
        $subject = $this->canonicalSubject($this->cleanSubject($request->subject));
        abort_if(! $subject, 404);

        $students = $this->studentsOf($section);

        return [$section, $subject, $students, $this->gradesFor($students, $subject)];
    }

    private function studentsOf(Section $section)
    {
        return User::with('section')
            ->where('role', 'student')
            ->where('section_id', $section->id)
            ->orderByRaw('COALESCE(last_name, name), name')
            ->get();
    }

    private function gradesFor($students, string $subject)
    {
        return Grade::with('teacher')
            ->whereIn('student_id', $students->pluck('id'))
            ->whereRaw('lower(subject) = ?', [mb_strtolower($subject)])
            ->get()
            ->keyBy('student_id');
    }

    private function renderReports($students, string $backUrl)
    {
        $grades = Grade::whereIn('student_id', $students->pluck('id'))
            ->orderBy('subject')
            ->get()
            ->groupBy('student_id');

        return view('grades.report', [
            'students' => $students,
            'grades'   => $grades,
            'periods'  => Grading::periods(),
            'passing'  => Grading::passing(),
            'backUrl'  => $backUrl,
        ]);
    }

    // Recently used sheets, for quick navigation
    private function recentSheets(User $user)
    {
        return Grade::query()
            ->join('users', 'users.id', '=', 'grades.student_id')
            ->when($user->role === 'teacher', fn ($q) => $q->where('grades.teacher_id', $user->id))
            ->whereNotNull('users.section_id')
            ->groupBy('users.section_id', 'grades.subject')
            ->selectRaw('users.section_id as section_id, grades.subject as subject, count(*) as students, max(grades.updated_at) as updated, sum(case when grades.locked = 1 then 1 else 0 end) as locked_count')
            ->orderByDesc('updated')
            ->limit(12)
            ->get();
    }

    private function subjectHints(User $user): Collection
    {
        $fromGrades = Grade::when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->distinct()->pluck('subject');

        $fromExams = Exam::when($user->role === 'teacher', fn ($q) => $q->where('user_id', $user->id))
            ->distinct()->pluck('subject');

        return $fromGrades->merge($fromExams)
            ->unique(fn ($s) => mb_strtolower($s))
            ->sort()
            ->values();
    }

    private function cleanSubject(?string $s): ?string
    {
        $s = trim(preg_replace('/\s+/', ' ', (string) $s));

        return $s === '' ? null : mb_substr($s, 0, 100);
    }

    // "programming 1" and "Programming 1" are the same subject: reuse the stored spelling
    private function canonicalSubject(?string $s): ?string
    {
        if (! $s) {
            return null;
        }

        return Grade::whereRaw('lower(subject) = ?', [mb_strtolower($s)])->value('subject') ?: $s;
    }

    private function statusLabel(?Grade $g): string
    {
        return match ($g?->status) {
            'passed'   => 'Passed',
            'failed'   => 'Failed',
            'progress' => 'In progress',
            default    => '',
        };
    }

    // Stops spreadsheet formula injection in exported files
    private function safeCell($value)
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}