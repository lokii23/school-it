<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Support\Grading;
use App\Support\Roster;
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
        $user    = $request->user();
        $isAdmin = $user->role === 'admin';
        $years   = Grading::yearLevels();

        $visible = $this->visibleSections($user);
        $section = $visible->firstWhere('id', (int) $request->section_id);

        // A teacher who opens a section they don't teach is turned away
        abort_if($request->filled('section_id') && ! $section, 403);

        // Year level: the one picked on the page, otherwise the year of the open section
        $year = (int) $request->query('year');
        $year = isset($years[$year])
            ? $year
            : (($section && isset($years[(int) $section->year_level])) ? (int) $section->year_level : null);

        $sections = $year ? $visible->where('year_level', $year)->values() : $visible;

        if ($section && ! $sections->contains('id', $section->id)) {
            $sections = $sections->push($section)->sortBy('name')->values();
        }

        $subject = null;
        if ($section && $request->filled('subject')) {
            $subject = $this->subjectName($request->subject);
            abort_if(! $subject, 404);
            abort_unless($this->canTeach($user, $section, $subject), 403);
        }

        // Students per section: regular students plus irregular students enrolled in it
        $regular   = User::where('role', 'student')->where('is_irregular', false)->whereNotNull('section_id')
            ->selectRaw('section_id, count(*) as c')->groupBy('section_id')->pluck('c', 'section_id');
        $irregular = StudentEnrollment::selectRaw('section_id, count(distinct student_id) as c')
            ->groupBy('section_id')->pluck('c', 'section_id');

        $counts = $sections->mapWithKeys(fn ($s) => [
            $s->id => (int) ($regular[$s->id] ?? 0) + (int) ($irregular[$s->id] ?? 0),
        ]);

        $data = [
            'sections'     => $sections,
            'section'      => $section,
            'subject'      => $subject,
            'counts'       => $counts,
            'years'        => $years,
            'year'         => $year,
            'yearCounts'   => $visible->groupBy(fn ($s) => (int) $s->year_level)->map->count(),
            'allSubjects'  => $isAdmin ? Subject::orderBy('name')->pluck('name') : collect(),
            'periods'      => Grading::periods(),
            'passing'      => Grading::passing(),
            'best'         => Grading::best(),
            'worst'        => Grading::worst(),
            'decimals'     => Grading::decimals(),
            'gradeDecimals' => Grading::gradeDecimals(),
            'transmute'    => config('grading.transmute'),
            'remarkScale'  => config('grading.remarks'),
            'failedRemark' => config('grading.failed_remark', 'Failed'),
            'incRemark'    => config('grading.inc_remark', 'Incomplete'),
            'isAdmin'      => $isAdmin,
        ];

        // Step 1: no section chosen yet
        if (! $section) {
            return view('grades.index', $data + [
                'cards'      => $this->sectionCards($user, $sections, $counts),
                'unassigned' => $isAdmin
                    ? User::where('role', 'student')->where('is_irregular', false)->whereNull('section_id')->count()
                    : 0,
            ]);
        }

        // Step 2 and 3: a section, with its subjects and (if picked) the grade table
        $data['subjects'] = $this->sectionSubjects($user, $section);

        return view('grades.index', $data + ($subject ? $this->sheetData($user, $section, $subject) : []));
    }

    // Save the whole sheet at once
    public function save(Request $request)
    {
        $user  = $request->user();
        $keys  = array_keys(Grading::periods());
        $best  = Grading::best();
        $worst = Grading::worst();

        $rules = [
            'section_id'     => ['required', 'exists:sections,id'],
            'subject'        => ['required', 'string', 'max:100'],
            'rows'           => ['sometimes', 'array'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];

        $message = 'Grades must be ' . Grading::fmt($best) . ' (highest) to ' . Grading::fmt($worst) . ' (lowest), or INC, with one decimal at most.';

        // A grade is a number from best to worst, or INC
        foreach ($keys as $k) {
            $rules["rows.*.{$k}"] = ['nullable', function ($attribute, $value, $fail) use ($best, $worst, $message) {
                if (! is_scalar($value)) {
                    $fail($message);
                    return;
                }

                $v = trim((string) $value);

                if (Grading::isInc($v)) {
                    return;
                }

                if (! preg_match('/^\d+(\.\d{1,' . Grading::gradeDecimals() . '})?$/', $v) || (float) $v < $best || (float) $v > $worst) {
                    $fail($message);
                }
            }];
        }

        $data = $request->validate($rules, [
            'rows.*.remarks.max' => 'Remarks can be up to 255 characters.',
        ]);

        $section = Section::findOrFail($data['section_id']);
        $subject = $this->subjectName($data['subject']);

        abort_if(! $subject, 422, 'That subject does not exist.');
        abort_unless($this->canTeach($user, $section, $subject), 403);

        // The grades belong to whoever is assigned to this subject and section
        $teacherId = $this->assignmentFor($section, $subject)?->teacher_id ?? $user->id;

        // Only students who really take this subject here can be graded
        $studentIds = Roster::offering($section->id, $subject)->pluck('id')->all();
        $existing   = Grade::whereIn('student_id', $studentIds)
            ->whereRaw('lower(subject) = ?', [mb_strtolower($subject)])
            ->get()->keyBy('student_id');

        $saved = $removed = $skipped = 0;

        DB::transaction(function () use ($data, $keys, $teacherId, $subject, $studentIds, $existing, &$saved, &$removed, &$skipped) {
            foreach (($data['rows'] ?? []) as $sid => $row) {
                $sid = (int) $sid;

                if (! in_array($sid, $studentIds, true)) {
                    continue;
                }

                $g = $existing->get($sid);

                // Finalized rows can't be changed
                if ($g && $g->locked) {
                    $skipped++;
                    continue;
                }

                // Numbers go in the column. INC is remembered separately.
                $values  = [];
                $incKeys = [];

                foreach ($keys as $k) {
                    $v = isset($row[$k]) ? trim((string) $row[$k]) : '';

                    if ($v === '') {
                        $values[$k] = null;
                    } elseif (Grading::isInc($v)) {
                        $values[$k] = null;
                        $incKeys[]  = $k;
                    } else {
                        $values[$k] = round((float) $v, Grading::gradeDecimals());
                    }
                }

                // The teacher's own remark, otherwise the automatic one
                $remarks = isset($row['remarks']) ? trim($row['remarks']) : '';
                if ($remarks === '') {
                    $remarks = Grading::remarkFor($values, $incKeys);
                }

                $isEmpty = collect($values)->every(fn ($v) => $v === null) && ! $incKeys && $remarks === null;

                if ($isEmpty) {
                    if ($g) {
                        $g->delete();
                        $removed++;
                    }
                    continue;
                }

                $payload = $values + [
                    'remarks' => $remarks,
                    'inc'     => $incKeys ?: null,
                ];

                if ($g) {
                    $g->fill($payload + ['teacher_id' => $teacherId]);

                    if ($g->isDirty()) {
                        $g->save();
                        $saved++;
                    }
                } else {
                    Grade::create($payload + [
                        'student_id' => $sid,
                        'teacher_id' => $teacherId,
                        'subject'    => $subject,
                    ]);
                    $saved++;
                }
            }
        });

        $msg = $saved ? "Saved {$saved} " . Str::plural('student record', $saved) . '.' : 'No changes to save.';
        if ($removed) $msg .= " Cleared {$removed}.";
        if ($skipped) $msg .= " {$skipped} finalized, skipped.";

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
        $subject = $this->subjectName($data['subject']);
        abort_if(! $subject, 404);

        $query = Grade::whereIn('student_id', Roster::offering($section->id, $subject)->pluck('id'))
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

            fputcsv($out, array_merge(['Student', 'Email', 'Type'], array_values($periods), ['Average', 'Status', 'Remarks']));

            foreach ($students as $s) {
                $g = $grades->get($s->id);

                $cells = [];
                foreach (array_keys($periods) as $k) {
                    $cells[] = ! $g ? '' : ($g->isInc($k) ? 'INC' : ($g->{$k} !== null ? Grading::fmt($g->{$k}) : ''));
                }

                fputcsv($out, array_map([$this, 'safeCell'], array_merge(
                    [$s->list_name, $s->email, $s->is_irregular ? 'Irregular' : 'Regular'],
                    $cells,
                    [
                        $g ? ($g->has_inc ? 'INC' : ($g->average !== null ? Grading::fmtAvg($g->average) : '')) : '',
                        $this->statusLabel($g),
                        $g->display_remarks ?? '',
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
            'teacher'  => $this->assignmentFor($section, $subject)?->teacher?->name,
        ]);
    }

    // Printable report card for one student
    public function report(Request $request, User $student)
    {
        abort_unless($student->role === 'student', 404);

        $user = $request->user();

        abort_unless(
            $user->role === 'admin' || Roster::forTeacher($user)->whereKey($student->id)->exists(),
            403
        );

        return $this->renderReports(collect([$student]), route('grades.index'), $this->yearParam($request));
    }

    // Printable report cards for a whole section (one per page)
    public function reportCards(Request $request)
    {
        $section = Section::findOrFail((int) $request->section_id);
        $this->authorizeSection($request->user(), $section->id);

        $students = $this->sectionRoster($section);
        abort_if($students->isEmpty(), 404);

        return $this->renderReports($students, route('grades.index', ['section_id' => $section->id]), $this->yearParam($request));
    }

    // ===================== Student =====================

    public function mine(Request $request)
    {
        $me     = $request->user();
        $years  = Grading::yearLevels();
        $grades = Grade::with('teacher')->where('student_id', $me->id)->orderBy('subject')->get();
        $yearOf = $this->subjectYears();

        $byYear = $grades->groupBy(fn ($g) => (int) ($yearOf[mb_strtolower($g->subject)] ?? 0));
        $range  = max(0.01, Grading::worst() - Grading::best());

        // The numbers for one tab
        $build = function (string $label, Collection $list) use ($range) {
            $avgs    = $list->map(fn ($g) => $g->average)->filter(fn ($a) => $a !== null);
            $general = $avgs->isNotEmpty() ? round($avgs->avg(), Grading::decimals()) : null;
            $fill    = $general === null ? 0 : max(0, min(1, (Grading::worst() - $general) / $range));   // 1.00 = full ring

            return [
                'label'    => $label,
                'grades'   => $list->values(),
                'count'    => $list->count(),
                'general'  => $general,
                'offset'   => round(339.3 * (1 - $fill), 1),
                'allFinal' => $list->isNotEmpty() && $list->every(fn ($g) => $g->complete),
                'complete' => $list->filter(fn ($g) => $g->complete)->count(),
                'passed'   => $list->filter(fn ($g) => $g->status === 'passed')->count(),
                'inc'      => $list->filter(fn ($g) => $g->status === 'inc')->count(),
                'top'      => $avgs->min(),   // best = lowest number
                'low'      => $avgs->max(),   // weakest = highest number
            ];
        };

        $tabs = ['all' => $build('All years', $grades)];

        $keys = collect(array_keys($years))
            ->merge($byYear->keys()->filter(fn ($k) => $k > 0))
            ->unique()->sort()->values();

        foreach ($keys as $n) {
            $tabs[(string) $n] = $build(Grading::yearLabel((int) $n), $byYear->get($n, collect()));
        }

        if ($byYear->has(0)) {
            $tabs['0'] = $build('Other subjects', $byYear->get(0));
        }

        // Open on the year asked for, otherwise the student's current year, otherwise all
        $current = (! $me->is_irregular && $me->section) ? (int) $me->section->year_level : null;
        $asked   = $request->query('year');
        $active  = ($asked !== null && isset($tabs[(string) $asked])) ? (string) $asked : null;

        if ($active === null) {
            $active = ($current && isset($tabs[(string) $current]) && $tabs[(string) $current]['count'] > 0)
                ? (string) $current
                : 'all';
        }

        return view('grades.mine', [
            'tabs'        => $tabs,
            'active'      => $active,
            'current'     => $current,
            'yearOf'      => $yearOf,
            'periods'     => Grading::periods(),
            'passing'     => Grading::passing(),
            'instructors' => $this->instructorsFor(collect([$me]), $grades->groupBy('student_id')),
        ]);
    }


    public function reportMine(Request $request)
    {
        return $this->renderReports(collect([$request->user()]), route('student.grades'), $this->yearParam($request));
    }

    // ===================== access rules =====================

    // Admin sees every section. A teacher sees only the sections they have a subject in.
    private function visibleSections(User $user): Collection
    {
        return Section::orderBy('name')
            ->when($user->role !== 'admin', fn ($q) => $q->whereIn(
                'id',
                SubjectAssignment::where('teacher_id', $user->id)->pluck('section_id')
            ))
            ->get();
    }

    private function assignmentFor(Section $section, string $subject): ?SubjectAssignment
    {
        return SubjectAssignment::with('teacher')
            ->where('section_id', $section->id)
            ->whereHas('subject', fn ($q) => $q->whereRaw('lower(name) = ?', [mb_strtolower($subject)]))
            ->first();
    }

    private function canTeach(User $user, Section $section, string $subject): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $a = $this->assignmentFor($section, $subject);

        return $a && (int) $a->teacher_id === (int) $user->id;
    }

    private function authorizeSection(User $user, ?int $sectionId): void
    {
        if ($user->role === 'admin') {
            return;
        }

        abort_unless(
            $sectionId && SubjectAssignment::where('teacher_id', $user->id)->where('section_id', $sectionId)->exists(),
            403
        );
    }

    // The subject's official spelling from the Subjects list, or null if it doesn't exist
    private function subjectName(?string $s): ?string
    {
        $s = trim(preg_replace('/\s+/', ' ', (string) $s));

        return $s === '' ? null : Subject::whereRaw('lower(name) = ?', [mb_strtolower($s)])->value('name');
    }

    // ===================== helpers =====================

    // One card per section, for the first page
    private function sectionCards(User $user, Collection $sections, Collection $counts)
    {
        $assigned = SubjectAssignment::when($user->role !== 'admin', fn ($q) => $q->where('teacher_id', $user->id))
            ->selectRaw('section_id, count(*) as c')
            ->groupBy('section_id')
            ->pluck('c', 'section_id');

        $updated = Grade::join('users', 'users.id', '=', 'grades.student_id')
            ->whereNotNull('users.section_id')
            ->selectRaw('users.section_id as section_id, max(grades.updated_at) as updated')
            ->groupBy('users.section_id')
            ->pluck('updated', 'section_id');

        return $sections->map(fn ($s) => [
            'section'  => $s,
            'students' => (int) ($counts[$s->id] ?? 0),
            'subjects' => (int) ($assigned[$s->id] ?? 0),
            'updated'  => $updated[$s->id] ?? null,
        ]);
    }

    // The subjects of one section with how complete each one is
    private function sectionSubjects(User $user, Section $section): Collection
    {
        $assignments = SubjectAssignment::with(['subject', 'teacher'])
            ->where('section_id', $section->id)
            ->when($user->role !== 'admin', fn ($q) => $q->where('teacher_id', $user->id))
            ->get()
            ->filter(fn ($a) => $a->subject)
            ->sortBy(fn ($a) => mb_strtolower($a->subject->name))
            ->values();

        return $assignments->map(function ($a) {
            $name = $a->subject->name;
            $ids  = Roster::offering($a->section_id, $name)->pluck('id');
            $rows = Grade::whereIn('student_id', $ids)
                ->whereRaw('lower(subject) = ?', [mb_strtolower($name)])
                ->get();

            return [
                'name'     => $name,
                'code'     => $a->subject->code,
                'teacher'  => $a->teacher->name ?? null,
                'students' => $ids->count(),
                'graded'   => $rows->count(),
                'complete' => $rows->filter(fn ($g) => $g->complete)->count(),
                'locked'   => $rows->where('locked', true)->count(),
            ];
        });
    }

    private function sheetData(User $user, Section $section, string $subject): array
    {
        $keys     = array_keys(Grading::periods());
        $students = $this->rosterOf($section, $subject);
        $grades   = $this->gradesFor($students, $subject);

        $rows = $meta = $editable = [];

        foreach ($students as $s) {
            $g   = $grades->get($s->id);
            $old = old("rows.{$s->id}");   // keeps typed values if validation failed

            $row = [];
            foreach ($keys as $k) {
                $saved   = $g ? ($g->isInc($k) ? 'INC' : ($g->{$k} !== null ? Grading::fmt($g->{$k}) : '')) : '';
                $row[$k] = $old[$k] ?? $saved;
            }
            $row['remarks'] = $old['remarks'] ?? ($g?->remarks ?? '');
            $rows[$s->id]   = $row;

            $locked = (bool) ($g?->locked);

            $meta[$s->id]     = ['editable' => ! $locked, 'locked' => $locked, 'by' => null];
            $editable[$s->id] = ! $locked;
        }

        // Exam results for THIS subject in THIS section, available to import
        $exams = Exam::where('section_id', $section->id)
            ->whereRaw('lower(subject) = ?', [mb_strtolower($subject)])
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
            'teacherName' => $this->assignmentFor($section, $subject)?->teacher?->name,
        ];
    }

    private function loadSheet(Request $request): array
    {
        $section = Section::findOrFail((int) $request->section_id);
        $subject = $this->subjectName($request->subject);
        abort_if(! $subject, 404);
        abort_unless($this->canTeach($request->user(), $section, $subject), 403);

        $students = $this->rosterOf($section, $subject);

        return [$section, $subject, $students, $this->gradesFor($students, $subject)];
    }

    // The class list of one subject in one section (regular students plus enrolled irregular students)
    private function rosterOf(Section $section, string $subject)
    {
        return Roster::offering($section->id, $subject)
            ->with('section')
            ->orderByRaw('COALESCE(last_name, name), name')
            ->get();
    }

    // Everyone connected to a section, for report cards
    private function sectionRoster(Section $section)
    {
        return Roster::inSection($section->id)
            ->with('section')
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

    private function renderReports($students, string $backUrl, ?int $year = null)
    {
        $grades = Grade::with('teacher')
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('subject')
            ->get()
            ->groupBy('student_id');

        // Only the subjects of the chosen year level
        if ($year !== null) {
            $yearOf = $this->subjectYears();

            $grades = $grades->map(fn ($list) => $list
                ->filter(fn ($g) => (int) ($yearOf[mb_strtolower($g->subject)] ?? 0) === $year)
                ->values());
        }

        return view('grades.report', [
            'students'    => $students,
            'grades'      => $grades,
            'instructors' => $this->instructorsFor($students, $grades),
            'periods'     => Grading::periods(),
            'passing'     => Grading::passing(),
            'backUrl'     => $backUrl,
            'yearLabel'   => $year === null ? null : Grading::yearLabel($year),
        ]);
    }


    // The teacher currently assigned to each of a student's subjects: [student_id][grade_id] => name
    private function instructorsFor($students, $grades): array
    {
        $subjectIds = Subject::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);

        $assigned = SubjectAssignment::with('teacher')->get()
            ->mapWithKeys(fn ($a) => [$a->subject_id . '|' . $a->section_id => $a->teacher->name ?? null]);

        // For irregular students: which section they take each subject in
        $enrolled = StudentEnrollment::whereIn('student_id', $students->pluck('id'))->get()
            ->mapWithKeys(fn ($e) => [$e->student_id . '|' . $e->subject_id => $e->section_id]);

        $out = [];

        foreach ($students as $s) {
            foreach ($grades->get($s->id, collect()) as $g) {
                $subId = $subjectIds[mb_strtolower($g->subject)] ?? null;
                $secId = $s->is_irregular ? ($enrolled[$s->id . '|' . $subId] ?? null) : $s->section_id;
                $name  = ($subId && $secId) ? ($assigned[$subId . '|' . $secId] ?? null) : null;

                // No current assignment? Fall back to the teacher who last graded it
                $out[$s->id][$g->id] = $name ?: ($g->teacher->name ?? null);
            }
        }

        return $out;
    }

    private function statusLabel(?Grade $g): string
    {
        return match ($g?->status) {
            'passed'   => 'Passed',
            'failed'   => 'Failed',
            'inc'      => 'INC',
            'progress' => 'In progress',
            default    => '',
        };
    }

    // Stops spreadsheet formula injection in exported files
    private function safeCell($value)
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    // subject name (lowercase) => its year level (0 = none)
    private function subjectYears(): Collection
    {
        return Subject::pluck('year_level', 'name')->mapWithKeys(fn ($y, $n) => [mb_strtolower($n) => (int) $y]);
    }

    private function yearParam(Request $request): ?int
    {
        return $request->has('year') ? (int) $request->query('year') : null;
    }
}