<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add notification preference columns to users table.
     * These fields control email and in-app notification settings.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true);
            $table->boolean('daily_summary')->default(true);
            $table->boolean('review_completed_notifications')->default(true);
            $table->boolean('student_progress_notifications')->default(true);
            $table->boolean('system_notifications')->default(true);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications',
                'daily_summary',
                'review_completed_notifications',
                'student_progress_notifications',
                'system_notifications'
            ]);
        });
    }
};