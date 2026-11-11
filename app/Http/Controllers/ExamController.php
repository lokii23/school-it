<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $exams = Exam::with(['section', 'teacher'])
            ->withCount(['questions', 'results'])
            ->when($user->role === 'teacher', fn ($q) => $q->where('user_id', $user->id))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->section_id, fn ($q, $s) => $q->where('section_id', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $sections = Section::orderBy('name')->get();

        return view('exams.index', compact('exams', 'sections'));
    }

    public function create()
    {
        return view('exams.form', [
            'exam'     => new Exam(['type' => 'exam']),
            'sections' => Section::orderBy('name')->get(),
            'passkey' => ['required', 'string', 'min:4', 'max:20'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $this->checkWindow($data);

        DB::transaction(function () use ($data, $request) {
            $exam = Exam::create([
                'user_id'    => $request->user()->id,
                'section_id' => $data['section_id'],
                'title'      => trim($data['title']),
                'subject'    => trim($data['subject']),
                'type'       => $data['type'],
                'passkey' => strtoupper(trim($data['passkey'])),
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'opens_at'         => $data['opens_at'] ?? null,
                'closes_at'        => $data['closes_at'] ?? null,
                'review_mode' => $data['review_mode'],
                'score_mode' => $data['score_mode'],
            ]);

            $exam->questions()->createMany($this->questionRows($data['questions']));
        });

        return redirect()->route('exams.index')->with('success', 'Exam published.');
    }

    public function edit(Request $request, Exam $exam)
    {
        $this->authorizeExam($request, $exam);

        $exam->load('questions')->loadCount('results');

        return view('exams.form', [
            'exam'     => $exam,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorizeExam($request, $exam);

        $data = $request->validate($this->rules(), $this->messages());
        $this->checkWindow($data);

        DB::transaction(function () use ($exam, $data) {
            $exam->update([
                'section_id' => $data['section_id'],
                'title'      => trim($data['title']),
                'subject'    => trim($data['subject']),
                'type'       => $data['type'],
                'passkey.required' => 'Set a passkey that students must enter before starting.',
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'opens_at'         => $data['opens_at'] ?? null,
                'closes_at'        => $data['closes_at'] ?? null,
                'review_mode' => $data['review_mode'],
                'score_mode' => $data['score_mode'],
            ]);

            // Replace the question set with the submitted one
            $exam->questions()->delete();
            $exam->questions()->createMany($this->questionRows($data['questions']));
        });

        return redirect()->route('exams.index')->with('success', 'Exam updated.');
    }

    public function destroy(Request $request, Exam $exam)
    {
        $this->authorizeExam($request, $exam);

        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Exam deleted.');
    }

    // ---------- helpers ----------

    private function authorizeExam(Request $request, Exam $exam): void
    {
        $user = $request->user();

        abort_unless($user->role === 'admin' || $exam->user_id === $user->id, 403);
    }

    private function rules(): array
    {
        return [
            'title'                    => ['required', 'string', 'max:150'],
            'subject'                  => ['required', 'string', 'max:100'],
            'type'                     => ['required', Rule::in(['exam', 'quiz'])],
            'section_id'               => ['required', 'exists:sections,id'],
            'passkey'                  => ['required', 'string', 'min:4', 'max:20'],
            'questions'                => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.question'     => ['required', 'string', 'max:1000'],
            'questions.*.options'      => ['required', 'array', 'size:4'],
            'questions.*.options.*'    => ['required', 'string', 'max:300'],
            'questions.*.answer'       => ['required', 'integer', 'between:0,3'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,600'],
            'opens_at'         => ['nullable', 'date'],
            'closes_at'        => ['nullable', 'date'],
            'review_mode' => ['required', Rule::in(['off', 'after_submit', 'after_close'])],
            'score_mode' => ['required', Rule::in(['immediate', 'after_close'])],
        ];
    }

    private function messages(): array
    {
        return [
            'questions.required'                => 'Add at least one question.',
            'questions.*.question.required'     => 'Every question needs text.',
            'questions.*.options.*.required'    => 'Every question needs all four choices filled in.',
            'section_id.required'               => 'Please choose a section.',
        ];
    }

    private function questionRows(array $questions): array
    {
        return collect($questions)->map(fn ($q) => [
            'question' => trim($q['question']),
            'options'  => array_values(array_map('trim', $q['options'])),
            'answer'   => (int) $q['answer'],
        ])->values()->all();
    }

    private function checkWindow(array $data): void
    {
        if (! empty($data['opens_at']) && ! empty($data['closes_at'])
            && Carbon::parse($data['closes_at'])->lte(Carbon::parse($data['opens_at']))) {
            throw ValidationException::withMessages([
                'closes_at' => 'The closing time must be after the opening time.',
            ]);
        }

        if (($data['score_mode'] ?? 'immediate') === 'after_close' && empty($data['closes_at'])) {
            throw ValidationException::withMessages([
                'closes_at' => 'Set a closing time. Scores are hidden until the exam closes.',
            ]);
        }
    }
}