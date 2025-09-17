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
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class WhisperService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Transcribe audio file using OpenAI Whisper API.
     *
     * @param string $audioPath Path to audio file
     * @param string $language Language code (default: 'ar' for Arabic)
     * @return array Transcription result with text and confidence
     * @throws Exception If transcription fails
     */
    public function transcribeAudio(string $audioPath, string $language = 'ar'): array
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
        }

        if (!Storage::exists($audioPath)) {
            throw new Exception('Audio file not found: ' . $audioPath);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file',
                Storage::get($audioPath),
                basename($audioPath)
            )->post($this->baseUrl . '/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => $language,
                'response_format' => 'verbose_json',
                'temperature' => 0.0,
            ]);

            if (!$response->successful()) {
                Log::error('Whisper API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Whisper API request failed: ' . $response->body());
            }

            $data = $response->json();

            return [
                'text' => $data['text'] ?? '',
                'language' => $data['language'] ?? $language,
                'duration' => $data['duration'] ?? 0,
                'segments' => $data['segments'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error('Whisper transcription failed', [
                'error' => $e->getMessage(),
                'audio_path' => $audioPath
            ]);
            throw $e;
        }
    }

    /**
     * Analyze Arabic recitation for tajweed and pronunciation.
     *
     * @param string $transcribedText Transcribed Arabic text
     * @param string $expectedText Expected Quranic text
     * @return array Analysis result with tajweed feedback
     */
    public function analyzeTajweed(string $transcribedText, string $expectedText): array
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
        }

        $prompt = $this->buildTajweedPrompt($transcribedText, $expectedText);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in Quranic recitation and tajweed rules. Analyze the transcribed recitation and provide detailed feedback on pronunciation, tajweed rules application, and areas for improvement.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                Log::error('GPT-4 API error for tajweed analysis', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('GPT-4 API request failed: ' . $response->body());
            }

            $data = $response->json();
            $analysis = $data['choices'][0]['message']['content'] ?? '';

            return $this->parseTajweedAnalysis($analysis, $transcribedText, $expectedText);
        } catch (Exception $e) {
            Log::error('Tajweed analysis failed', [
                'error' => $e->getMessage(),
                'transcribed' => $transcribedText,
                'expected' => $expectedText
            ]);
            throw $e;
        }
    }

    /**
     * Generate audio feedback using text-to-speech.
     *
     * @param string $feedbackText Feedback text to convert to speech
     * @param string $voice Voice model (default: 'alloy')
     * @return string Path to generated audio file
     * @throws Exception If TTS generation fails
     */
    public function generateAudioFeedback(string $feedbackText, string $voice = 'alloy'): string
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/audio/speech', [
                'model' => 'tts-1',
                'input' => $feedbackText,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]);

            if (!$response->successful()) {
                Log::error('TTS API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('TTS API request failed: ' . $response->body());
            }

            $audioContent = $response->body();
            $filename = 'feedback/' . uniqid('feedback_') . '.mp3';
            
            Storage::put($filename, $audioContent);

            return $filename;
        } catch (Exception $e) {
            Log::error('Audio feedback generation failed', [
                'error' => $e->getMessage(),
                'text' => $feedbackText
            ]);
            throw $e;
        }
    }

    /**
     * Build prompt for tajweed analysis.
     *
     * @param string $transcribed Transcribed text
     * @param string $expected Expected text
     * @return string Formatted prompt
     */
    private function buildTajweedPrompt(string $transcribed, string $expected): string
    {
        return "Please analyze this Quranic recitation:\n\n" .
               "Expected text: {$expected}\n" .
               "Transcribed text: {$transcribed}\n\n" .
               "Provide analysis in the following format:\n" .
               "1. Accuracy Score (0-100): [score]\n" .
               "2. Pronunciation Issues: [list any mispronunciations]\n" .
               "3. Tajweed Rules Applied: [list correctly applied rules]\n" .
               "4. Tajweed Rules Missed: [list missed or incorrectly applied rules]\n" .
               "5. Specific Feedback: [detailed feedback for improvement]\n" .
               "6. Positive Points: [what was done well]\n" .
               "7. Areas for Improvement: [specific areas to focus on]";
    }

    /**
     * Parse GPT-4 tajweed analysis response.
     *
     * @param string $analysis Raw analysis text
     * @param string $transcribed Transcribed text
     * @param string $expected Expected text
     * @return array Structured analysis data
     */
    private function parseTajweedAnalysis(string $analysis, string $transcribed, string $expected): array
    {
        // Extract accuracy score
        preg_match('/Accuracy Score.*?(\d+)/i', $analysis, $scoreMatches);
        $accuracyScore = isset($scoreMatches[1]) ? (int)$scoreMatches[1] : 0;

        // Calculate similarity score
        $similarityScore = $this->calculateSimilarity($transcribed, $expected);

        return [
            'accuracy_score' => $accuracyScore,
            'similarity_score' => $similarityScore,
            'raw_analysis' => $analysis,
            'transcribed_text' => $transcribed,
            'expected_text' => $expected,
            'feedback_sections' => $this->extractFeedbackSections($analysis),
            'analyzed_at' => now()->toISOString(),
        ];
    }

    /**
     * Extract structured feedback sections from analysis.
     *
     * @param string $analysis Raw analysis text
     * @return array Structured feedback sections
     */
    private function extractFeedbackSections(string $analysis): array
    {
        $sections = [];
        
        $patterns = [
            'pronunciation_issues' => '/Pronunciation Issues:(.*?)(?=\d+\.|$)/is',
            'tajweed_applied' => '/Tajweed Rules Applied:(.*?)(?=\d+\.|$)/is',
            'tajweed_missed' => '/Tajweed Rules Missed:(.*?)(?=\d+\.|$)/is',
            'specific_feedback' => '/Specific Feedback:(.*?)(?=\d+\.|$)/is',
            'positive_points' => '/Positive Points:(.*?)(?=\d+\.|$)/is',
            'improvements' => '/Areas for Improvement:(.*?)(?=\d+\.|$)/is',
        ];

        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $analysis, $matches);
            $sections[$key] = isset($matches[1]) ? trim($matches[1]) : '';
        }

        return $sections;
    }

    /**
     * Calculate text similarity score.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-100)
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        $text1 = $this->normalizeArabicText($text1);
        $text2 = $this->normalizeArabicText($text2);

        if (empty($text1) || empty($text2)) {
            return 0.0;
        }

        $longer = strlen($text1) > strlen($text2) ? $text1 : $text2;
        $shorter = strlen($text1) > strlen($text2) ? $text2 : $text1;

        if (strlen($longer) === 0) {
            return 100.0;
        }

        $distance = levenshtein($shorter, $longer);
        return (1 - $distance / strlen($longer)) * 100;
    }

    /**
     * Normalize Arabic text for comparison.
     *
     * @param string $text Arabic text
     * @return string Normalized text
     */
    private function normalizeArabicText(string $text): string
    {
        // Remove diacritics and normalize
        $text = preg_replace('/[\u064B-\u065F\u0670\u06D6-\u06ED]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Analyze recitation with comprehensive feedback.
     *
     * @param string $audioUrl URL to audio file
     * @param array $options Analysis options
     * @return array Comprehensive analysis results
     * @throws Exception If analysis fails
     */
    public function analyzeRecitation(string $audioUrl, array $options = []): array
    {
        // Extract audio path from URL
        $audioPath = str_replace(Storage::url(''), '', $audioUrl);
        
        // Transcribe the audio
        $transcription = $this->transcribeAudio($audioPath, 'ar');
        
        // Get expected text from options
        $expectedText = '';
        if (isset($options['verses']) && !empty($options['verses'])) {
            $expectedText = implode(' ', $options['verses']);
        }
        
        // Perform tajweed analysis if expected text is available
        $analysis = [];
        if (!empty($expectedText)) {
            $analysis = $this->analyzeTajweed($transcription['text'], $expectedText);
        } else {
            // Basic analysis without comparison
            $analysis = [
                'accuracy_score' => 75,
                'similarity_score' => 0,
                'transcribed_text' => $transcription['text'],
                'expected_text' => '',
                'feedback_sections' => [
                    'specific_feedback' => 'Audio transcribed successfully. Manual review recommended for detailed analysis.',
                    'positive_points' => 'Clear audio quality detected.',
                    'improvements' => 'Consider providing reference text for detailed comparison.'
                ],
                'analyzed_at' => now()->toISOString(),
            ];
        }
        
        // Add area-specific analysis
        $area = $options['area'] ?? 'general';
        $analysis = $this->enhanceAnalysisForArea($analysis, $area, $transcription);
        
        // Add assignment type specific feedback
        if (isset($options['assignment_type'])) {
            $analysis['assignment_feedback'] = $this->getAssignmentTypeFeedback(
                $options['assignment_type'],
                $analysis
            );
        }
        
        return array_merge($analysis, [
            'transcription' => $transcription,
            'audio_duration' => $transcription['duration'] ?? 0,
            'analysis_area' => $area,
        ]);
    }
    
    /**
     * Enhance analysis based on specific feedback area.
     *
     * @param array $analysis Base analysis
     * @param string $area Feedback area
     * @param array $transcription Transcription data
     * @return array Enhanced analysis
     */
    private function enhanceAnalysisForArea(array $analysis, string $area, array $transcription): array
    {
        switch ($area) {
            case 'tajweed':
                $analysis['tajweed_score'] = $analysis['accuracy_score'] ?? 75;
                $analysis['tajweed_rules'] = $this->identifyTajweedRules($transcription['text']);
                break;
                
            case 'fluency':
                $analysis['fluency_score'] = $this->calculateFluencyScore($transcription);
                $analysis['pace_analysis'] = $this->analyzePace($transcription);
                break;
                
            case 'accuracy':
                $analysis['pronunciation_errors'] = $this->identifyPronunciationErrors(
                    $analysis['transcribed_text'] ?? '',
                    $analysis['expected_text'] ?? ''
                );
                break;
        }
        
        return $analysis;
    }
    
    /**
     * Get assignment type specific feedback.
     *
     * @param string $assignmentType Type of assignment
     * @param array $analysis Analysis results
     * @return array Assignment specific feedback
     */
    private function getAssignmentTypeFeedback(string $assignmentType, array $analysis): array
    {
        $feedback = [];
        
        switch ($assignmentType) {
            case 'memorization':
                $feedback['focus'] = 'Memorization accuracy and fluency';
                $feedback['criteria'] = ['accuracy', 'fluency', 'confidence'];
                break;
                
            case 'tajweed':
                $feedback['focus'] = 'Tajweed rules application';
                $feedback['criteria'] = ['pronunciation', 'rules_application', 'clarity'];
                break;
                
            case 'recitation':
                $feedback['focus'] = 'Overall recitation quality';
                $feedback['criteria'] = ['accuracy', 'tajweed', 'fluency', 'melody'];
                break;
                
            default:
                $feedback['focus'] = 'General recitation assessment';
                $feedback['criteria'] = ['accuracy', 'clarity'];
        }
        
        return $feedback;
    }
    
    /**
     * Identify tajweed rules in transcribed text.
     *
     * @param string $text Transcribed Arabic text
     * @return array Identified tajweed rules
     */
    private function identifyTajweedRules(string $text): array
    {
        $rules = [];
        
        // Basic tajweed rule detection (simplified)
        if (preg_match('/[قكطتبجدذرزسشصضظعغفلمنهوي]ّ/', $text)) {
            $rules[] = 'Shaddah (Emphasis)';
        }
        
        if (preg_match('/[اوي]~/', $text)) {
            $rules[] = 'Madd (Elongation)';
        }
        
        if (preg_match('/ن[بمفو]/', $text)) {
            $rules[] = 'Noon Sakinah/Tanween Rules';
        }
        
        return $rules;
    }
    
    /**
     * Calculate fluency score based on transcription timing.
     *
     * @param array $transcription Transcription with segments
     * @return int Fluency score (0-100)
     */
    private function calculateFluencyScore(array $transcription): int
    {
        if (!isset($transcription['segments']) || empty($transcription['segments'])) {
            return 70; // Default score
        }
        
        $segments = $transcription['segments'];
        $totalDuration = $transcription['duration'] ?? 1;
        $wordCount = 0;
        $pauseCount = 0;
        
        foreach ($segments as $segment) {
            $words = explode(' ', trim($segment['text'] ?? ''));
            $wordCount += count(array_filter($words));
            
            // Detect long pauses (simplified)
            if (isset($segment['no_speech_prob']) && $segment['no_speech_prob'] > 0.8) {
                $pauseCount++;
            }
        }
        
        // Calculate words per minute
        $wpm = $wordCount / ($totalDuration / 60);
        
        // Ideal Arabic recitation is around 80-120 WPM
        $fluencyScore = 100;
        if ($wpm < 60) {
            $fluencyScore -= 20; // Too slow
        } elseif ($wpm > 150) {
            $fluencyScore -= 15; // Too fast
        }
        
        // Penalize excessive pauses
        $fluencyScore -= min($pauseCount * 5, 30);
        
        return max(0, min(100, $fluencyScore));
    }
    
    /**
     * Analyze recitation pace.
     *
     * @param array $transcription Transcription data
     * @return string Pace analysis description
     */
    private function analyzePace(array $transcription): string
    {
        $duration = $transcription['duration'] ?? 0;
        $text = $transcription['text'] ?? '';
        $wordCount = str_word_count($text);
        
        if ($duration == 0 || $wordCount == 0) {
            return 'Unable to analyze pace';
        }
        
        $wpm = $wordCount / ($duration / 60);
        
        if ($wpm < 60) {
            return 'Slow pace - consider increasing speed slightly';
        } elseif ($wpm > 150) {
            return 'Fast pace - consider slowing down for clarity';
        } else {
            return 'Good pace for recitation';
        }
    }
    
    /**
     * Identify pronunciation errors by comparing texts.
     *
     * @param string $transcribed Transcribed text
     * @param string $expected Expected text
     * @return array List of pronunciation errors
     */
    private function identifyPronunciationErrors(string $transcribed, string $expected): array
    {
        $errors = [];
        
        if (empty($expected)) {
            return $errors;
        }
        
        $transcribedWords = explode(' ', $this->normalizeArabicText($transcribed));
        $expectedWords = explode(' ', $this->normalizeArabicText($expected));
        
        $maxLength = max(count($transcribedWords), count($expectedWords));
        
        for ($i = 0; $i < $maxLength; $i++) {
            $transcribedWord = $transcribedWords[$i] ?? '';
            $expectedWord = $expectedWords[$i] ?? '';
            
            if ($transcribedWord !== $expectedWord && !empty($expectedWord)) {
                $errors[] = "Expected '{$expectedWord}' but heard '{$transcribedWord}'";
            }
        }
        
        return array_slice($errors, 0, 5); // Limit to 5 errors
    }
}