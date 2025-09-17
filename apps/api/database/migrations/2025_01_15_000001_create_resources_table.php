<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create resources table for file management.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'video', 'audio', 'image', 'document', 'worksheet']);
            $table->string('filename'); // Original filename
            $table->string('file_path'); // S3 path
            $table->string('file_url'); // S3 URL
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('mime_type');
            $table->boolean('is_public')->default(false);
            $table->json('tags')->nullable(); // Array of tags
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['user_id', 'type']);
            $table->index(['is_public', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};