<?php

use App\Support\Grading;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keys = array_keys(Grading::periods());

        DB::table('grades')->orderBy('id')->each(function ($g) use ($keys) {
            $inc = json_decode($g->inc ?? 'null', true) ?: [];

            $old = $new = [];
            foreach ($keys as $k) {
                $old[$k] = $g->{$k} !== null ? (float) $g->{$k} : null;
                $new[$k] = $old[$k] !== null ? round($old[$k], 1) : null;
            }

            $incKeys   = array_values(array_intersect($keys, $inc));
            $oldRemark = Grading::remarkFor($old, $incKeys);
            $newRemark = Grading::remarkFor($new, $incKeys);

            $update = [];
            foreach (['prelim', 'midterm', 'prefinal', 'finals'] as $col) {
                if ($g->{$col} !== null) {
                    $update[$col] = round((float) $g->{$col}, 1);
                }
            }

            // An automatic remark follows the new average. A remark the teacher wrote is kept.
            $cur = trim((string) $g->remarks);
            if ($cur === '' || $cur === (string) $oldRemark) {
                $update['remarks'] = $newRemark;
            }

            if ($update) {
                DB::table('grades')->where('id', $g->id)->update($update);
            }
        });
    }

    public function down(): void
    {
        // The original two-decimal grades can't be recovered
    }
};