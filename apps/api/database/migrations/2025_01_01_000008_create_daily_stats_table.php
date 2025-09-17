<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create daily_stats table for precomputed daily/weekly stats tracking.
     * Supports student dashboard analytics and progress monitoring.
     */
    public function up(): void {
        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('verses_read')->default(0);
            $table->unsignedBigInteger('hasanat_earned')->default(0);
            $table->unsignedInteger('time_spent')->default(0); // Seconds
            $table->unsignedInteger('streak_days')->default(0);
            $table->unsignedInteger('daily_goal')->default(10); // Verses per day
            $table->boolean('goal_achieved')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('daily_stats');
    }
};