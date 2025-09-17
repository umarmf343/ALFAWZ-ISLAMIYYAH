<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Tajweed user preferences to users table.
     * Stores user-specific settings for Tajweed analysis features.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if settings column already exists
            if (!Schema::hasColumn('users', 'settings')) {
                $table->json('settings')->nullable()->after('remember_token');
            }
        });
        
        // Update existing users to have default Tajweed settings
        DB::table('users')->whereNull('settings')->update([
            'settings' => json_encode([
                'tajweed_enabled' => true,
                'notifications' => [
                    'analysis_complete' => true,
                    'feedback_received' => true
                ]
            ])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't drop the settings column as it might be used for other purposes
        // Only remove Tajweed-specific settings from existing records
        $users = DB::table('users')->whereNotNull('settings')->get();
        
        foreach ($users as $user) {
            $settings = json_decode($user->settings, true);
            if (isset($settings['tajweed_enabled'])) {
                unset($settings['tajweed_enabled']);
                DB::table('users')->where('id', $user->id)->update([
                    'settings' => json_encode($settings)
                ]);
            }
        }
    }
};