<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create quran_progress table for per-ayah progress and hasanat tracking.
     * Tracks recitation count, memorization confidence, and reward points.
     */
    public function up(): void {
        Schema::create('quran_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_id');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedInteger('recited_count')->default(0);
            $table->float('memorized_confidence')->default(0); // 0..1
            $table->unsignedInteger('hasanat')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id','surah_id','ayah_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('quran_progress');
    }
};