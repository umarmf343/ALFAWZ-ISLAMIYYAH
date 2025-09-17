<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create org_settings table and add settings column to users table.
     * Enables site-wide and per-user Tajweed analysis toggles.
     */
    public function up(): void
    {
        // org_settings — single row table to store site-wide defaults
        if (!Schema::hasTable('org_settings')) {
            Schema::create('org_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('tajweed_default')->default(true);
                $table->timestamps();
            });
        }

        // users.settings JSON for per-user preferences (future-proof)
        if (!Schema::hasColumn('users', 'settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('settings')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('org_settings')) {
            Schema::drop('org_settings');
        }
        
        if (Schema::hasColumn('users', 'settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('settings');
            });
        }
    }
};