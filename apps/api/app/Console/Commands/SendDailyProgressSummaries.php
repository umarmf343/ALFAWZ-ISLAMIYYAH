<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendDailyProgressSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:daily-summary {--date= : Specific date to generate summary for (Y-m-d format)}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily progress summaries to all teachers';
    
    protected $notificationService;
    
    /**
     * Create a new command instance.
     *
     * @param NotificationService $notificationService Service for handling notifications
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }
    
    /**
     * Execute the console command.
     * Sends daily progress summaries to all teachers with the preference enabled.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $this->info('Starting daily progress summary generation...');
        
        // Get the date for summary (default to yesterday)
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();
        
        $this->info("Generating summaries for: {$date->format('Y-m-d')}");
        
        try {
            $result = $this->notificationService->sendDailyProgressSummaries($date->format('Y-m-d'));
            
            $this->info("Successfully sent daily summaries to {$result['teachers_notified']} teachers");
            $this->info("Total reviews processed: {$result['total_reviews']}");
            $this->info("Active students: {$result['active_students']}");
            
            if ($result['teachers_notified'] > 0) {
                $this->info("Average quality score: " . number_format($result['avg_quality'], 2));
            }
            
            // Log successful execution
            Log::info('Daily progress summaries sent successfully', [
                'date' => $date->format('Y-m-d'),
                'teachers_notified' => $result['teachers_notified'],
                'total_reviews' => $result['total_reviews'],
                'active_students' => $result['active_students']
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to send daily progress summaries: ' . $e->getMessage());
            
            // Log the error
            Log::error('Daily progress summary command failed', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Get the console command arguments.
     *
     * @return array Command arguments
     */
    protected function getArguments(): array
    {
        return [];
    }
    
    /**
     * Get the console command options.
     *
     * @return array Command options
     */
    protected function getOptions(): array
    {
        return [
            ['date', null, InputOption::VALUE_OPTIONAL, 'Specific date to generate summary for (Y-m-d format)']
        ];
    }
}