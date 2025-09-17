<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create feature_flags table for rollout and kill switches.
 * Enables/disables features without redeployment.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'whisper_v2', 'payment_gateway'
            $table->boolean('enabled')->default(false);
            $table->string('segment')->nullable(); // optional user segment filter
            $table->text('note')->nullable(); // description or rollout notes
            $table->timestamps();
            
            // Index for fast key lookups
            $table->index('key');
            $table->index(['enabled', 'segment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};