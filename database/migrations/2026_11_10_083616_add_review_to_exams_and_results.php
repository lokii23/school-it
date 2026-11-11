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
        Schema::table('exam_results', function (Blueprint $table) {
        $table->json('answers')->nullable()->after('total');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('review_mode', 20)->default('after_close')->after('closes_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', fn (Blueprint $table) => $table->dropColumn('review_mode'));
        Schema::table('exam_results', fn (Blueprint $table) => $table->dropColumn('answers'));

    }
};
