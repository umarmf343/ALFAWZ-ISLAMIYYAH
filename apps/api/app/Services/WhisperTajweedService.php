<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhisperTajweedService
{
    private string $openaiApiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        
        if (!$this->openaiApiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }
    
    /**
     * Analyze Quranic recitation for tajweed accuracy and pronunciation.
     * 
     * @param string $audioUrl URL to the audio file
     * @param int $surahId Surah number (1-114)
     * @param int $ayahId Ayah number within the surah
     * @return array Analysis results with tajweed score and feedback
     */
    public function analyzeRecitation(string $audioUrl, int $surahId, int $ayahId): array
    {
        try {
            // Get the expected Arabic text for this ayah
            $expectedText = $this->getAyahText($surahId, $ayahId);
            
            // Step 1: Transcribe audio using Whisper
            $transcription = $this->transcribeAudio($audioUrl);
            
            if (!$transcription) {
                throw new \Exception('Failed to transcribe audio');
            }
            
            // Step 2: Analyze tajweed using GPT-4 with Quranic knowledge
            $tajweedAnalysis = $this->analyzeTajweed(
                $transcription['text'],
                $expectedText,
                $surahId,
                $ayahId
            );
            
            return [
                'transcription' => $transcription['text'],
                'expected_text' => $expectedText,
                'tajweed_score' => $tajweedAnalysis['score'] ?? 0,
                'pronunciation_accuracy' => $tajweedAnalysis['pronunciation'] ?? 0,
                'fluency_score' => $tajweedAnalysis['fluency'] ?? 0,
                'feedback' => $tajweedAnalysis['feedback'] ?? [],
                'mistakes' => $tajweedAnalysis['mistakes'] ?? [],
                'suggestions' => $tajweedAnalysis['suggestions'] ?? []
            ];
            
        } catch (\Exception $e) {
            Log::error('Whisper Tajweed analysis failed', [
                'error' => $e->getMessage(),
                'surah_id' => $surahId,
                'ayah_id' => $ayahId,
                'audio_url' => $audioUrl
            ]);
            
            return [
                'error' => 'Analysis failed: ' . $e->getMessage(),
                'tajweed_score' => 0
            ];
        }
    }
    
    /**
     * Transcribe audio using OpenAI Whisper API.
     */
    private function transcribeAudio(string $audioUrl): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
        ])->attach(
            'file', file_get_contents($audioUrl), 'audio.mp3'
        )->post($this->baseUrl . '/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'ar', // Arabic
            'response_format' => 'json'
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        Log::error('Whisper transcription failed', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        
        return null;
    }
    
    /**
     * Analyze tajweed using GPT-4 with specialized Quranic knowledge.
     */
    private function analyzeTajweed(string $transcribedText, string $expectedText, int $surahId, int $ayahId): array
    {
        $prompt = $this->buildTajweedAnalysisPrompt($transcribedText, $expectedText, $surahId, $ayahId);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert Quranic recitation teacher specializing in tajweed analysis. Analyze Arabic recitations for pronunciation accuracy, tajweed rules, and fluency.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000
        ]);
        
        if ($response->successful()) {
            $result = $response->json();
            $analysis = $result['choices'][0]['message']['content'] ?? '';
            
            return $this->parseTajweedAnalysis($analysis);
        }
        
        Log::error('GPT-4 tajweed analysis failed', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        
        return ['score' => 0, 'feedback' => ['Analysis service unavailable']];
    }
    
    /**
     * Build comprehensive prompt for tajweed analysis.
     */
    private function buildTajweedAnalysisPrompt(string $transcribed, string $expected, int $surahId, int $ayahId): string
    {
        return "Analyze this Quranic recitation for tajweed accuracy:\n\n" .
               "Expected Text (Surah {$surahId}, Ayah {$ayahId}): {$expected}\n" .
               "Transcribed Recitation: {$transcribed}\n\n" .
               "Please provide analysis in this JSON format:\n" .
               "{\n" .
               "  \"score\": 85,\n" .
               "  \"pronunciation\": 90,\n" .
               "  \"fluency\": 80,\n" .
               "  \"feedback\": [\"Excellent makhraj\", \"Good rhythm\"],\n" .
               "  \"mistakes\": [\"Missed ghunnah in 'min'\"],\n" .
               "  \"suggestions\": [\"Practice elongation rules\"]\n" .
               "}\n\n" .
               "Focus on: makhraj (articulation points), sifaat (characteristics), " .
               "ghunnah, qalqalah, madd (elongation), waqf (stopping), and overall fluency.";
    }
    
    /**
     * Parse GPT-4 response into structured tajweed analysis.
     */
    private function parseTajweedAnalysis(string $analysis): array
    {
        // Try to extract JSON from the response
        if (preg_match('/\{[^}]*\}/', $analysis, $matches)) {
            $jsonData = json_decode($matches[0], true);
            if ($jsonData) {
                return $jsonData;
            }
        }
        
        // Fallback: basic parsing if JSON extraction fails
        return [
            'score' => $this->extractScore($analysis),
            'pronunciation' => 75,
            'fluency' => 70,
            'feedback' => $this->extractFeedback($analysis),
            'mistakes' => [],
            'suggestions' => []
        ];
    }
    
    /**
     * Extract numerical score from analysis text.
     */
    private function extractScore(string $text): int
    {
        if (preg_match('/score[:\s]*(\d+)/', $text, $matches)) {
            return (int) $matches[1];
        }
        return 75; // Default score
    }
    
    /**
     * Extract feedback points from analysis text.
     */
    private function extractFeedback(string $text): array
    {
        $feedback = [];
        
        if (strpos($text, 'excellent') !== false || strpos($text, 'good') !== false) {
            $feedback[] = 'Good recitation quality detected';
        }
        
        if (strpos($text, 'improve') !== false || strpos($text, 'practice') !== false) {
            $feedback[] = 'Areas for improvement identified';
        }
        
        return $feedback ?: ['Analysis completed'];
    }
    
    /**
     * Get Arabic text for a specific ayah (cached for performance).
     */
    private function getAyahText(int $surahId, int $ayahId): string
    {
        $cacheKey = "ayah_text_{$surahId}_{$ayahId}";
        
        return Cache::remember($cacheKey, now()->addDays(30), function () use ($surahId, $ayahId) {
            try {
                // Use Quran API to get the Arabic text
                $response = Http::get("https://api.quran.com/api/v4/verses/by_key/{$surahId}:{$ayahId}", [
                    'language' => 'ar',
                    'fields' => 'text_uthmani'
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['verse']['text_uthmani'] ?? '';
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch ayah text', [
                    'surah_id' => $surahId,
                    'ayah_id' => $ayahId,
                    'error' => $e->getMessage()
                ]);
            }
            
            return ''; // Return empty string if API fails
        });
    }
}