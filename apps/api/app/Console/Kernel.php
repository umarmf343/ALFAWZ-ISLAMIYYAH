<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * Includes queue heartbeat for monitoring queue health.
     *
     * @param Schedule $schedule Laravel scheduler instance
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // Queue heartbeat - runs every minute to indicate queue system is active
        // This is used by the admin tools to check if the queue system is running
        $schedule->call(function () {
            Cache::put('queue_heartbeat', now()->toISOString(), now()->addMinutes(10));
        })->everyMinute()->name('queue-heartbeat');

        // Run queued jobs (for database queue driver)
        $schedule->command('queue:work --stop-when-empty --max-time=3600')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->name('queue-worker');

        // Clean up old failed jobs (older than 7 days)
        $schedule->command('queue:prune-failed --hours=168')
                 ->daily()
                 ->at('02:00')
                 ->name('queue-cleanup');

        // Clear expired cache entries
        $schedule->command('cache:prune-stale-tags')
                 ->hourly()
                 ->name('cache-cleanup');

        // Generate daily analytics snapshots
        $schedule->call(function () {
            // This could trigger analytics generation
            // For now, just log that the schedule is running
            \Log::info('Daily analytics schedule executed', [
                'timestamp' => now()->toISOString()
            ]);
        })->dailyAt('01:00')->name('daily-analytics');

        // Send daily progress summaries to teachers at 6 PM
        $schedule->command('send:daily-progress-summaries')
                 ->dailyAt('18:00')
                 ->timezone('Africa/Lagos')
                 ->withoutOverlapping();

        // Weekly leaderboard snapshots
        $schedule->call(function () {
            // This could trigger leaderboard snapshot generation
            \Log::info('Weekly leaderboard schedule executed', [
                'timestamp' => now()->toISOString()
            ]);
        })->weeklyOn(1, '03:00')->name('weekly-leaderboard'); // Monday at 3 AM

        // Monthly cleanup tasks
        $schedule->call(function () {
            // Clean up old temporary files, logs, etc.
            \Log::info('Monthly cleanup schedule executed', [
                'timestamp' => now()->toISOString()
            ]);
        })->monthlyOn(1, '04:00')->name('monthly-cleanup'); // 1st of month at 4 AM

        // Tajweed-specific scheduled tasks
        
        // Reprocess failed Tajweed jobs daily
        $schedule->command('tajweed:reprocess-failed --limit=20 --older-than=6')
                 ->dailyAt('05:00')
                 ->name('tajweed-reprocess-failed');

        // Clean up old Tajweed audio files weekly (keep analyzed files)
        $schedule->command('tajweed:cleanup-audio --days=30 --keep-analyzed --force')
                 ->weeklyOn(0, '06:00') // Sunday at 6 AM
                 ->name('tajweed-audio-cleanup');

        // More aggressive cleanup monthly (remove all old files)
        $schedule->command('tajweed:cleanup-audio --days=90 --force')
                 ->monthlyOn(15, '07:00') // 15th of month at 7 AM
                 ->name('tajweed-audio-deep-cleanup');
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SendDailyProgressSummaries::class,
        \App\Console\Commands\OpenApiSyncCommand::class,
    ];

    /**
     * Register the commands for the application.
     * Auto-discovers commands in the Commands directory.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}