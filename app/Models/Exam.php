<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    protected $fillable = [
        'user_id', 'section_id', 'title', 'subject', 'type',
        'passkey', 'duration_minutes', 'opens_at', 'closes_at',
        'review_mode', 'score_mode',
    ];

    protected $casts = [
        'passkey'   => 'encrypted',
        'opens_at'  => 'datetime',
        'closes_at' => 'datetime',
    ];

    public function teacher()   { return $this->belongsTo(User::class, 'user_id'); }   // who created it
    public function section()   { return $this->belongsTo(Section::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function results()   { return $this->hasMany(ExamResult::class); }

    // The exams a user manages. Admins manage all of them. A teacher manages the exams whose
    // section AND subject are assigned to them, so exams follow the assignment, not the creator.
    public function scopeManagedBy($query, User $user)
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->whereExists(function ($q) use ($user) {
            $q->select(DB::raw(1))
                ->from('subject_assignments')
                ->join('subjects', 'subjects.id', '=', 'subject_assignments.subject_id')
                ->whereColumn('subject_assignments.section_id', 'exams.section_id')
                ->whereRaw('lower(subjects.name) = lower(exams.subject)')
                ->where('subject_assignments.teacher_id', $user->id);
        });
    }

    public function isManagedBy(User $user): bool
    {
        return $user->role === 'admin' || static::whereKey($this->getKey())->managedBy($user)->exists();
    }

    // Exams students can start right now
    public function scopeOpenNow($query)
    {
        return $query
            ->where(fn ($w) => $w->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('closes_at')->orWhere('closes_at', '>=', now()));
    }

    // 'upcoming' | 'open' | 'closed'
    public function getStatusAttribute(): string
    {
        if ($this->opens_at && now()->lt($this->opens_at)) return 'upcoming';
        if ($this->closes_at && now()->gt($this->closes_at)) return 'closed';

        return 'open';
    }

    // May students see their score yet?
    public function scoreVisible(): bool
    {
        if ($this->score_mode !== 'after_close') {
            return true;
        }

        return $this->closes_at ? now()->gt($this->closes_at) : true;
    }

    // May students see the correct answers yet?
    public function reviewOpen(): bool
    {
        if ($this->review_mode === 'off') {
            return false;
        }

        // Answers are never revealed before the score is
        if (! $this->scoreVisible()) {
            return false;
        }

        return match ($this->review_mode) {
            'after_submit' => true,
            default        => $this->closes_at ? now()->gt($this->closes_at) : true,
        };
    }

    // The exams a student may see. Regular students: their section. Irregular: their enrolled subjects only.
    public function scopeForStudent($query, User $student)
    {
        if (! $student->is_irregular) {
            return $student->section_id ? $query->where('section_id', $student->section_id) : $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($q) use ($student) {
            $q->select(DB::raw(1))
                ->from('student_enrollments')
                ->join('subjects', 'subjects.id', '=', 'student_enrollments.subject_id')
                ->whereColumn('student_enrollments.section_id', 'exams.section_id')
                ->whereRaw('lower(subjects.name) = lower(exams.subject)')
                ->where('student_enrollments.student_id', $student->id);
        });
    }

    public function isOpenToStudent(User $student): bool
    {
        return static::whereKey($this->getKey())->forStudent($student)->exists();
    }
}