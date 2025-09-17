<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create hotspots table.
     * Hotspots are interactive elements within assignments (audio, tooltips, clickable areas).
     */
    public function up(): void
    {
        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            
            // Position and display
            $table->decimal('x_coordinate', 8, 4); // Percentage or pixel position
            $table->decimal('y_coordinate', 8, 4);
            $table->integer('width')->default(50); // Hotspot area width
            $table->integer('height')->default(50); // Hotspot area height
            
            // Content
            $table->enum('type', ['audio', 'tooltip', 'image', 'video', 'text']);
            $table->string('title')->nullable();
            $table->text('content')->nullable(); // Tooltip text or description
            $table->string('media_url')->nullable(); // Audio/video/image URL
            $table->string('thumbnail_url')->nullable();
            
            // Interaction settings
            $table->boolean('is_required')->default(false); // Must be clicked/played
            $table->integer('play_count')->default(0); // Track interactions
            $table->boolean('auto_play')->default(false);
            $table->integer('duration_seconds')->nullable(); // For audio/video
            
            // Styling
            $table->string('icon')->nullable(); // Icon class or emoji
            $table->string('color', 7)->default('#FFD700'); // Hex color
            $table->enum('animation', ['pulse', 'bounce', 'glow', 'none'])->default('pulse');
            
            // Ordering and grouping
            $table->integer('order_index')->default(0);
            $table->string('group_name')->nullable(); // Group related hotspots
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['assignment_id', 'is_active']);
            $table->index('order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};