<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add hasanat_total field to users table for tracking total spiritual rewards.
     */
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('hasanat_total')->default(0)->after('preferences');
            $table->index('hasanat_total'); // For leaderboard queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['hasanat_total']);
            $table->dropColumn('hasanat_total');
        });
    }
};