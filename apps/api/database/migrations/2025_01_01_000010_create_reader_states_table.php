<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create reader_states table for storing user's Quran reading progress and preferences.
 * Tracks current position, display settings, and audio preferences.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reader_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('current_surah')->default(1); // Current surah (1-114)
            $table->unsignedInteger('current_ayah')->default(1); // Current ayah number
            $table->enum('font_size', ['small', 'medium', 'large'])->default('medium');
            $table->boolean('translation_enabled')->default(false);
            $table->boolean('audio_enabled')->default(false);
            $table->unsignedInteger('reciter_id')->nullable(); // Selected reciter
            $table->timestamps();
            
            // Ensure one state per user
            $table->unique('user_id');
            
            // Indexes for performance
            $table->index(['user_id', 'current_surah', 'current_ayah']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reader_states');
    }
};