<?php

namespace App\Models;

use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    // 'locked' is deliberately not fillable: only the lock action can change it
    protected $fillable = [
        'student_id', 'teacher_id', 'subject',
        'prelim', 'midterm', 'prefinal', 'finals', 'remarks',
    ];

    protected $casts = [
        'prelim'    => 'float',
        'midterm'   => 'float',
        'prefinal'  => 'float',
        'finals'    => 'float',
        'locked'    => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }

    public function getAverageAttribute(): ?float
    {
        return Grading::average(array_map(fn ($k) => $this->{$k}, array_keys(Grading::periods())));
    }

    // All four periods entered
    public function getCompleteAttribute(): bool
    {
        foreach (array_keys(Grading::periods()) as $k) {
            if ($this->{$k} === null) {
                return false;
            }
        }

        return true;
    }

    // 'passed' | 'failed' | 'progress' (not all periods yet) | 'empty'
    public function getStatusAttribute(): string
    {
        if ($this->average === null) return 'empty';
        if (! $this->complete) return 'progress';

        return Grading::passes($this->average) ? 'passed' : 'failed';
    }

    // A student's general average across all subjects
    public static function generalAverage(int $studentId): ?float
    {
        $avgs = static::where('student_id', $studentId)->get()
            ->map(fn ($g) => $g->average)
            ->filter(fn ($a) => $a !== null);

        return $avgs->isNotEmpty() ? round($avgs->avg(), Grading::decimals()) : null;
    }
}