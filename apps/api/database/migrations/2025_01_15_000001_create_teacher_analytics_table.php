<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create teacher_analytics table.
     * Stores analytics data for teacher dashboard metrics.
     */
    public function up(): void
    {
        Schema::create('teacher_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->integer('total_students')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0.00); // Percentage with 2 decimal places
            $table->integer('hotspot_interactions')->default(0);
            $table->integer('game_sessions')->default(0);
            $table->integer('high_scores')->default(0);
            $table->integer('active_assignments')->default(0);
            $table->integer('pending_submissions')->default(0);
            $table->integer('total_classes')->default(0);
            $table->decimal('average_score', 5, 2)->default(0.00);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('teacher_id');
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_analytics');
    }
};