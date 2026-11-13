<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\Roster;

class ResultController extends Controller
{
    private const PASSING = 75; // percent, same as StudentExamController

    // ===================== Teacher / Admin =====================

    // Overview: one card per exam
    public function index(Request $request)
    {
        $user = $request->user();

        $exams = Exam::with('section')
            ->withCount('questions')
            ->managedBy($user)
            ->when($request->section_id, fn ($q, $s) => $q->where('section_id', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")))
            ->when($request->state, function ($q, $state) {
                if ($state === 'open')     $q->openNow();
                if ($state === 'upcoming') $q->where('opens_at', '>', now());
                if ($state === 'closed')   $q->where('closes_at', '<', now());
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        // Per-exam numbers for the cards on this page
        $stats = ExamResult::whereIn('exam_id', $exams->pluck('id'))
            ->selectRaw(
                'exam_id, count(*) as taken,
                 avg(case when total > 0 then score * 100.0 / total end) as avg_pct,
                 sum(case when total > 0 and score * 100 >= total * ? then 1 else 0 end) as passed',
                [self::PASSING]
            )
            ->groupBy('exam_id')->get()->keyBy('exam_id');

        $enrolled = $exams->getCollection()->mapWithKeys(fn ($e) => [
            $e->id => Roster::offering($e->section_id, $e->subject)->count(),
        ]);

        // Overall numbers for the summary cards
        $overall = ExamResult::whereHas('exam', fn ($q) => $q->managedBy($user))
            ->selectRaw(
                'count(*) as taken,
                 avg(case when total > 0 then score * 100.0 / total end) as avg_pct,
                 sum(case when total > 0 and score * 100 >= total * ? then 1 else 0 end) as passed',
                [self::PASSING]
            )->first();

        return view('results.index', [
            'exams'     => $exams,
            'stats'     => $stats,
            'enrolled'  => $enrolled,
            'overall'   => $overall,
            'examCount' => Exam::managedBy($user)->count(),
            'sections'  => $this->sectionsFor($user),
            'passing'   => self::PASSING,
        ]);
    }

    // One exam: the whole section, who took it, and how they did
    public function exam(Request $request, Exam $exam)
    {
        $this->authorizeExam($request->user(), $exam);
        $exam->load(['section', 'teacher'])->loadCount('questions');

        [$rows, $results] = $this->buildRows($exam);

        $taken    = $rows->whereNotNull('pct');
        $percents = $taken->pluck('pct');

        $summary = [
            'enrolled' => $rows->count(),
            'taken'    => $taken->count(),
            'pending'  => $rows->where('state', 'pending')->count(),
            'passed'   => $rows->where('state', 'passed')->count(),
            'failed'   => $rows->where('state', 'failed')->count(),
            'avg'      => $percents->isNotEmpty() ? (int) round($percents->avg()) : null,
            'high'     => $percents->max(),
            'low'      => $percents->min(),
        ];
        $summary['rate'] = $summary['taken'] ? (int) round($summary['passed'] / $summary['taken'] * 100) : null;

        // Score distribution
        $dist = ['Below 60%' => 0, '60–74%' => 0, '75–84%' => 0, '85–94%' => 0, '95–100%' => 0];
        foreach ($percents as $p) {
            $key = $p < 60 ? 'Below 60%' : ($p < 75 ? '60–74%' : ($p < 85 ? '75–84%' : ($p < 95 ? '85–94%' : '95–100%')));
            $dist[$key]++;
        }

        return view('results.exam', [
            'exam'     => $exam,
            'rows'     => $rows,
            'summary'  => $summary,
            'dist'     => $dist,
            'analysis' => $this->analyse($results),
            'passing'  => self::PASSING,
        ]);
    }

    public function examExport(Request $request, Exam $exam): StreamedResponse
    {
        $this->authorizeExam($request->user(), $exam);
        $exam->load('section');

        [$rows] = $this->buildRows($exam);
        $name   = 'results-' . \Illuminate\Support\Str::slug($exam->title) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $exam) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads names correctly

            fputcsv($out, ['Student', 'Email', 'Section', 'Status', 'Score', 'Total', 'Percent', 'Result', 'Date taken', 'Time taken (min)']);

            foreach ($rows as $row) {
                $r = $row['result'];

                $status = match (true) {
                    (bool) $r                  => 'Submitted',
                    $row['in_progress']        => 'In progress',
                    $exam->status === 'closed' => 'Missed',
                    default                    => 'Not taken yet',
                };

                fputcsv($out, array_map([$this, 'safeCell'], [
                    $row['student']->list_name,
                    $row['student']->email,
                    $exam->section->name ?? '',
                    $status,
                    $r->score ?? '',
                    $r->total ?? '',
                    $row['pct'] !== null ? $row['pct'] . '%' : '',
                    $row['state'] === 'passed' ? 'Passed' : ($row['state'] === 'failed' ? 'Failed' : ''),
                    $r ? $r->created_at->format('Y-m-d H:i') : '',
                    $row['time'] !== null ? round($row['time'] / 60, 1) : '',
                ]));
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    // Flat list of every submission
    public function all(Request $request)
    {
        $user  = $request->user();
        $query = $this->filtered($request);

        $stats = (clone $query)->selectRaw(
            'count(*) as taken,
             sum(case when total > 0 and score * 100 >= total * ? then 1 else 0 end) as passed,
             avg(case when total > 0 then score * 100.0 / total end) as avg_pct,
             max(case when total > 0 then score * 100.0 / total end) as best_pct',
            [self::PASSING]
        )->first();

        $results = $query
            ->with(['student.section', 'exam.section'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('results.all', [
            'results'  => $results,
            'stats'    => $stats,
            'exams'    => Exam::managedBy($user)->orderBy('title')->get(['id', 'title']),
            'sections' => $this->sectionsFor($user),
            'passing'  => self::PASSING,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($request)
            ->with(['student.section', 'exam'])
            ->latest()
            ->get();

        $passing = self::PASSING;

        return response()->streamDownload(function () use ($rows, $passing) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Student', 'Email', 'Section', 'Exam', 'Subject', 'Score', 'Total', 'Percent', 'Status', 'Date taken']);

            foreach ($rows as $r) {
                $pct = $r->total ? round($r->score / $r->total * 100, 1) : 0;

                fputcsv($out, array_map([$this, 'safeCell'], [
                    $r->student->name ?? '—',
                    $r->student->email ?? '—',
                    $r->student->section->name ?? '—',
                    $r->exam->title ?? '—',
                    $r->exam->subject ?? '—',
                    $r->score,
                    $r->total,
                    $pct . '%',
                    $pct >= $passing ? 'Passed' : 'Failed',
                    $r->created_at->format('Y-m-d H:i'),
                ]));
            }

            fclose($out);
        }, 'results-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // A student's submission, answer by answer
    public function show(Request $request, ExamResult $result)
    {
        $result->load(['student.section', 'exam']);

        abort_unless($result->exam && $result->exam->isManagedBy($request->user()), 403);

        return view('exams.review', [
            'exam'    => $result->exam,
            'result'  => $result,
            'staff'   => true,
            'passing' => self::PASSING,
            'backUrl' => route('results.exam', $result->exam),
        ]);
    }

    // Reset an attempt so the student can retake the exam
    public function destroy(Request $request, ExamResult $result)
    {
        $result->load('exam');

        abort_unless($result->exam && $result->exam->isManagedBy($request->user()), 403);

        $name = $result->student->name ?? 'The student';

        ExamAttempt::where('user_id', $result->user_id)->where('exam_id', $result->exam_id)->delete();
        $result->delete();

        return back()->with('success', "{$name}'s attempt was reset. They can retake the exam.");
    }

    // ===================== Student =====================

    public function mine(Request $request)
    {
        $results = ExamResult::with('exam')
            ->where('user_id', $request->user()->id)
            ->whereHas('exam')
            ->latest()
            ->get();

        $released = $results->filter(fn ($r) => $r->exam->scoreVisible());
        $percents = $released->map(fn ($r) => $r->total ? $r->score / $r->total * 100 : 0);

        return view('results.mine', [
            'results' => $results,
            'passing' => self::PASSING,
            'stats'   => [
                'taken'    => $results->count(),
                'pending'  => $results->count() - $released->count(),
                'released' => $released->count(),
                'passed'   => $percents->filter(fn ($p) => $p >= self::PASSING)->count(),
                'avg'      => $percents->isNotEmpty() ? round($percents->avg()) : null,
                'best'     => $percents->isNotEmpty() ? round($percents->max()) : null,
            ],
        ]);
    }

    // ===================== helpers =====================

    private function authorizeExam(User $user, Exam $exam): void
    {
        abort_unless($exam->isManagedBy($user), 403);
    }

    // Admin: every section. Teacher: only the sections where they teach a subject.
    private function sectionsFor(User $user)
    {
        return Section::orderBy('name')
            ->when($user->role !== 'admin', fn ($q) => $q->whereIn(
                'id',
                SubjectAssignment::where('teacher_id', $user->id)->pluck('section_id')
            ))
            ->get();
    }

    // Every student of the section, with or without a result
    private function buildRows(Exam $exam): array
    {
        $results  = ExamResult::where('exam_id', $exam->id)->get();
        $byUser   = $results->keyBy('user_id');
        $attempts = ExamAttempt::where('exam_id', $exam->id)->get()->keyBy('user_id');

        $students = Roster::offering($exam->section_id, $exam->subject)
            ->orderByRaw('COALESCE(last_name, name), name')
            ->get();

        // Students who took it but have since moved to another section
        $extra = User::whereIn('id', $results->pluck('user_id'))
            ->whereNotIn('id', $students->pluck('id'))
            ->get();

        $rows = $students->concat($extra)->map(function ($s) use ($byUser, $attempts) {
            $r = $byUser->get($s->id);
            $a = $attempts->get($s->id);

            $pct = $r && $r->total ? (int) round($r->score / $r->total * 100) : ($r ? 0 : null);

            return [
                'student'     => $s,
                'result'      => $r,
                'pct'         => $pct,
                'state'       => $r ? ($pct >= self::PASSING ? 'passed' : 'failed') : 'pending',
                'in_progress' => $a && ! $r,
                'time'        => ($r && $a) ? max(0, $r->created_at->timestamp - $a->started_at->timestamp) : null,
            ];
        })->values();

        return [$rows, $results];
    }

    // Per-question analysis from the answers saved at submit time
    private function analyse($results): array
    {
        $qa = [];

        foreach ($results as $r) {
            foreach (($r->answers ?? []) as $a) {
                $key = md5($a['question'] ?? '');

                $qa[$key] ??= [
                    'question' => $a['question'] ?? '',
                    'options'  => $a['options'] ?? [],
                    'correct'  => $a['correct'] ?? null,
                    'right'    => 0, 'wrong' => 0, 'skipped' => 0,
                    'picks'    => [0, 0, 0, 0],
                ];

                $chosen = $a['chosen'] ?? null;

                if ($chosen === null)                        $qa[$key]['skipped']++;
                elseif ($chosen === ($a['correct'] ?? null)) $qa[$key]['right']++;
                else { $qa[$key]['wrong']++; $qa[$key]['picks'][$chosen] = ($qa[$key]['picks'][$chosen] ?? 0) + 1; }
            }
        }

        return collect($qa)->map(function ($q) {
            $total = $q['right'] + $q['wrong'] + $q['skipped'];
            $q['total']       = $total;
            $q['pct_correct'] = $total ? (int) round($q['right'] / $total * 100) : 0;
            $q['top_wrong']   = $q['wrong'] > 0 ? array_search(max($q['picks']), $q['picks']) : null;

            return $q;
        })->sortBy('pct_correct')->values()->all();
    }

    private function filtered(Request $request)
    {
        $user = $request->user();

        return ExamResult::query()
            ->whereHas('exam', fn ($q) => $q
                ->managedBy($user)
                ->when($request->section_id, fn ($w, $s) => $w->where('section_id', $s)))
            ->when($request->exam_id, fn ($q, $e) => $q->where('exam_id', $e))
            ->when($request->q, fn ($q, $s) => $q->whereHas('student', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($request->status === 'passed', fn ($q) => $q->whereRaw('total > 0 and score * 100 >= total * ?', [self::PASSING]))
            ->when($request->status === 'failed', fn ($q) => $q->whereRaw('(total = 0 or score * 100 < total * ?)', [self::PASSING]));
    }

    // Stops spreadsheet formula injection in exported files
    private function safeCell($value)
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}