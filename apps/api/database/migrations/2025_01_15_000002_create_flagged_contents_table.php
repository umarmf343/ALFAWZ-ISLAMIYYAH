<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create flagged_contents table for content moderation.
     * Supports polymorphic relationships for different content types.
     */
    public function up(): void
    {
        Schema::create('flagged_contents', function (Blueprint $table) {
            $table->id();
            $table->morphs('content'); // journal_entry, group_post, etc.
            $table->foreignId('flagged_by')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, reviewed, removed
            $table->timestamps();
            
            // Index for efficient querying by status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flagged_contents');
    }
};