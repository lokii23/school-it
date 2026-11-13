<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IrregularController extends Controller
{
    public function index(Request $request)
    {
        $students = User::with(['enrollments.subject', 'enrollments.section'])
            ->where('role', 'student')->where('is_irregular', true)
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('student_number', 'like', "%{$s}%")))
            ->orderByRaw('COALESCE(last_name, name), name')
            ->paginate(10)
            ->withQueryString();

        // Every subject that has a teacher, with the sections it is offered in
        $offerings = SubjectAssignment::with(['subject', 'section', 'teacher'])->get()
            ->filter(fn ($a) => $a->subject && $a->section)
            ->groupBy('subject_id')
            ->map(fn ($g) => [
                'id'       => $g->first()->subject_id,
                'name'     => $g->first()->subject->name,
                'sections' => $g->sortBy(fn ($a) => mb_strtolower($a->section->name))
                    ->map(fn ($a) => ['id' => $a->section_id, 'name' => $a->section->name, 'teacher' => $a->teacher->name ?? '—'])
                    ->values()->all(),
            ])
            ->sortBy(fn ($o) => mb_strtolower($o['name']))
            ->values()->all();

        // Regular students that can be marked irregular
        $candidates = User::with('section')->where('role', 'student')->where('is_irregular', false)
            ->orderByRaw('COALESCE(last_name, name), name')->get()
            ->map(fn ($u) => [
                'id'      => $u->id,
                'name'    => $u->list_name,
                'number'  => $u->student_number,
                'section' => $u->section->name ?? 'No section',
                'search'  => mb_strtolower($u->name . ' ' . $u->email . ' ' . $u->student_number),
            ])->values();

        return view('irregular.index', [
            'students'   => $students,
            'offerings'  => $offerings,
            'candidates' => $candidates,
            'sections'   => Section::orderBy('name')->get(),
            'total'      => User::where('role', 'student')->where('is_irregular', true)->count(),
        ]);
    }

    // Mark a regular student as irregular (they leave their section and get subjects instead)
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('users', 'id')->where('role', 'student')->where('is_irregular', false)],
        ], ['student_id.required' => 'Choose a student.']);

        $student = User::findOrFail($data['student_id']);
        $student->is_irregular = true;
        $student->section_id   = null;
        $student->save();

        return redirect()->route('irregular.index')
            ->with('success', "{$student->name} is now an irregular student. Choose the subjects they take.");
    }

    // Set the subjects an irregular student takes: enroll[subject_id] = section_id
    public function enroll(Request $request, User $student)
    {
        abort_unless($student->role === 'student' && $student->is_irregular, 404);

        $picked = collect($request->input('enroll', []))
            ->filter(fn ($sec) => $sec !== null && $sec !== '')
            ->map(fn ($sec) => (int) $sec);

        $rows = [];

        foreach ($picked as $subjectId => $sectionId) {
            $offered = SubjectAssignment::where('subject_id', (int) $subjectId)->where('section_id', $sectionId)->exists();

            if (! $offered) {
                throw ValidationException::withMessages([
                    'enroll' => 'One of the chosen subjects is not offered in that section.',
                ]);
            }

            $rows[] = ['student_id' => $student->id, 'subject_id' => (int) $subjectId, 'section_id' => $sectionId];
        }

        DB::transaction(function () use ($student, $rows) {
            StudentEnrollment::where('student_id', $student->id)->delete();

            foreach ($rows as $r) {
                StudentEnrollment::create($r);
            }
        });

        $n = count($rows);

        return redirect()->route('irregular.index')
            ->with('success', "{$student->name} is enrolled in {$n} " . Str::plural('subject', $n) . '.');
    }

    // Turn an irregular student back into a regular one
    public function regular(Request $request, User $student)
    {
        abort_unless($student->role === 'student' && $student->is_irregular, 404);

        $data = $request->validate(['section_id' => ['nullable', 'exists:sections,id']]);

        DB::transaction(function () use ($student, $data) {
            StudentEnrollment::where('student_id', $student->id)->delete();

            $student->is_irregular = false;
            $student->section_id   = $data['section_id'] ?: null;
            $student->save();
        });

        return redirect()->route('irregular.index')
            ->with('success', "{$student->name} is a regular student again.");
    }
}