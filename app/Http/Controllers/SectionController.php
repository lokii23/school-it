<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::withCount(['students', 'exams'])
            ->with(['students' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        $unassigned = User::where('role', 'student')
            ->whereNull('section_id')
            ->orderBy('name')
            ->get();

        return view('sections.index', compact('sections', 'unassigned'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:sections,name'],
        ]);

        Section::create(['name' => trim($request->name)]);

        return redirect()->route('sections.index')->with('success', 'Section created.');
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('sections', 'name')->ignore($section->id)],
        ]);

        $section->update(['name' => trim($request->name)]);

        return redirect()->route('sections.index')->with('success', 'Section renamed.');
    }

    public function destroy(Section $section)
    {
        if ($section->students()->exists()) {
            return back()->with('error', 'Move or unassign the students in this section before deleting it.');
        }

        if ($section->exams()->exists()) {
            return back()->with('error', 'This section has exams. Delete or reassign them first.');
        }

        $section->delete();

        return redirect()->route('sections.index')->with('success', 'Section deleted.');
    }

    // Move a student to another section (or unassign)
    public function assign(Request $request, User $user)
    {
        abort_unless($user->role === 'student', 404);

        $data = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        $user->update(['section_id' => $data['section_id'] ?: null]);

        $label = $user->section?->name ?? 'Unassigned';

        return back()->with('success', "{$user->name} moved to {$label}.");
    }
}