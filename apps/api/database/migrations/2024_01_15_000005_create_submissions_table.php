<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create submissions table.
     * Submissions track student responses and progress on assignments.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            
            // Submission content
            $table->text('text_response')->nullable();
            $table->string('audio_url')->nullable(); // Student's recitation
            $table->integer('audio_duration')->nullable(); // Duration in seconds
            $table->string('audio_format')->nullable(); // mp3, wav, etc.
            
            // Progress tracking
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'reviewed', 'completed'])
                  ->default('not_started');
            $table->integer('completion_percentage')->default(0);
            $table->json('hotspot_interactions')->nullable(); // Track which hotspots were used
            $table->integer('attempts_count')->default(0);
            
            // Scoring and feedback
            $table->integer('hasanat_earned')->default(0);
            $table->decimal('accuracy_score', 5, 2)->nullable(); // Percentage
            $table->decimal('fluency_score', 5, 2)->nullable(); // Tajweed quality
            $table->decimal('overall_score', 5, 2)->nullable();
            
            // AI Analysis (Whisper integration)
            $table->json('ai_analysis')->nullable(); // Whisper API response
            $table->text('transcription')->nullable(); // Audio to text
            $table->json('tajweed_feedback')->nullable(); // Pronunciation analysis
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->integer('time_spent_seconds')->default(0); // Total time on assignment
            
            // Teacher review
            $table->boolean('requires_review')->default(true);
            $table->text('teacher_notes')->nullable();
            $table->enum('teacher_rating', ['excellent', 'good', 'needs_improvement', 'poor'])->nullable();
            
            $table->timestamps();
            
            $table->unique(['assignment_id', 'student_id']); // One submission per student per assignment
            $table->index(['student_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};