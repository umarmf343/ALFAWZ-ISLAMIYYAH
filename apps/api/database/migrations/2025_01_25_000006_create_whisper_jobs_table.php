<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create whisper_jobs table for admin monitoring.
 * Tracks Whisper job processing with comprehensive metadata.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whisper_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recitation_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['queued', 'processing', 'done', 'failed'])->default('queued');
            $table->json('result_json')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whisper_jobs');
    }
};