<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create feedback table.
     * Feedback stores detailed comments and suggestions from teachers and AI analysis.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Feedback content
            $table->enum('type', ['teacher', 'ai', 'peer', 'self'])->default('teacher');
            $table->text('content');
            $table->json('detailed_analysis')->nullable(); // Structured feedback data
            
            // Specific feedback areas
            $table->text('pronunciation_feedback')->nullable();
            $table->text('tajweed_feedback')->nullable();
            $table->text('fluency_feedback')->nullable();
            $table->text('memorization_feedback')->nullable();
            
            // Scoring breakdown
            $table->decimal('pronunciation_score', 5, 2)->nullable();
            $table->decimal('tajweed_score', 5, 2)->nullable();
            $table->decimal('fluency_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            
            // Recommendations
            $table->json('improvement_suggestions')->nullable();
            $table->json('strengths')->nullable();
            $table->text('next_steps')->nullable();
            
            // Audio feedback
            $table->string('audio_feedback_url')->nullable(); // Teacher's voice feedback
            $table->integer('audio_duration')->nullable();
            
            // Visibility and status
            $table->boolean('is_visible_to_student')->default(true);
            $table->boolean('is_final')->default(false); // Can be edited if false
            $table->enum('sentiment', ['positive', 'constructive', 'encouraging', 'corrective'])
                  ->default('constructive');
            
            // AI confidence (for AI-generated feedback)
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('ai_metadata')->nullable(); // Model version, processing time, etc.
            
            $table->timestamps();
            
            $table->index(['submission_id', 'type']);
            $table->index(['teacher_id', 'created_at']);
            $table->index('is_visible_to_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};