<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('hasanat')->default(0);
            $table->integer('surahs_completed')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('sujud_count')->default(0);
            $table->float('memorization_score')->default(0); // Sum of confidence scores
            $table->integer('streak_days')->default(0); // Consecutive days active
            $table->boolean('is_public')->default(true); // Privacy control
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
            
            $table->index(['hasanat', 'is_public']);
            $table->index(['user_id', 'last_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};