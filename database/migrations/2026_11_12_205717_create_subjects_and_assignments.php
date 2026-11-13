<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['subject_id', 'section_id']);   // one teacher per subject per section
        });

        // ---------- keep everything that already exists ----------
        $now = now();

        $names = DB::table('grades')->pluck('subject')
            ->merge(DB::table('exams')->pluck('subject'))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique(fn ($s) => mb_strtolower($s))
            ->values();

        foreach ($names as $name) {
            DB::table('subjects')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);

            // Make the spelling identical everywhere
            DB::table('grades')->whereRaw('lower(subject) = ?', [mb_strtolower($name)])->update(['subject' => $name]);
            DB::table('exams')->whereRaw('lower(subject) = ?', [mb_strtolower($name)])->update(['subject' => $name]);
        }

        $ids = DB::table('subjects')->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id]);

        $teachers = DB::table('users')->where('role', 'teacher')->pluck('id')->flip();

        $pairs = collect();

        foreach (DB::table('exams')->get(['subject', 'section_id', 'user_id']) as $e) {
            $pairs->push([$ids[mb_strtolower(trim($e->subject))] ?? null, $e->section_id, $e->user_id]);
        }

        foreach (DB::table('grades')
            ->join('users', 'users.id', '=', 'grades.student_id')
            ->whereNotNull('users.section_id')
            ->get(['grades.subject', 'users.section_id', 'grades.teacher_id']) as $g) {
            $pairs->push([$ids[mb_strtolower(trim($g->subject))] ?? null, $g->section_id, $g->teacher_id]);
        }

        foreach ($pairs as [$subjectId, $sectionId, $teacherId]) {
            if (! $subjectId || ! $sectionId || ! $teachers->has($teacherId)) {
                continue;
            }

            DB::table('subject_assignments')->insertOrIgnore([
                'subject_id' => $subjectId, 'section_id' => $sectionId, 'teacher_id' => $teacherId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
        Schema::dropIfExists('subjects');
    }
};