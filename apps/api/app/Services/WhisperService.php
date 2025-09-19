<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhisperService
{
    protected $openaiApiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Analyze Quranic recitation using Whisper API for Tajweed feedback.
     *
     * @param string $audioUrl URL or path to the audio file
     * @param array $context Context information including expected verses
     * @return array Analysis results with Tajweed feedback
     */
    public function analyzeRecitation(string $audioUrl, array $context = []): array
    {
        try {
            // First, transcribe the audio using Whisper
            $transcription = $this->transcribeAudio($audioUrl);
            
            if (!$transcription) {
                return [
                    'success' => false,
                    'error' => 'Failed to transcribe audio'
                ];
            }

            // Analyze Tajweed based on transcription and expected text
            $tajweedAnalysis = $this->analyzeTajweed(
                $transcription,
                $context['verses'] ?? [],
                $context['area'] ?? 'general',
                $context['assignment_type'] ?? 'memorization'
            );

            return [
                'success' => true,
                'transcription' => $transcription,
                'tajweed_analysis' => $tajweedAnalysis,
                'confidence_score' => $tajweedAnalysis['overall_score'] ?? 0.0,
                'feedback' => $tajweedAnalysis['feedback'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Whisper analysis failed', [
                'error' => $e->getMessage(),
                'audio_url' => $audioUrl,
                'context' => $context
            ]);

            return [
                'success' => false,
                'error' => 'Audio analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Transcribe audio using OpenAI Whisper API.
     *
     * @param string $audioUrl
     * @return string|null
     */
    protected function transcribeAudio(string $audioUrl): ?string
    {
        if (!$this->openaiApiKey) {
            throw new \Exception('OpenAI API key not configured');
        }

        // Download audio file if it's a URL
        $audioPath = $this->downloadAudioFile($audioUrl);
        
        if (!$audioPath) {
            throw new \Exception('Failed to access audio file');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
            ])->attach(
                'file', file_get_contents($audioPath), basename($audioPath)
            )->post($this->baseUrl . '/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'ar', // Arabic
                'response_format' => 'json',
                'temperature' => 0.0 // More deterministic
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['text'] ?? null;
            }

            Log::error('Whisper API error', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } finally {
            // Clean up temporary file if we downloaded it
            if (str_starts_with($audioUrl, 'http') && file_exists($audioPath)) {
                unlink($audioPath);
            }
        }
    }

    /**
     * Analyze Tajweed based on transcription and expected text.
     *
     * @param string $transcription
     * @param array $expectedVerses
     * @param string $area
     * @param string $assignmentType
     * @return array
     */
    protected function analyzeTajweed(string $transcription, array $expectedVerses, string $area, string $assignmentType): array
    {
        $analysis = [
            'overall_score' => 0.0,
            'accuracy_score' => 0.0,
            'pronunciation_score' => 0.0,
            'fluency_score' => 0.0,
            'feedback' => [],
            'corrections' => [],
            'strengths' => []
        ];

        if (empty($expectedVerses)) {
            $analysis['feedback'][] = [
                'type' => 'info',
                'message' => 'Audio transcribed successfully. Expected text not available for detailed comparison.'
            ];
            $analysis['overall_score'] = 0.7; // Default score when we can't compare
            return $analysis;
        }

        $expectedText = implode(' ', $expectedVerses);
        
        // Basic text similarity analysis
        $similarity = $this->calculateTextSimilarity($transcription, $expectedText);
        $analysis['accuracy_score'] = $similarity;

        // Analyze common Tajweed issues
        $tajweedIssues = $this->detectTajweedIssues($transcription, $expectedText);
        
        foreach ($tajweedIssues as $issue) {
            $analysis['feedback'][] = [
                'type' => 'correction',
                'category' => $issue['category'],
                'message' => $issue['message'],
                'severity' => $issue['severity']
            ];
        }

        // Calculate pronunciation and fluency scores
        $analysis['pronunciation_score'] = $this->calculatePronunciationScore($transcription, $expectedText);
        $analysis['fluency_score'] = $this->calculateFluencyScore($transcription);

        // Overall score calculation
        $analysis['overall_score'] = (
            $analysis['accuracy_score'] * 0.4 +
            $analysis['pronunciation_score'] * 0.4 +
            $analysis['fluency_score'] * 0.2
        );

        // Add positive feedback for good performance
        if ($analysis['overall_score'] >= 0.8) {
            $analysis['strengths'][] = 'Excellent recitation! Your pronunciation and accuracy are very good.';
        } elseif ($analysis['overall_score'] >= 0.6) {
            $analysis['strengths'][] = 'Good recitation. Keep practicing to improve further.';
        }

        return $analysis;
    }

    /**
     * Download audio file from URL to temporary location.
     *
     * @param string $audioUrl
     * @return string|null Path to downloaded file
     */
    protected function downloadAudioFile(string $audioUrl): ?string
    {
        if (!str_starts_with($audioUrl, 'http')) {
            // Local file path
            $localPath = Storage::path($audioUrl);
            return file_exists($localPath) ? $localPath : null;
        }

        try {
            $tempPath = tempnam(sys_get_temp_dir(), 'whisper_audio_');
            $audioContent = Http::get($audioUrl)->body();
            file_put_contents($tempPath, $audioContent);
            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Failed to download audio file', ['url' => $audioUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate text similarity between transcription and expected text.
     *
     * @param string $transcription
     * @param string $expected
     * @return float Similarity score between 0 and 1
     */
    protected function calculateTextSimilarity(string $transcription, string $expected): float
    {
        // Normalize Arabic text (remove diacritics, extra spaces)
        $normalizedTranscription = $this->normalizeArabicText($transcription);
        $normalizedExpected = $this->normalizeArabicText($expected);

        // Simple Levenshtein distance-based similarity
        $maxLength = max(strlen($normalizedTranscription), strlen($normalizedExpected));
        
        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($normalizedTranscription, $normalizedExpected);
        return max(0, 1 - ($distance / $maxLength));
    }

    /**
     * Detect common Tajweed issues in transcription.
     *
     * @param string $transcription
     * @param string $expected
     * @return array
     */
    protected function detectTajweedIssues(string $transcription, string $expected): array
    {
        $issues = [];

        // This is a simplified implementation
        // In a real application, you would have more sophisticated Tajweed rules
        
        $normalizedTranscription = $this->normalizeArabicText($transcription);
        $normalizedExpected = $this->normalizeArabicText($expected);

        // Check for missing or extra words
        $transcriptionWords = explode(' ', $normalizedTranscription);
        $expectedWords = explode(' ', $normalizedExpected);

        if (count($transcriptionWords) !== count($expectedWords)) {
            $issues[] = [
                'category' => 'completeness',
                'message' => 'The recitation appears to have missing or extra words. Please ensure you recite the complete verse.',
                'severity' => 'medium'
            ];
        }

        // Check for significant differences that might indicate pronunciation issues
        $similarity = $this->calculateTextSimilarity($transcription, $expected);
        
        if ($similarity < 0.7) {
            $issues[] = [
                'category' => 'pronunciation',
                'message' => 'Some words may need attention. Focus on clear pronunciation of each letter.',
                'severity' => 'medium'
            ];
        }

        return $issues;
    }

    /**
     * Calculate pronunciation score based on text comparison.
     *
     * @param string $transcription
     * @param string $expected
     * @return float
     */
    protected function calculatePronunciationScore(string $transcription, string $expected): float
    {
        // This is a simplified implementation
        return $this->calculateTextSimilarity($transcription, $expected);
    }

    /**
     * Calculate fluency score based on transcription characteristics.
     *
     * @param string $transcription
     * @return float
     */
    protected function calculateFluencyScore(string $transcription): float
    {
        // Simple fluency metrics
        $wordCount = str_word_count($transcription);
        
        // Assume good fluency if we have a reasonable amount of text
        if ($wordCount >= 5) {
            return 0.8;
        } elseif ($wordCount >= 3) {
            return 0.6;
        } else {
            return 0.4;
        }
    }

    /**
     * Normalize Arabic text for comparison.
     *
     * @param string $text
     * @return string
     */
    protected function normalizeArabicText(string $text): string
    {
        // Remove diacritics (Tashkeel)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);
        
        // Normalize spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        return trim($text);
    }
}
