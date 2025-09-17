<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create org_settings table for organization-wide Tajweed configuration.
     * Stores global settings, limits, and policies for Tajweed analysis system.
     */
    public function up(): void
    {
        Schema::create('org_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
        });
        
        // Insert default Tajweed settings
        DB::table('org_settings')->insert([
            [
                'key' => 'tajweed_default_enabled',
                'value' => json_encode(true),
                'description' => 'Default organization-wide Tajweed analysis setting',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'tajweed_daily_limit_per_user',
                'value' => json_encode(50),
                'description' => 'Maximum number of Tajweed analyses per user per day',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'tajweed_max_audio_duration',
                'value' => json_encode(300),
                'description' => 'Maximum audio duration in seconds for Tajweed analysis',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'tajweed_retention_days',
                'value' => json_encode(30),
                'description' => 'Number of days to retain raw audio files',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_settings');
    }
};