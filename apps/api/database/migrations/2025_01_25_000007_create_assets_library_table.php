<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create assets_library table for shared content repository.
 * Stores reusable assets for teachers and assignments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // admin who uploaded
            $table->enum('kind', ['image', 'audio', 'pdf']); // asset type
            $table->string('title'); // display name
            $table->string('s3_url'); // storage URL
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->json('meta_json')->nullable(); // additional metadata (dimensions, duration, etc.)
            $table->timestamps();
            
            // Indexes for asset browsing
            $table->index(['kind', 'created_at']);
            $table->index('owner_id');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets_library');
    }
};