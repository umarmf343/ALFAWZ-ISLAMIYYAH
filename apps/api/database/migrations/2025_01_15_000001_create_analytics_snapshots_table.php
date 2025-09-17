<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create analytics_snapshots table for storing precomputed analytics data.
     * Supports global, class, and regional analytics with different time periods.
     */
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // global, class, region
            $table->string('period'); // daily, weekly, monthly
            $table->json('data_json'); // e.g., {"active_users": 100, "verses_read": 5000}
            $table->timestamps();
            
            // Index for efficient querying
            $table->index(['scope', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};