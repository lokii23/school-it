<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\StudentEnrollment;
use App\Support\Grading;

class SectionController extends Controller
{
    // Admin: every section. Teacher: only the sections where they teach a subject (view only).
    public function index(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->role === 'admin';

        $sections = Section::withCount('students')
            ->with(['students' => fn ($q) => $q->orderByRaw('COALESCE(last_name, name), name')])
            ->when(! $isAdmin, fn ($q) => $q->whereIn(
                'id',
                SubjectAssignment::where('teacher_id', $user->id)->pluck('section_id')
            ))
            ->orderBy('name')
            ->get();

        // Students with no section are an admin matter
        $unassigned = $isAdmin
            ? User::where('role', 'student')->where('is_irregular', false)->whereNull('section_id')->orderByRaw('COALESCE(last_name, name), name')->get()
            : collect();

        // The subjects taught in each section (a teacher sees only their own)
        $subjectsBySection = SubjectAssignment::with(['subject', 'teacher'])
            ->whereIn('section_id', $sections->pluck('id'))
            ->when(! $isAdmin, fn ($q) => $q->where('teacher_id', $user->id))
            ->get()
            ->filter(fn ($a) => $a->subject)
            ->sortBy(fn ($a) => mb_strtolower($a->subject->name))
            ->groupBy('section_id');

        $examCounts = Exam::managedBy($user)
            ->selectRaw('section_id, count(*) as c')
            ->groupBy('section_id')
            ->pluck('c', 'section_id');
        
        // Irregular students enrolled in these sections' subjects (a teacher sees only their own subjects)
        $irregularBySection = StudentEnrollment::with(['student', 'subject'])
            ->whereIn('section_id', $sections->pluck('id'))
            ->when(! $isAdmin, function ($q) use ($user) {
                $mine = SubjectAssignment::where('teacher_id', $user->id)->get(['subject_id', 'section_id']);

                $q->where(function ($w) use ($mine) {
                    if ($mine->isEmpty()) {
                        $w->whereRaw('1 = 0');
                    }

                    foreach ($mine as $p) {
                        $w->orWhere(fn ($x) => $x->where('subject_id', $p->subject_id)->where('section_id', $p->section_id));
                    }
                });
            })
            ->get()
            ->groupBy('section_id');

        return view('sections.index', compact('sections', 'unassigned', 'subjectsBySection', 'examCounts', 'isAdmin', 'irregularBySection'));
    }

    public function store(Request $request)
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50', 'unique:sections,name'],
            'year_level' => ['nullable', Rule::in(array_keys(Grading::yearLevels()))],
        ]);

        Section::create(['name' => trim($data['name']), 'year_level' => $data['year_level'] ?? null]);

        return redirect()->route('sections.index')->with('success', 'Section created.');
    }

    public function update(Request $request, Section $section)
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50', Rule::unique('sections', 'name')->ignore($section->id)],
            'year_level' => ['nullable', Rule::in(array_keys(Grading::yearLevels()))],
        ]);

        $section->update(['name' => trim($data['name']), 'year_level' => $data['year_level'] ?? null]);

        return redirect()->route('sections.index')->with('success', 'Section updated.');
    }

    public function destroy(Request $request, Section $section)
    {
        $this->adminOnly($request);

        if ($section->students()->exists()) {
            return back()->with('error', 'Move or unassign the students in this section before deleting it.');
        }

        if ($section->exams()->exists()) {
            return back()->with('error', 'This section has exams. Delete or reassign them first.');
        }

        $section->delete();   // its subject assignments are removed with it

        return redirect()->route('sections.index')->with('success', 'Section deleted.');
    }

    // Move a student to another section (or unassign)
    public function assign(Request $request, User $user)
    {
        $this->adminOnly($request);

        abort_unless($user->role === 'student', 404);
        if ($user->is_irregular) {
            return back()->with('error', 'This is an irregular student. Manage their subjects in Irregular Students.');
        }
        $data = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        $user->update(['section_id' => $data['section_id'] ?: null]);

        $label = $user->fresh('section')->section?->name ?? 'Unassigned';

        return back()->with('success', "{$user->name} moved to {$label}.");
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }
}