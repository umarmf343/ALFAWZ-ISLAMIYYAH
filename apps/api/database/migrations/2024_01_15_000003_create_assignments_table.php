<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create assignments table.
     * Assignments are tasks created by teachers for students with Quran content and hotspots.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            
            // Quran content
            $table->integer('surah_id')->nullable();
            $table->integer('ayah_start')->nullable();
            $table->integer('ayah_end')->nullable();
            $table->text('arabic_text')->nullable();
            $table->text('translation')->nullable();
            
            // Assignment settings
            $table->enum('type', ['recitation', 'memorization', 'reading', 'hotspot_interaction']);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('expected_hasanat')->default(0);
            $table->boolean('requires_audio')->default(false);
            $table->boolean('has_hotspots')->default(false);
            
            // Timing
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->enum('status', ['draft', 'published', 'completed', 'archived'])->default('draft');
            
            // Media
            $table->string('background_image')->nullable();
            $table->string('audio_url')->nullable();
            $table->json('settings')->nullable(); // Additional configuration
            
            $table->timestamps();
            
            $table->index(['class_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};