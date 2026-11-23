<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', fn (Blueprint $t) => $t->unsignedTinyInteger('year_level')->nullable()->after('name'));
        Schema::table('subjects', fn (Blueprint $t) => $t->unsignedTinyInteger('year_level')->nullable()->after('code'));

        // Sections: use the first digit in the name ("BSIT 2A" becomes 2nd year)
        foreach (DB::table('sections')->get(['id', 'name']) as $s) {
            if (preg_match('/[1-6]/', $s->name, $m)) {
                DB::table('sections')->where('id', $s->id)->update(['year_level' => (int) $m[0]]);
            }
        }

        // Subjects: the lowest year of the sections they are assigned to
        $years = DB::table('subject_assignments')
            ->join('sections', 'sections.id', '=', 'subject_assignments.section_id')
            ->whereNotNull('sections.year_level')
            ->selectRaw('subject_assignments.subject_id as sid, min(sections.year_level) as y')
            ->groupBy('subject_assignments.subject_id')
            ->pluck('y', 'sid');

        foreach ($years as $sid => $y) {
            DB::table('subjects')->where('id', $sid)->update(['year_level' => (int) $y]);
        }
    }

    public function down(): void
    {
        Schema::table('subjects', fn (Blueprint $t) => $t->dropColumn('year_level'));
        Schema::table('sections', fn (Blueprint $t) => $t->dropColumn('year_level'));
    }
};