<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

class TajweedAnalyzerService
{
    /**
     * analyze compares transcribed text with expected tokens and returns Tajweed analysis.
     * This is a heuristic implementation that can be extended with more sophisticated rules.
     */
    public function analyze(string $transcription, array $expectedTokens = []): array
    {
        // Normalize both transcription and expected text
        $normalizedTranscription = $this->normalizeArabic($transcription);
        $expectedText = implode(' ', $expectedTokens);
        $normalizedExpected = $this->normalizeArabic($expectedText);

        // Split into words for comparison
        $transcribedWords = $this->splitArabicWords($normalizedTranscription);
        $expectedWords = $this->splitArabicWords($normalizedExpected);

        // Perform word-by-word analysis
        $wordAnalysis = $this->analyzeWords($transcribedWords, $expectedWords);
        
        // Calculate overall scores
        $scores = $this->calculateScores($wordAnalysis);
        
        // Generate color-coded highlights
        $highlights = $this->generateHighlights($wordAnalysis, $expectedWords);

        return [
            'transcription' => $transcription,
            'expected' => $expectedText,
            'word_analysis' => $wordAnalysis,
            'scores' => $scores,
            'highlights' => $highlights,
            'overall_score' => $scores['overall'],
        ];
    }

    /**
     * normalizeArabic removes diacritics and normalizes Arabic text for comparison.
     */
    private function normalizeArabic(string $text): string
    {
        // Remove diacritics (Tashkeel)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }

    /**
     * splitArabicWords splits Arabic text into individual words.
     */
    private function splitArabicWords(string $text): array
    {
        return array_filter(explode(' ', $text), fn($word) => !empty(trim($word)));
    }

    /**
     * analyzeWords performs word-by-word comparison and analysis.
     */
    private function analyzeWords(array $transcribedWords, array $expectedWords): array
    {
        $analysis = [];
        $maxLength = max(count($transcribedWords), count($expectedWords));

        for ($i = 0; $i < $maxLength; $i++) {
            $transcribed = $transcribedWords[$i] ?? '';
            $expected = $expectedWords[$i] ?? '';
            
            $wordAnalysis = [
                'position' => $i,
                'transcribed' => $transcribed,
                'expected' => $expected,
                'match_type' => $this->getMatchType($transcribed, $expected),
                'similarity' => $this->calculateSimilarity($transcribed, $expected),
                'tajweed_issues' => $this->detectTajweedIssues($transcribed, $expected),
            ];
            
            $analysis[] = $wordAnalysis;
        }

        return $analysis;
    }

    /**
     * getMatchType determines the type of match between transcribed and expected words.
     */
    private function getMatchType(string $transcribed, string $expected): string
    {
        if (empty($transcribed) && empty($expected)) {
            return 'empty';
        }
        
        if (empty($transcribed)) {
            return 'missing';
        }
        
        if (empty($expected)) {
            return 'extra';
        }
        
        if ($transcribed === $expected) {
            return 'exact';
        }
        
        $similarity = $this->calculateSimilarity($transcribed, $expected);
        
        if ($similarity > 0.8) {
            return 'close';
        } elseif ($similarity > 0.5) {
            return 'partial';
        } else {
            return 'different';
        }
    }

    /**
     * calculateSimilarity computes similarity score between two Arabic words.
     */
    private function calculateSimilarity(string $word1, string $word2): float
    {
        if (empty($word1) && empty($word2)) {
            return 1.0;
        }
        
        if (empty($word1) || empty($word2)) {
            return 0.0;
        }
        
        // Use Levenshtein distance for similarity
        $maxLen = max(mb_strlen($word1, 'UTF-8'), mb_strlen($word2, 'UTF-8'));
        $distance = levenshtein($word1, $word2);
        
        return 1.0 - ($distance / $maxLen);
    }

    /**
     * detectTajweedIssues identifies potential Tajweed issues (extensible heuristics).
     */
    private function detectTajweedIssues(string $transcribed, string $expected): array
    {
        $issues = [];
        
        // Heuristic: Check for common letter substitutions
        $commonSubstitutions = [
            'ض' => 'د', // Dad vs Dal
            'ظ' => 'ز', // Zah vs Zay
            'ث' => 'س', // Thaa vs Seen
            'ذ' => 'ز', // Thal vs Zay
        ];
        
        foreach ($commonSubstitutions as $correct => $incorrect) {
            if (strpos($expected, $correct) !== false && strpos($transcribed, $incorrect) !== false) {
                $issues[] = [
                    'type' => 'letter_substitution',
                    'expected' => $correct,
                    'found' => $incorrect,
                    'description' => "Possible confusion between {$correct} and {$incorrect}"
                ];
            }
        }
        
        return $issues;
    }

    /**
     * calculateScores computes overall performance scores.
     */
    private function calculateScores(array $wordAnalysis): array
    {
        $totalWords = count($wordAnalysis);
        if ($totalWords === 0) {
            return ['tajweed' => 0, 'fluency' => 0, 'accuracy' => 0, 'overall' => 0];
        }
        
        $exactMatches = 0;
        $closeMatches = 0;
        $totalSimilarity = 0;
        $tajweedIssues = 0;
        
        foreach ($wordAnalysis as $word) {
            if ($word['match_type'] === 'exact') {
                $exactMatches++;
            } elseif ($word['match_type'] === 'close') {
                $closeMatches++;
            }
            
            $totalSimilarity += $word['similarity'];
            $tajweedIssues += count($word['tajweed_issues']);
        }
        
        $accuracy = ($exactMatches + ($closeMatches * 0.8)) / $totalWords;
        $fluency = $totalSimilarity / $totalWords;
        $tajweed = max(0, 1 - ($tajweedIssues / $totalWords));
        $overall = ($accuracy * 0.4 + $fluency * 0.3 + $tajweed * 0.3);
        
        return [
            'accuracy' => round($accuracy * 100, 1),
            'fluency' => round($fluency * 100, 1),
            'tajweed' => round($tajweed * 100, 1),
            'overall' => round($overall * 100, 1),
        ];
    }

    /**
     * generateHighlights creates color-coded highlights for the UI.
     */
    private function generateHighlights(array $wordAnalysis, array $expectedWords): array
    {
        $highlights = [];
        
        foreach ($wordAnalysis as $word) {
            $color = match($word['match_type']) {
                'exact' => 'green',
                'close' => 'yellow',
                'partial' => 'orange',
                'different' => 'red',
                'missing' => 'gray',
                'extra' => 'purple',
                default => 'black'
            };
            
            $highlights[] = [
                'word' => $word['expected'],
                'color' => $color,
                'similarity' => $word['similarity'],
                'issues' => $word['tajweed_issues'],
            ];
        }
        
        return $highlights;
    }
}