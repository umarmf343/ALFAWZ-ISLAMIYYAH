<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('hotspot_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotspot_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('interaction_type', ['play', 'pause', 'complete', 'click', 'hover', 'view'])
                  ->default('click');
            $table->integer('duration_seconds')->nullable();
            $table->decimal('completion_percentage', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['hotspot_id', 'user_id']);
            $table->index(['user_id', 'timestamp']);
            $table->index(['hotspot_id', 'interaction_type']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_interactions');
    }
};