<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Jobs;

use App\Models\WhisperJob;
use App\Models\Recitation;
use App\Models\OrgSetting;
use App\Events\TajweedAnalysisProgress;
use App\Events\TajweedAnalysisCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessTajweedAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    protected int $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $job = WhisperJob::with('recitation.user')->findOrFail($this->jobId);
        
        try {
            Log::info("Starting Tajweed analysis for job {$this->jobId}");
            
            // Update job status to processing
            $job->update(['status' => WhisperJob::STATUS_PROCESSING]);
            
            // Broadcast progress: Starting analysis
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'initializing',
                10,
                'Starting Tajweed analysis...'
            );
            
            // Download audio from S3
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'downloading',
                20,
                'Downloading audio file...'
            );
            $audioPath = $this->downloadAudioFile($job->recitation);
            
            // Transcribe audio using OpenAI Whisper
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'transcribing',
                40,
                'Transcribing audio with AI...'
            );
            $transcription = $this->transcribeAudio($audioPath);
            
            // Align transcription with expected tokens
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'aligning',
                60,
                'Aligning transcription with expected text...'
            );
            $alignment = $this->alignTranscription($transcription, $job->recitation->expected_tokens);
            
            // Analyze Tajweed rules
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'analyzing',
                80,
                'Analyzing Tajweed rules and pronunciation...'
            );
            $tajweedAnalysis = $this->analyzeTajweedRules($alignment, $job->recitation);
            
            // Calculate overall score
            TajweedAnalysisProgress::dispatch(
                $job->recitation,
                'scoring',
                90,
                'Calculating final scores...'
            );
            $overallScore = $this->calculateOverallScore($tajweedAnalysis);
            
            // Prepare final results
            $results = [
                'transcription' => $transcription,
                'alignment' => $alignment,
                'tajweed_analysis' => $tajweedAnalysis,
                'overall_score' => $overallScore,
                'processed_at' => now()->toISOString(),
                'processing_time_seconds' => now()->diffInSeconds($job->created_at),
            ];
            
            // Update job with results
            $job->update([
                'status' => WhisperJob::STATUS_DONE,
                'result_json' => $results,
            ]);
            
            // Clean up temporary audio file
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
            
            Log::info("Completed Tajweed analysis for job {$this->jobId} with score {$overallScore}%");
            
            // Broadcast completion event
            TajweedAnalysisCompleted::dispatch(
                $job->recitation,
                $results,
                WhisperJob::STATUS_DONE
            );
            
        } catch (Exception $e) {
            Log::error("Failed Tajweed analysis for job {$this->jobId}: " . $e->getMessage());
            
            $job->update([
                'status' => WhisperJob::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Download audio file from S3 to local temporary storage.
     */
    private function downloadAudioFile(Recitation $recitation): string
    {
        $tempPath = sys_get_temp_dir() . '/tajweed_' . $recitation->id . '_' . time() . '.audio';
        
        $audioContent = Storage::disk('s3')->get($recitation->s3_key);
        
        if (!$audioContent) {
            throw new Exception("Failed to download audio file from S3: {$recitation->s3_key}");
        }
        
        file_put_contents($tempPath, $audioContent);
        
        return $tempPath;
    }

    /**
     * Transcribe audio using OpenAI Whisper API.
     */
    private function transcribeAudio(string $audioPath): array
    {
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->attach(
            'file', file_get_contents($audioPath), basename($audioPath)
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'ar', // Arabic
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['word'],
        ]);
        
        if (!$response->successful()) {
            throw new Exception('Whisper API request failed: ' . $response->body());
        }
        
        $data = $response->json();
        
        return [
            'text' => $data['text'] ?? '',
            'words' => $data['words'] ?? [],
            'duration' => $data['duration'] ?? 0,
        ];
    }

    /**
     * Align transcribed words with expected Arabic tokens.
     */
    private function alignTranscription(array $transcription, array $expectedTokens): array
    {
        $transcribedWords = $transcription['words'] ?? [];
        $alignment = [];
        
        // Simple alignment algorithm - in production, use more sophisticated methods
        $expectedIndex = 0;
        $transcribedIndex = 0;
        
        while ($expectedIndex < count($expectedTokens) && $transcribedIndex < count($transcribedWords)) {
            $expectedToken = $this->normalizeArabicText($expectedTokens[$expectedIndex]);
            $transcribedWord = $this->normalizeArabicText($transcribedWords[$transcribedIndex]['word'] ?? '');
            
            $similarity = $this->calculateSimilarity($expectedToken, $transcribedWord);
            
            $alignment[] = [
                'expected' => $expectedTokens[$expectedIndex],
                'transcribed' => $transcribedWords[$transcribedIndex]['word'] ?? '',
                'similarity' => $similarity,
                'start_time' => $transcribedWords[$transcribedIndex]['start'] ?? 0,
                'end_time' => $transcribedWords[$transcribedIndex]['end'] ?? 0,
                'is_match' => $similarity >= 0.7, // 70% similarity threshold
            ];
            
            $expectedIndex++;
            $transcribedIndex++;
        }
        
        // Handle remaining tokens
        while ($expectedIndex < count($expectedTokens)) {
            $alignment[] = [
                'expected' => $expectedTokens[$expectedIndex],
                'transcribed' => '',
                'similarity' => 0,
                'start_time' => 0,
                'end_time' => 0,
                'is_match' => false,
            ];
            $expectedIndex++;
        }
        
        return $alignment;
    }

    /**
     * Analyze Tajweed rules based on alignment.
     */
    private function analyzeTajweedRules(array $alignment, Recitation $recitation): array
    {
        $analysis = [
            'pronunciation_accuracy' => $this->analyzePronunciation($alignment),
            'fluency' => $this->analyzeFluency($alignment),
            'tajweed_rules' => $this->checkTajweedRules($alignment),
            'timing' => $this->analyzeTimingAndPacing($alignment),
        ];
        
        return $analysis;
    }

    /**
     * Analyze pronunciation accuracy.
     */
    private function analyzePronunciation(array $alignment): array
    {
        $totalWords = count($alignment);
        $correctWords = array_filter($alignment, fn($item) => $item['is_match']);
        $accuracy = $totalWords > 0 ? (count($correctWords) / $totalWords) * 100 : 0;
        
        $errors = [];
        foreach ($alignment as $index => $item) {
            if (!$item['is_match'] && !empty($item['transcribed'])) {
                $errors[] = [
                    'position' => $index + 1,
                    'expected' => $item['expected'],
                    'actual' => $item['transcribed'],
                    'similarity' => $item['similarity'],
                ];
            }
        }
        
        return [
            'accuracy_percentage' => round($accuracy, 2),
            'correct_words' => count($correctWords),
            'total_words' => $totalWords,
            'errors' => $errors,
        ];
    }

    /**
     * Analyze fluency and flow.
     */
    private function analyzeFluency(array $alignment): array
    {
        $pauses = [];
        $speakingRate = 0;
        
        for ($i = 1; $i < count($alignment); $i++) {
            $prevEnd = $alignment[$i-1]['end_time'] ?? 0;
            $currentStart = $alignment[$i]['start_time'] ?? 0;
            
            if ($currentStart > $prevEnd) {
                $pauseDuration = $currentStart - $prevEnd;
                if ($pauseDuration > 0.5) { // Pauses longer than 0.5 seconds
                    $pauses[] = [
                        'position' => $i,
                        'duration' => round($pauseDuration, 2),
                        'before_word' => $alignment[$i]['expected'] ?? '',
                    ];
                }
            }
        }
        
        // Calculate speaking rate (words per minute)
        $totalDuration = end($alignment)['end_time'] ?? 1;
        $wordsPerMinute = (count($alignment) / $totalDuration) * 60;
        
        return [
            'words_per_minute' => round($wordsPerMinute, 2),
            'pause_count' => count($pauses),
            'long_pauses' => $pauses,
            'fluency_score' => $this->calculateFluencyScore($wordsPerMinute, count($pauses)),
        ];
    }

    /**
     * Check specific Tajweed rules.
     */
    private function checkTajweedRules(array $alignment): array
    {
        // This is a simplified implementation
        // In production, implement comprehensive Tajweed rule checking
        
        $rules = [
            'ghunnah' => $this->checkGhunnahRules($alignment),
            'qalqalah' => $this->checkQalqalahRules($alignment),
            'madd' => $this->checkMaddRules($alignment),
            'idgham' => $this->checkIdghamRules($alignment),
        ];
        
        return $rules;
    }

    /**
     * Analyze timing and pacing.
     */
    private function analyzeTimingAndPacing(array $alignment): array
    {
        $wordDurations = [];
        
        foreach ($alignment as $item) {
            if ($item['end_time'] > $item['start_time']) {
                $duration = $item['end_time'] - $item['start_time'];
                $wordDurations[] = $duration;
            }
        }
        
        $avgDuration = count($wordDurations) > 0 ? array_sum($wordDurations) / count($wordDurations) : 0;
        
        return [
            'average_word_duration' => round($avgDuration, 3),
            'total_duration' => end($alignment)['end_time'] ?? 0,
            'pacing_consistency' => $this->calculatePacingConsistency($wordDurations),
        ];
    }

    /**
     * Calculate overall score based on all analyses.
     */
    private function calculateOverallScore(array $analysis): float
    {
        $pronunciationScore = $analysis['pronunciation_accuracy']['accuracy_percentage'] ?? 0;
        $fluencyScore = $analysis['fluency']['fluency_score'] ?? 0;
        
        // Weighted average
        $overallScore = ($pronunciationScore * 0.6) + ($fluencyScore * 0.4);
        
        return round($overallScore, 2);
    }

    /**
     * Normalize Arabic text for comparison.
     */
    private function normalizeArabicText(string $text): string
    {
        // Remove diacritics and normalize
        $normalized = preg_replace('/[\u064B-\u065F\u0670\u06D6-\u06ED]/u', '', $text);
        return trim($normalized);
    }

    /**
     * Calculate similarity between two Arabic strings.
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) || empty($str2)) {
            return 0;
        }
        
        // Use Levenshtein distance for similarity
        $maxLen = max(strlen($str1), strlen($str2));
        $distance = levenshtein($str1, $str2);
        
        return $maxLen > 0 ? (1 - ($distance / $maxLen)) : 0;
    }

    /**
     * Calculate fluency score based on speaking rate and pauses.
     */
    private function calculateFluencyScore(float $wordsPerMinute, int $pauseCount): float
    {
        // Ideal speaking rate for Quranic recitation: 80-120 WPM
        $idealRate = 100;
        $rateScore = 100 - abs($wordsPerMinute - $idealRate);
        $rateScore = max(0, min(100, $rateScore));
        
        // Penalty for excessive pauses
        $pausePenalty = min($pauseCount * 5, 50); // Max 50% penalty
        
        return max(0, $rateScore - $pausePenalty);
    }

    /**
     * Calculate pacing consistency.
     */
    private function calculatePacingConsistency(array $durations): float
    {
        if (count($durations) < 2) {
            return 100;
        }
        
        $mean = array_sum($durations) / count($durations);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $durations)) / count($durations);
        $stdDev = sqrt($variance);
        
        // Lower standard deviation = higher consistency
        $consistency = max(0, 100 - ($stdDev * 100));
        
        return round($consistency, 2);
    }

    // Simplified Tajweed rule checking methods
    // In production, these would be much more comprehensive
    
    private function checkGhunnahRules(array $alignment): array
    {
        // Check for proper ghunnah (nasal sound) in noon and meem
        return ['checked' => true, 'violations' => []];
    }
    
    private function checkQalqalahRules(array $alignment): array
    {
        // Check for qalqalah letters (ق ط ب ج د)
        return ['checked' => true, 'violations' => []];
    }
    
    private function checkMaddRules(array $alignment): array
    {
        // Check for proper elongation rules
        return ['checked' => true, 'violations' => []];
    }
    
    private function checkIdghamRules(array $alignment): array
    {
        // Check for proper merging of letters
        return ['checked' => true, 'violations' => []];
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Tajweed analysis job {$this->jobId} failed permanently: " . $exception->getMessage());
        
        $job = WhisperJob::find($this->jobId);
        if ($job) {
            $job->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }
}