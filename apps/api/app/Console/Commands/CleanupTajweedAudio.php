<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recitation;
use App\Models\WhisperJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupTajweedAudio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tajweed:cleanup-audio 
                            {--days=30 : Delete audio files older than X days}
                            {--keep-analyzed : Keep audio files that have been successfully analyzed}
                            {--dry-run : Show what would be deleted without actually doing it}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old Tajweed audio files based on retention policies';

    /**
     * Execute the console command to clean up old audio files.
     * Removes audio files older than specified days with optional preservation of analyzed files.
     *
     * @return int Command exit code (0 = success)
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $keepAnalyzed = $this->option('keep-analyzed');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Searching for audio files older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Build query for old recitations
        $query = Recitation::where('created_at', '<', $cutoffDate)
            ->whereNotNull('audio_path');

        if ($keepAnalyzed) {
            // Only delete files that haven't been successfully analyzed
            $query->whereDoesntHave('whisperJobs', function ($q) {
                $q->where('status', 'completed');
            });
            $this->info('Keeping audio files that have been successfully analyzed.');
        }

        $oldRecitations = $query->get();

        if ($oldRecitations->isEmpty()) {
            $this->info('No audio files found matching cleanup criteria.');
            return 0;
        }

        $this->info("Found {$oldRecitations->count()} audio files to clean up.");

        // Calculate total size
        $totalSize = 0;
        $filesToDelete = [];
        
        foreach ($oldRecitations as $recitation) {
            if (Storage::disk('s3')->exists($recitation->audio_path)) {
                try {
                    $size = Storage::disk('s3')->size($recitation->audio_path);
                    $totalSize += $size;
                    $filesToDelete[] = [
                        'recitation' => $recitation,
                        'path' => $recitation->audio_path,
                        'size' => $size,
                        'age_days' => $recitation->created_at->diffInDays(now())
                    ];
                } catch (\Exception $e) {
                    $this->warn("Could not get size for {$recitation->audio_path}: {$e->getMessage()}");
                }
            }
        }

        $totalSizeMB = round($totalSize / 1024 / 1024, 2);
        $this->info("Total size to be freed: {$totalSizeMB} MB");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will actually be deleted.');
            $this->table(
                ['Recitation ID', 'File Path', 'Size (KB)', 'Age (days)'],
                collect($filesToDelete)->map(fn($file) => [
                    $file['recitation']->id,
                    substr($file['path'], 0, 50) . '...',
                    round($file['size'] / 1024, 2),
                    $file['age_days']
                ])->toArray()
            );
            return 0;
        }

        // Confirmation prompt
        if (!$force) {
            if (!$this->confirm("Are you sure you want to delete {$oldRecitations->count()} audio files ({$totalSizeMB} MB)?")) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $deleted = 0;
        $errors = 0;
        $freedSpace = 0;

        foreach ($filesToDelete as $file) {
            try {
                $recitation = $file['recitation'];
                $audioPath = $file['path'];
                
                // Delete from S3
                if (Storage::disk('s3')->exists($audioPath)) {
                    Storage::disk('s3')->delete($audioPath);
                    $freedSpace += $file['size'];
                }
                
                // Update recitation record
                $recitation->update(['audio_path' => null]);
                
                // Also clean up any associated failed jobs
                WhisperJob::where('recitation_id', $recitation->id)
                    ->where('status', 'failed')
                    ->delete();
                
                $this->line("✓ Deleted audio for recitation {$recitation->id}");
                $deleted++;
                
                Log::info('Cleaned up Tajweed audio file', [
                    'recitation_id' => $recitation->id,
                    'audio_path' => $audioPath,
                    'size_bytes' => $file['size']
                ]);
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to delete audio for recitation {$recitation->id}: {$e->getMessage()}");
                $errors++;
                
                Log::error('Failed to cleanup Tajweed audio', [
                    'recitation_id' => $recitation->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $freedSpaceMB = round($freedSpace / 1024 / 1024, 2);
        
        $this->info("\nCleanup complete:");
        $this->info("- Successfully deleted: {$deleted} files");
        $this->info("- Space freed: {$freedSpaceMB} MB");
        if ($errors > 0) {
            $this->warn("- Errors encountered: {$errors} files");
        }

        return $errors > 0 ? 1 : 0;
    }
}