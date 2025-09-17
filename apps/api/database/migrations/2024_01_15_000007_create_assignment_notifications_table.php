<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create assignment_notifications table.
     * Tracks notifications sent to students about assignments and their status.
     */
    public function up(): void
    {
        Schema::create('assignment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            
            // Notification content
            $table->enum('type', [
                'assignment_created',
                'assignment_due_soon', 
                'assignment_overdue',
                'feedback_received',
                'assignment_completed',
                'reminder'
            ]);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional notification data
            
            // Status tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            
            // Delivery channels
            $table->boolean('sent_via_app')->default(true);
            $table->boolean('sent_via_email')->default(false);
            $table->boolean('sent_via_sms')->default(false);
            
            // Scheduling
            $table->timestamp('scheduled_for')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            
            // Priority and categorization
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('category')->nullable(); // Group related notifications
            
            $table->timestamps();
            
            $table->index(['student_id', 'is_read']);
            $table->index(['assignment_id', 'type']);
            $table->index(['scheduled_for', 'is_sent']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_notifications');
    }
};