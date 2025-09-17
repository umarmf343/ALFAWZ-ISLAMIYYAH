<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for teacher oversight fields in srs_queues table
     */
    public function up(): void
    {
        Schema::table('srs_queues', function (Blueprint $table) {
            $table->string('audio_path')->nullable()->after('confidence_score');
            $table->json('tajweed_analysis')->nullable()->after('audio_path');
            $table->enum('review_status', ['pending', 'reviewed', 'approved'])->default('pending')->after('tajweed_analysis');
            $table->text('teacher_feedback')->nullable()->after('review_status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('teacher_feedback');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('last_reviewed_at')->nullable()->after('reviewed_at');
            
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['review_status', 'last_reviewed_at']);
            $table->index(['user_id', 'audio_path']);
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('srs_queues', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['review_status', 'last_reviewed_at']);
            $table->dropIndex(['user_id', 'audio_path']);
            
            $table->dropColumn([
                'audio_path',
                'tajweed_analysis',
                'review_status',
                'teacher_feedback',
                'reviewed_by',
                'reviewed_at',
                'last_reviewed_at'
            ]);
        });
    }
};