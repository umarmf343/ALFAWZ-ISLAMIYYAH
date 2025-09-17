<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create submissions table for student responses to assignments.
     * Includes scoring, rubric data, and audio recordings.
     */
    public function up(): void {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending','graded'])->default('pending');
            $table->unsignedTinyInteger('score')->nullable(); // 0-100
            $table->json('rubric_json')->nullable(); // tajweed/fluency/memory
            $table->string('audio_s3_url')->nullable(); // student's recitation
            $table->timestamps();
            $table->unique(['assignment_id','student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('submissions');
    }
};