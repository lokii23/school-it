<?php

namespace App\Support;

class Grading
{
    public static function periods(): array { return config('grading.periods'); }
    public static function best(): float     { return (float) config('grading.best'); }
    public static function worst(): float    { return (float) config('grading.worst'); }
    public static function passing(): float  { return (float) config('grading.passing'); }
    public static function decimals(): int   { return (int) config('grading.decimals', 2); }

    // Mean of the periods that have a grade (null if none)
    public static function average(array $values): ?float
    {
        $nums = [];

        foreach ($values as $v) {
            if ($v !== null && $v !== '') {
                $nums[] = (float) $v;
            }
        }

        return $nums ? round(array_sum($nums) / count($nums), self::decimals()) : null;
    }

    // Lower is better: 3.00 or lower passes
    public static function passes(?float $average): bool
    {
        return $average !== null && $average <= self::passing() + 0.000001;
    }

    // Exam percentage to grade (used only when importing exam scores)
    public static function transmute(float $percent): float
    {
        foreach (config('grading.transmute') as [$min, $grade]) {
            if ($percent >= $min) {
                return (float) $grade;
            }
        }

        return self::worst();
    }

    // Always two decimals: 1.00, 2.25, 3.00
    public static function fmt($n): string
    {
        if ($n === null || $n === '') {
            return '—';
        }

        return number_format((float) $n, 2, '.', '');
    }
}