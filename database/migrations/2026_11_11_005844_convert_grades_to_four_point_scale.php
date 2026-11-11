<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $keys = ['prelim', 'midterm', 'prefinal', 'finals'];

        // Any value outside 1 to 4 is an old percentage, so convert it
        DB::table('grades')->orderBy('id')->each(function ($g) use ($keys) {
            $update = [];

            foreach ($keys as $k) {
                if ($g->{$k} !== null && ($g->{$k} > 4 || $g->{$k} < 1)) {
                    $update[$k] = Grading::transmute((float) $g->{$k});
                }
            }

            if ($update) {
                DB::table('grades')->where('id', $g->id)->update($update);
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('four_point_scale', function (Blueprint $table) {
            //
        });
    }
};
