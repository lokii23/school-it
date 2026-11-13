<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::with(['assignments.section', 'assignments.teacher'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->teacher_id, fn ($q, $t) => $q->whereHas('assignments', fn ($a) => $a->where('teacher_id', $t)))
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        return view('subjects.index', [
            'subjects'   => $subjects,
            'teachers'   => User::where('role', 'teacher')->orderByRaw('COALESCE(last_name, name), name')->get(),
            'sections'   => Section::orderBy('name')->get(),
            'unassigned' => Subject::doesntHave('assignments')->count(),
            'total'      => Subject::count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
        ]);

        $name = $this->clean($data['name']);
        $this->ensureUnique($name);

        Subject::create(['name' => $name, 'code' => $this->clean($data['code'] ?? '') ?: null]);

        return redirect()->route('subjects.index')->with('success', 'Subject created. Now assign a teacher to it.');
    }

    // Renaming also updates the subject on existing grades and exams
    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
        ]);

        $name = $this->clean($data['name']);
        $this->ensureUnique($name, $subject);

        DB::transaction(function () use ($subject, $name, $data) {
            $old = $subject->name;

            $subject->update(['name' => $name, 'code' => $this->clean($data['code'] ?? '') ?: null]);

            if ($old !== $name) {
                foreach ([Grade::class, Exam::class] as $model) {
                    $model::whereRaw('lower(subject) = ?', [mb_strtolower($old)])->update(['subject' => $name]);
                }
            }
        });

        return redirect()->route('subjects.index')->with('success', 'Subject updated.');
    }

    public function destroy(Subject $subject)
    {
        $low  = mb_strtolower($subject->name);
        $used = Grade::whereRaw('lower(subject) = ?', [$low])->exists()
             || Exam::whereRaw('lower(subject) = ?', [$low])->exists();

        if ($used) {
            return back()->with('error', 'This subject already has grades or exams, so it cannot be deleted. Remove its teacher assignments instead.');
        }

        $subject->delete();   // its assignments are deleted with it

        return redirect()->route('subjects.index')->with('success', 'Subject deleted.');
    }

    // Assign one teacher to a subject for one or more sections
    public function assign(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'teacher_id'    => ['required', Rule::exists('users', 'id')->where('role', 'teacher')],
            'section_ids'   => ['required', 'array', 'min:1'],
            'section_ids.*' => ['exists:sections,id'],
        ], [
            'teacher_id.required'  => 'Choose a teacher.',
            'section_ids.required' => 'Tick at least one section.',
        ]);

        $teacherId = (int) $data['teacher_id'];
        $changed = $replaced = 0;

        DB::transaction(function () use ($subject, $data, $teacherId, &$changed, &$replaced) {
            foreach (array_unique($data['section_ids']) as $sectionId) {
                $a = SubjectAssignment::where('subject_id', $subject->id)->where('section_id', $sectionId)->first();

                if ($a && (int) $a->teacher_id === $teacherId) {
                    continue;
                }

                if ($a) {
                    $a->update(['teacher_id' => $teacherId]);
                    $replaced++;
                } else {
                    SubjectAssignment::create([
                        'subject_id' => $subject->id, 'section_id' => $sectionId, 'teacher_id' => $teacherId,
                    ]);
                }

                // Grades already entered now belong to the new teacher
                Grade::whereRaw('lower(subject) = ?', [mb_strtolower($subject->name)])
                    ->whereIn('student_id', User::where('role', 'student')->where('section_id', $sectionId)->pluck('id'))
                    ->update(['teacher_id' => $teacherId]);

                $changed++;
            }
        });

        $teacher = User::find($teacherId)?->name ?? 'The teacher';

        $msg = $changed
            ? "{$teacher} is now assigned to {$subject->name} in {$changed} " . \Illuminate\Support\Str::plural('section', $changed) . '.'
            : "Nothing changed: {$teacher} was already assigned to those sections.";

        if ($replaced) {
            $msg .= " {$replaced} previous " . \Illuminate\Support\Str::plural('assignment was', $replaced) . ' replaced.';
        }

        return redirect()->route('subjects.index')->with('success', $msg);
    }

    public function unassign(SubjectAssignment $assignment)
    {
        $assignment->delete();

        return back()->with('success', 'Assignment removed. Existing grades are kept.');
    }

    // ---------- helpers ----------

    private function clean(?string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $s));
    }

    private function ensureUnique(string $name, ?Subject $ignore = null): void
    {
        $q = Subject::whereRaw('lower(name) = ?', [mb_strtolower($name)]);

        if ($ignore) {
            $q->where('id', '!=', $ignore->id);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages(['name' => 'That subject already exists.']);
        }
    }
}