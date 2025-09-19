<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhisperJob;
use App\Jobs\ProcessTajweedAnalysis;
use Illuminate\Support\Facades\Log;

class ReprocessFailedTajweedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tajweed:reprocess-failed 
                            {--limit=10 : Maximum number of jobs to reprocess}
                            {--older-than=24 : Only reprocess jobs older than X hours}
                            {--dry-run : Show what would be reprocessed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess failed Tajweed analysis jobs';

    /**
     * Execute the console command to reprocess failed Tajweed jobs.
     * Finds failed jobs older than specified hours and requeues them.
     *
     * @return int Command exit code (0 = success)
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $olderThanHours = (int) $this->option('older-than');
        $dryRun = $this->option('dry-run');

        $this->info('Searching for failed Tajweed jobs...');

        // Find failed jobs older than specified hours
        $failedJobs = WhisperJob::where('status', WhisperJob::STATUS_FAILED)
            ->where('created_at', '<', now()->subHours($olderThanHours))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs found matching criteria.');
            return 0;
        }

        $this->info("Found {$failedJobs->count()} failed jobs to reprocess.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No jobs will actually be reprocessed.');
            $this->table(
                ['ID', 'Recitation ID', 'Failed At', 'Error'],
                $failedJobs->map(fn($job) => [
                    $job->id,
                    $job->recitation_id,
                    $job->updated_at->format('Y-m-d H:i:s'),
                    substr($job->error ?? 'Unknown error', 0, 50) . '...'
                ])->toArray()
            );
            return 0;
        }

        $reprocessed = 0;
        $errors = 0;

        foreach ($failedJobs as $job) {
            try {
                // Reset job status and clear error
                $job->update([
                    'status' => WhisperJob::STATUS_QUEUED,
                    'error' => null,
                    'updated_at' => now()
                ]);

                // Dispatch the job again
                ProcessTajweedAnalysis::dispatch($job->id);
                
                $this->line("✓ Reprocessed job {$job->id} for recitation {$job->recitation_id}");
                $reprocessed++;
                
                Log::info('Reprocessed failed Tajweed job', [
                    'job_id' => $job->id,
                    'recitation_id' => $job->recitation_id
                ]);
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to reprocess job {$job->id}: {$e->getMessage()}");
                $errors++;
                
                Log::error('Failed to reprocess Tajweed job', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\nReprocessing complete:");
        $this->info("- Successfully reprocessed: {$reprocessed} jobs");
        if ($errors > 0) {
            $this->warn("- Errors encountered: {$errors} jobs");
        }

        return $errors > 0 ? 1 : 0;
    }
}