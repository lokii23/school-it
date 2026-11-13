<?php

namespace App\Support;

class Grading
{
    public static function periods(): array { return config('grading.periods'); }
    public static function best(): float     { return (float) config('grading.best'); }
    public static function worst(): float    { return (float) config('grading.worst'); }
    public static function passing(): float  { return (float) config('grading.passing'); }
    public static function decimals(): int   { return (int) config('grading.decimals', 1); }

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

    // Is this typed value "INC"?
    public static function isInc($value): bool
    {
        return is_string($value) && strcasecmp(trim($value), (string) config('grading.inc', 'INC')) === 0;
    }

    // The description for an average (null if there is no average)
    public static function remark(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        foreach (config('grading.remarks') as [$max, $label]) {
            if ($average <= $max + 0.000001) {
                return $label;
            }
        }

        return config('grading.failed_remark', 'Failed');
    }

    // The automatic remark for a set of period grades. Needs every period, or an INC.
    public static function remarkFor(array $values, array $incKeys = []): ?string
    {
        if ($incKeys) {
            return config('grading.inc_remark', 'Incomplete');
        }

        foreach (array_keys(self::periods()) as $k) {
            if (($values[$k] ?? null) === null) {
                return null;
            }
        }

        return self::remark(self::average($values));
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

    // A single grade: always two decimals (1.25, 2.00, 3.00)
    public static function fmt($n): string
    {
        if ($n === null || $n === '') {
            return '—';
        }

        return number_format((float) $n, 2, '.', '');
    }

    // An average: one decimal (1.9, 2.5, 3.0)
    public static function fmtAvg($n): string
    {
        if ($n === null || $n === '') {
            return '—';
        }

        return number_format((float) $n, self::decimals(), '.', '');
    }
}