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

        Schema::create('srs_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('memorization_plans')->onDelete('cascade');
            $table->integer('surah_id');
            $table->integer('ayah_id');
            $table->timestamp('due_at');
            $table->float('ease_factor')->default(2.5); // SM-2 algorithm
            $table->integer('interval')->default(1); // Days
            $table->integer('repetitions')->default(0);
            $table->float('confidence_score')->default(0); // 0-1
            $table->integer('review_count')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'due_at']);
            $table->index(['surah_id', 'ayah_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('srs_queues');
    }
};