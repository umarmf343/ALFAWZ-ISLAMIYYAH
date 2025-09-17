<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create bookmarks table for storing user's Quran verse bookmarks.
 * Allows students to save specific verses with optional notes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('surah_id'); // Surah number (1-114)
            $table->unsignedInteger('ayah_number'); // Ayah number within surah
            $table->string('note', 500)->nullable(); // Optional user note
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['surah_id', 'ayah_number']);
            
            // Prevent duplicate bookmarks for same verse by same user
            $table->unique(['user_id', 'surah_id', 'ayah_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};