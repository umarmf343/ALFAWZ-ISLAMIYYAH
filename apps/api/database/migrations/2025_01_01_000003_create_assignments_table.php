<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create assignments table for tasks sent by teachers to classes or individuals.
     * Assignments can have image bases with hotspot annotations.
     */
    public function up(): void {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_s3_url')->nullable(); // flip image base
            $table->dateTime('due_at')->nullable();
            $table->enum('status', ['draft','published'])->default('draft');
            $table->json('targets')->nullable(); // array of user_ids if not class-wide
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('assignments');
    }
};