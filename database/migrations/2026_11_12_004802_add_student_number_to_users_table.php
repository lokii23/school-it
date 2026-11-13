<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('student_number')->nullable()->unique()->after('email');
        });

        // Give the students who already exist a number, oldest first
        $n = 2026001;

        foreach (DB::table('users')->where('role', 'student')->orderBy('id')->get(['id']) as $u) {
            DB::table('users')->where('id', $u->id)->update(['student_number' => $n++]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['student_number']);
            $table->dropColumn('student_number');
        });
    }
};