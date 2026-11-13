<?php

namespace App\Models;

use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    // 'locked' is deliberately not fillable: only the lock action can change it
    protected $fillable = [
        'student_id', 'teacher_id', 'subject',
        'prelim', 'midterm', 'prefinal', 'finals', 'inc', 'remarks',
    ];

    protected $casts = [
        'prelim'    => 'float',
        'midterm'   => 'float',
        'prefinal'  => 'float',
        'finals'    => 'float',
        'inc'       => 'array',
        'locked'    => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }

    // Is this period marked INC?
    public function isInc(string $period): bool
    {
        return in_array($period, $this->inc ?? [], true);
    }

    // Any of the current periods marked INC
    public function getHasIncAttribute(): bool
    {
        foreach (array_keys(Grading::periods()) as $k) {
            if ($this->isInc($k)) {
                return true;
            }
        }

        return false;
    }

    // With an INC there is no average yet
    public function getAverageAttribute(): ?float
    {
        if ($this->has_inc) {
            return null;
        }

        return Grading::average(array_map(fn ($k) => $this->{$k}, array_keys(Grading::periods())));
    }

    // Every period has a number
    public function getCompleteAttribute(): bool
    {
        foreach (array_keys(Grading::periods()) as $k) {
            if ($this->{$k} === null) {
                return false;
            }
        }

        return true;
    }

    // 'passed' | 'failed' | 'inc' | 'progress' (not all periods yet) | 'empty'
    public function getStatusAttribute(): string
    {
        if ($this->has_inc) return 'inc';
        if ($this->average === null) return 'empty';
        if (! $this->complete) return 'progress';

        return Grading::passes($this->average) ? 'passed' : 'failed';
    }

    // The remark worked out from the grades
    public function getAutoRemarkAttribute(): ?string
    {
        $keys   = array_keys(Grading::periods());
        $values = [];

        foreach ($keys as $k) {
            $values[$k] = $this->{$k};
        }

        return Grading::remarkFor($values, array_values(array_filter($keys, fn ($k) => $this->isInc($k))));
    }

    // What to show: the teacher's own text, otherwise the automatic remark
    public function getDisplayRemarksAttribute(): ?string
    {
        $r = trim((string) $this->remarks);

        return $r !== '' ? $r : $this->auto_remark;
    }

    // A student's general average across all subjects (subjects with an INC are left out)
    public static function generalAverage(int $studentId): ?float
    {
        $avgs = static::where('student_id', $studentId)->get()
            ->map(fn ($g) => $g->average)
            ->filter(fn ($a) => $a !== null);

        return $avgs->isNotEmpty() ? round($avgs->avg(), Grading::decimals()) : null;
    }
}