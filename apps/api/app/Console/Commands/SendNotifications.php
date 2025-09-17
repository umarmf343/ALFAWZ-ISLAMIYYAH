<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send {--type=all : Type of notifications to send (all, due-soon, overdue, cleanup)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled notifications for assignments and clean up old notifications';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $type = $this->option('type');
        
        try {
            switch ($type) {
                case 'due-soon':
                    $this->sendDueSoonNotifications();
                    break;
                    
                case 'overdue':
                    $this->sendOverdueNotifications();
                    break;
                    
                case 'cleanup':
                    $this->cleanupOldNotifications();
                    break;
                    
                case 'all':
                default:
                    $this->sendDueSoonNotifications();
                    $this->sendOverdueNotifications();
                    $this->cleanupOldNotifications();
                    break;
            }
            
            $this->info('Notification tasks completed successfully.');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error processing notifications: ' . $e->getMessage());
            Log::error('Notification command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Send due soon notifications for assignments.
     *
     * @return void
     */
    protected function sendDueSoonNotifications(): void
    {
        $this->info('Sending due soon notifications...');
        
        // Send notifications for assignments due in 24 hours
        $notifications24h = $this->notificationService->notifyAssignmentsDueSoon(24);
        
        // Send notifications for assignments due in 1 hour
        $notifications1h = $this->notificationService->notifyAssignmentsDueSoon(1);
        
        $total = $notifications24h->count() + $notifications1h->count();
        
        $this->info("Sent {$total} due soon notifications.");
        
        if ($total > 0) {
            Log::info('Due soon notifications sent', [
                '24_hours' => $notifications24h->count(),
                '1_hour' => $notifications1h->count(),
                'total' => $total
            ]);
        }
    }

    /**
     * Send overdue notifications for assignments.
     *
     * @return void
     */
    protected function sendOverdueNotifications(): void
    {
        $this->info('Sending overdue notifications...');
        
        $notifications = $this->notificationService->notifyAssignmentsOverdue();
        $count = $notifications->count();
        
        $this->info("Sent {$count} overdue notifications.");
        
        if ($count > 0) {
            Log::info('Overdue notifications sent', [
                'count' => $count
            ]);
        }
    }

    /**
     * Clean up old read notifications.
     *
     * @return void
     */
    protected function cleanupOldNotifications(): void
    {
        $this->info('Cleaning up old notifications...');
        
        $deletedCount = $this->notificationService->cleanupOldNotifications(30);
        
        $this->info("Deleted {$deletedCount} old notifications.");
        
        if ($deletedCount > 0) {
            Log::info('Old notifications cleaned up', [
                'deleted_count' => $deletedCount
            ]);
        }
    }
}