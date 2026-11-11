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
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('prelim', 5, 2)->nullable()->after('subject');
            $table->decimal('midterm', 5, 2)->nullable()->after('prelim');
            $table->decimal('prefinal', 5, 2)->nullable()->after('midterm');
            $table->decimal('finals', 5, 2)->nullable()->after('prefinal');
            $table->string('remarks', 255)->nullable()->after('finals');
            $table->boolean('locked')->default(false)->after('remarks');
            $table->timestamp('locked_at')->nullable()->after('locked');
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
        });

        // Keep every existing grade: the old single score becomes the Prelim grade
        DB::table('grades')->update(['prelim' => DB::raw('score')]);

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('score');
            $table->unique(['student_id', 'subject']);   // one row per student per subject
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject']);
            $table->unsignedTinyInteger('score')->default(0);
        });

        DB::table('grades')->update(['score' => DB::raw('COALESCE(prelim, 0)')]);

        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['prelim', 'midterm', 'prefinal', 'finals', 'remarks', 'locked', 'locked_at', 'locked_by']);
        });
    }

};
