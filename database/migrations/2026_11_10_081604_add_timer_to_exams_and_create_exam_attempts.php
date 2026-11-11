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
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('passkey');
            $table->dateTime('opens_at')->nullable()->after('duration_minutes');
            $table->dateTime('closes_at')->nullable()->after('opens_at');
        });

        // Records when each student started, so the timer is enforced by the server
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamps();
            $table->unique(['user_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'opens_at', 'closes_at']);
        });
    }
};
