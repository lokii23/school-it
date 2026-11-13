<?php

namespace App\Support;

use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;

class Roster
{
    // Students of one subject in one section: the section's regular students,
    // plus irregular students enrolled in exactly this subject and section.
    public static function offering(int $sectionId, ?string $subject)
    {
        $subjectId = $subject
            ? Subject::whereRaw('lower(name) = ?', [mb_strtolower(trim($subject))])->value('id')
            : null;

        return User::where('role', 'student')->where(function ($q) use ($sectionId, $subjectId) {
            $q->where(fn ($r) => $r->where('is_irregular', false)->where('section_id', $sectionId));

            if ($subjectId) {
                $q->orWhereIn('id', StudentEnrollment::where('section_id', $sectionId)
                    ->where('subject_id', $subjectId)->select('student_id'));
            }
        });
    }

    // Everyone connected to a section: regular students and irregular students enrolled in any of its subjects
    public static function inSection(int $sectionId)
    {
        return User::where('role', 'student')->where(function ($q) use ($sectionId) {
            $q->where(fn ($r) => $r->where('is_irregular', false)->where('section_id', $sectionId))
              ->orWhereIn('id', StudentEnrollment::where('section_id', $sectionId)->select('student_id'));
        });
    }

    // Every student a teacher teaches: their sections' regular students and irregular students in their subjects
    public static function forTeacher(User $teacher)
    {
        $pairs = SubjectAssignment::where('teacher_id', $teacher->id)->get(['subject_id', 'section_id']);

        if ($pairs->isEmpty()) {
            return User::whereRaw('1 = 0');
        }

        $sections = $pairs->pluck('section_id')->unique()->values();

        return User::where('role', 'student')->where(function ($q) use ($sections, $pairs) {
            $q->where(fn ($r) => $r->where('is_irregular', false)->whereIn('section_id', $sections))
              ->orWhereIn('id', StudentEnrollment::where(function ($e) use ($pairs) {
                  foreach ($pairs as $p) {
                      $e->orWhere(fn ($x) => $x->where('subject_id', $p->subject_id)->where('section_id', $p->section_id));
                  }
              })->select('student_id'));
        });
    }
}