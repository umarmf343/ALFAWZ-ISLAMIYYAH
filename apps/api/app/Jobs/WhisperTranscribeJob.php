<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Jobs;

use App\Models\WhisperJob;
use App\Services\TajweedAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class WhisperTranscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $jobId;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job: download S3 audio, call OpenAI Whisper, run Tajweed analysis.
     */
    public function handle(TajweedAnalyzerService $analyzer): void
    {
        $job = WhisperJob::with('recitation')->find($this->jobId);
        if (!$job) {
            Log::warning("WhisperJob {$this->jobId} not found");
            return;
        }

        try {
            $job->update(['status' => 'processing']);

            // 1) Download audio from S3 to temp file
            $s3Key = $job->recitation->s3_key;
            $tempPath = storage_path('app/temp/' . basename($s3Key));
            
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $audioContent = Storage::disk('s3')->get($s3Key);
            file_put_contents($tempPath, $audioContent);

            // 2) Call OpenAI Whisper API
            $transcription = $this->callWhisperAPI($tempPath);

            // 3) Run Tajweed analysis
            $analysis = $analyzer->analyze(
                $transcription,
                $job->recitation->expected_tokens ?? []
            );

            // 4) Save results
            $job->update([
                'status' => 'done',
                'result_json' => [
                    'transcription' => $transcription,
                    'analysis' => $analysis,
                    'processed_at' => now()->toISOString(),
                ]
            ]);

            // Cleanup temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

        } catch (\Exception $e) {
            Log::error("WhisperTranscribeJob {$this->jobId} failed: " . $e->getMessage());
            $job->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * callWhisperAPI sends audio file to OpenAI Whisper and returns transcription text.
     */
    private function callWhisperAPI(string $filePath): string
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->attach(
            'file', file_get_contents($filePath), basename($filePath)
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'ar', // Arabic
        ]);

        if (!$response->successful()) {
            throw new \Exception('Whisper API failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['text'] ?? '';
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $job = WhisperJob::find($this->jobId);
        if ($job) {
            $job->update([
                'status' => 'failed',
                'error' => $exception->getMessage()
            ]);
        }
    }
}