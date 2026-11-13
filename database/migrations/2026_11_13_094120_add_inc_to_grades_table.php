<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The periods that are marked INC, for example ["finals"]
        Schema::table('grades', function (Blueprint $table) {
            $table->json('inc')->nullable()->after('finals');
        });
    }

    public function down(): void
    {
        Schema::table('grades', fn (Blueprint $table) => $table->dropColumn('inc'));
    }
};