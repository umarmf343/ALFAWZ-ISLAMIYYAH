<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create recitations table for user audio uploads with expected text for comparison.
     * Stores user audio uploads with Quran verse context and analysis metadata.
     */
    public function up(): void
    {
        Schema::create('recitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('surah')->nullable();
            $table->unsignedInteger('from_ayah')->nullable();
            $table->unsignedInteger('to_ayah')->nullable();
            $table->string('s3_key'); // S3 object key (no scheme)
            $table->string('mime')->nullable();
            $table->json('expected_tokens')->nullable(); // normalized expected Arabic tokens
            $table->integer('duration_seconds')->nullable();
            $table->boolean('tajweed_enabled')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('s3_key');
            $table->index('tajweed_enabled');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recitations');
    }
};