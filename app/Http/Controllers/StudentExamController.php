<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class StudentExamController extends Controller
{
    private const PASSING = 75; // percent
    private const GRACE   = 30; // seconds of network delay allowed after time is up

    public function index(Request $request)
    {
        $user = $request->user();
        $irregular  = (bool) $user->is_irregular;
        $hasSection = $irregular ? $user->enrollments()->exists() : (bool) $user->section_id;       

        // Record a 0 for anyone who started an exam and never came back in time
        $this->expireStaleAttempts($user->id);

        $pending = collect();

        if ($hasSection) {
            $pending = Exam::with('teacher')
                ->withCount('questions')
                ->forStudent($user)
                ->has('questions')
                ->whereDoesntHave('results', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        $completed = ExamResult::with('exam')
            ->where('user_id', $user->id)
            ->whereHas('exam')
            ->latest()
            ->get();

        return view('student.exams.index', [
            'available'  => $pending->filter(fn ($e) => $e->status !== 'closed')->values(),
            'missed'     => $pending->filter(fn ($e) => $e->status === 'closed')->values(),
            'attempts'   => ExamAttempt::where('user_id', $user->id)->pluck('started_at', 'exam_id'),
            'completed'  => $completed,
            'hasSection' => $hasSection,
            'section'    => $user->section,
            'passing'    => self::PASSING,
            'irregular'  => $irregular,
        ]);
    }

    // Step 1: passkey entry page
    public function access(Request $request, Exam $exam)
    {
        $user = $request->user();
        $this->authorizeSection($user, $exam);

        if ($this->existingResult($user, $exam)) {
            return redirect()->route('student.exams.result', $exam);
        }

        if ($reason = $this->blockedReason($user, $exam)) {
            return redirect()->route('student.exams')->with('error', $reason);
        }

        if (! $exam->passkey || $this->hasAccess($exam)) {
            return redirect()->route('student.exams.show', $exam);
        }

        $exam->load('teacher')->loadCount('questions');

        return view('student.exams.passkey', compact('exam'));
    }

    // Step 2: verify the passkey
    public function verify(Request $request, Exam $exam)
    {
        $user = $request->user();
        $this->authorizeSection($user, $exam);

        if ($reason = $this->blockedReason($user, $exam)) {
            return redirect()->route('student.exams')->with('error', $reason);
        }

        $request->validate(['passkey' => ['required', 'string', 'max:50']]);

        $key = 'exam-passkey:' . $user->id . ':' . $exam->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'passkey' => "Too many wrong attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $given = strtoupper(trim($request->passkey));

        if (! $exam->passkey || ! hash_equals(strtoupper($exam->passkey), $given)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'passkey' => 'Incorrect passkey. Please check with your teacher.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->put($this->sessionKey($exam), true);

        return redirect()->route('student.exams.show', $exam);
    }

    // Step 3: the exam itself. The timer starts here.
    public function show(Request $request, Exam $exam)
    {
        $user = $request->user();
        $this->authorizeSection($user, $exam);

        if ($this->existingResult($user, $exam)) {
            return redirect()->route('student.exams.result', $exam);
        }

        if ($exam->passkey && ! $this->hasAccess($exam)) {
            return redirect()->route('student.exams.access', $exam);
        }

        if ($reason = $this->blockedReason($user, $exam)) {
            return redirect()->route('student.exams')->with('error', $reason);
        }

        // Questions WITHOUT the correct answer column
        $questions = $exam->questions()->select('id', 'exam_id', 'question', 'options')->get();

        if ($questions->isEmpty()) {
            return redirect()->route('student.exams')->with('error', 'This exam has no questions yet.');
        }

        // Starting (or resuming) records the start time on the server
        $attempt  = ExamAttempt::firstOrCreate(
            ['user_id' => $user->id, 'exam_id' => $exam->id],
            ['started_at' => now()]
        );
        $deadline = $this->deadline($exam, $attempt);

        if ($deadline && now()->gt($deadline)) {
            $this->record($user->id, $exam, 0);

            return redirect()->route('student.exams.result', $exam)
                ->with('error', 'Time ran out before your answers were submitted, so this attempt was scored 0.');
        }

        // Same shuffled order on every refresh for this attempt
        $exam->setRelation('questions', $questions->shuffle(crc32($user->id . '-' . $attempt->id)));

        $remaining = $deadline ? max(1, $deadline->timestamp - now()->timestamp) : null;

        return view('student.exams.take', [
            'exam'      => $exam,
            'remaining' => $remaining,
            'limit'     => $exam->duration_minutes ? $exam->duration_minutes * 60 : $remaining,
            'deadline'  => $deadline,
            'draftKey'  => 'exam-draft:' . $user->id . ':' . $exam->id . ':' . $attempt->started_at->timestamp,
        ]);
    }

    public function submit(Request $request, Exam $exam)
    {
        $user = $request->user();
        $this->authorizeSection($user, $exam);

        if ($this->existingResult($user, $exam)) {
            return redirect()->route('student.exams.result', $exam)
                ->with('error', 'You have already submitted this exam.');
        }

        // Block direct POSTs that skipped the passkey step
        if ($exam->passkey && ! $this->hasAccess($exam)) {
            return redirect()->route('student.exams.access', $exam)
                ->with('error', 'Enter the exam passkey first.');
        }

        if ($reason = $this->blockedReason($user, $exam)) {
            return redirect()->route('student.exams')->with('error', $reason);
        }

        $request->validate([
            'answers'   => ['nullable', 'array'],
            'answers.*' => ['integer', 'between:0,3'],
        ]);

        $questions = $exam->questions()->orderBy('id')->get();
        abort_if($questions->isEmpty(), 404);

        $attempt  = ExamAttempt::firstOrCreate(
            ['user_id' => $user->id, 'exam_id' => $exam->id],
            ['started_at' => now()]
        );
        $deadline = $this->deadline($exam, $attempt);
        $late     = $deadline && now()->gt($deadline->copy()->addSeconds(self::GRACE));

        if ($late) {
            $score    = 0;     // answers that arrive after the grace period are not accepted
            $snapshot = null;
        } else {
            $answers = $request->input('answers', []);

            // Snapshot of the exam exactly as this student saw it
            $snapshot = $questions->map(fn ($q) => [
                'question' => $q->question,
                'options'  => $q->options,
                'correct'  => (int) $q->answer,
                'chosen'   => isset($answers[$q->id]) ? (int) $answers[$q->id] : null,
            ])->values()->all();

            // Unanswered questions count as wrong
            $score = collect($snapshot)->filter(fn ($s) => $s['chosen'] === $s['correct'])->count();
        }

        $this->record($user->id, $exam, $score, $snapshot);

        $request->session()->forget($this->sessionKey($exam));

        return $late
            ? redirect()->route('student.exams.result', $exam)->with('error', 'Time was already up, so your answers were not accepted.')
            : redirect()->route('student.exams.result', $exam)->with('success', 'Your exam has been submitted.');
    }

    public function result(Request $request, Exam $exam)
    {
        $user = $request->user();

        $result = ExamResult::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        $attempt   = ExamAttempt::where('user_id', $user->id)->where('exam_id', $exam->id)->first();
        $timeTaken = $attempt ? max(0, $result->created_at->timestamp - $attempt->started_at->timestamp) : null;

        $percent = $result->total ? (int) round($result->score / $result->total * 100) : 0;

        return view('student.exams.result', [
            'exam'          => $exam,
            'result'        => $result,
            'percent'       => $percent,
            'passed'        => $percent >= self::PASSING,
            'passing'       => self::PASSING,
            'timeTaken'     => $timeTaken,
            'scoreVisible'  => $exam->scoreVisible(),
            'canReview'     => $exam->reviewOpen() && ! empty($result->answers),
            'reviewOpensAt' => (! $exam->reviewOpen() && $exam->review_mode !== 'off') ? $exam->closes_at : null,
        ]);
    }

    public function review(Request $request, Exam $exam)
    {
        $user = $request->user();

        $result = ExamResult::with('student')
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        if (! $exam->reviewOpen()) {
            $msg = $exam->review_mode === 'off'
                ? 'Answer review is turned off for this exam.'
                : 'Answer review opens after the exam closes on ' . $exam->closes_at->format('M d, Y \a\t g:i A') . '.';

            return redirect()->route('student.exams.result', $exam)->with('error', $msg);
        }

        if (empty($result->answers)) {
            return redirect()->route('student.exams.result', $exam)
                ->with('error', 'A detailed review isn’t available for this attempt.');
        }

        return view('exams.review', [
            'exam'    => $exam,
            'result'  => $result,
            'staff'   => false,
            'passing' => self::PASSING,
            'backUrl' => route('student.exams.result', $exam),
        ]);
    }

    // ---------- helpers ----------

    private function authorizeSection($user, Exam $exam): void
    {
        abort_unless($exam->isOpenToStudent($user), 403);
    }

    private function existingResult($user, Exam $exam): ?ExamResult
    {
        return ExamResult::where('user_id', $user->id)->where('exam_id', $exam->id)->first();
    }

    // Why the student can't START this exam (null = they can, or they're resuming)
    private function blockedReason($user, Exam $exam): ?string
    {
        if (ExamAttempt::where('user_id', $user->id)->where('exam_id', $exam->id)->exists()) {
            return null;
        }

        return match ($exam->status) {
            'upcoming' => 'This exam opens on ' . $exam->opens_at->format('M d, Y \a\t g:i A') . '.',
            'closed'   => 'This exam closed on ' . $exam->closes_at->format('M d, Y \a\t g:i A') . '.',
            default    => null,
        };
    }

    // The moment this attempt ends: the timer or the closing time, whichever is first
    private function deadline(Exam $exam, ExamAttempt $attempt): ?Carbon
    {
        $deadline = null;

        if ($exam->duration_minutes) {
            $deadline = $attempt->started_at->copy()->addMinutes($exam->duration_minutes);
        }

        if ($exam->closes_at && (! $deadline || $exam->closes_at->lt($deadline))) {
            $deadline = $exam->closes_at->copy();
        }

        return $deadline;
    }

    private function record(int $userId, Exam $exam, int $score, ?array $snapshot = null): ExamResult
    {
        return ExamResult::firstOrCreate(
            ['user_id' => $userId, 'exam_id' => $exam->id],
            ['score' => $score, 'total' => $exam->questions()->count(), 'answers' => $snapshot]
        );
    }

    // Started but never submitted, and time is up: record a score of 0
    private function expireStaleAttempts(int $userId): void
    {
        ExamAttempt::with('exam')
            ->where('user_id', $userId)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('exam_results')
                ->whereColumn('exam_results.exam_id', 'exam_attempts.exam_id')
                ->where('exam_results.user_id', $userId))
            ->get()
            ->each(function (ExamAttempt $a) use ($userId) {
                $deadline = $a->exam ? $this->deadline($a->exam, $a) : null;

                if ($deadline && now()->gt($deadline->copy()->addSeconds(self::GRACE))) {
                    $this->record($userId, $a->exam, 0);
                }
            });
    }

    private function sessionKey(Exam $exam): string
    {
        return 'exam_access.' . $exam->id;
    }

    private function hasAccess(Exam $exam): bool
    {
        return (bool) session($this->sessionKey($exam), false);
    }
}