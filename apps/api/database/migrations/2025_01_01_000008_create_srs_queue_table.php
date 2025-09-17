<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create srs_queue table for spaced repetition system scheduling.
     * Manages when verses should be reviewed based on memorization algorithm.
     */
    public function up(): void {
        Schema::create('srs_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_id');
            $table->unsignedSmallInteger('ayah_id');
            $table->unsignedTinyInteger('interval_days')->default(1);
            $table->float('ease_factor')->default(2.5);
            $table->unsignedTinyInteger('repetitions')->default(0);
            $table->dateTime('next_review_at');
            $table->timestamps();
            $table->unique(['user_id','surah_id','ayah_id']);
            $table->index(['user_id','next_review_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('srs_queue');
    }
};