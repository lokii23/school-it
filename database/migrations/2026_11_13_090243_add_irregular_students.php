<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_irregular')->default(false)->after('section_id');
        });

        // Which subject (in which section) an irregular student takes
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id']);   // each subject in one section only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_irregular');
        });
    }
};