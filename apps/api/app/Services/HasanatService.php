<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use App\Models\User;
use App\Models\Hasanat;
use App\Models\Achievement;
use App\Models\Submission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing hasanat calculations and rewards
 */
class HasanatService
{
    /**
     * Calculate hasanat for Arabic text based on letter count
     * 
     * @param string $arabicText
     * @return int
     */
    public function calculateTextHasanat(string $arabicText): int
    {
        // Remove diacritics and normalize text
        $cleanText = $this->normalizeArabicText($arabicText);
        
        // Count Arabic letters (excluding spaces and punctuation)
        $letterCount = preg_match_all('/[\u0621-\u064A]/u', $cleanText);
        
        // Each Arabic letter = 10 hasanat (traditional Islamic belief)
        return $letterCount * 10;
    }

    /**
     * Award hasanat for assignment completion
     * 
     * @param User $user
     * @param Submission $submission
     * @return int Points awarded
     */
    public function awardAssignmentCompletion(User $user, Submission $submission): int
    {
        $basePoints = 100; // Base points for completion
        $qualityBonus = $this->calculateQualityBonus($submission);
        $textHasanat = 0;
        
        // Calculate hasanat from recited text if available
        if ($submission->assignment && $submission->assignment->expected_text) {
            $textHasanat = $this->calculateTextHasanat($submission->assignment->expected_text);
        }
        
        $totalPoints = $basePoints + $qualityBonus + $textHasanat;
        
        $hasanat = $user->awardHasanat(
            Hasanat::ACTIVITY_ASSIGNMENT_COMPLETION,
            $totalPoints,
            "Assignment completed: {$submission->assignment->title}",
            [
                'submission_id' => $submission->id,
                'assignment_id' => $submission->assignment_id,
                'base_points' => $basePoints,
                'quality_bonus' => $qualityBonus,
                'text_hasanat' => $textHasanat,
            ]
        );
        
        // Check for new achievements
        $this->checkAndAwardAchievements($user);
        
        return $totalPoints;
    }

    /**
     * Award hasanat for perfect recitation
     * 
     * @param User $user
     * @param Submission $submission
     * @param float $accuracyScore
     * @return int Points awarded
     */
    public function awardPerfectRecitation(User $user, Submission $submission, float $accuracyScore): int
    {
        if ($accuracyScore < 95.0) {
            return 0; // Not perfect enough
        }
        
        $points = 200; // Bonus for perfect recitation
        
        $user->awardHasanat(
            Hasanat::ACTIVITY_PERFECT_RECITATION,
            $points,
            "Perfect recitation achieved (Score: {$accuracyScore}%)",
            [
                'submission_id' => $submission->id,
                'accuracy_score' => $accuracyScore,
            ]
        );
        
        $this->checkAndAwardAchievements($user);
        
        return $points;
    }

    /**
     * Award hasanat for tajweed mastery
     * 
     * @param User $user
     * @param string $tajweedRule
     * @param float $masteryScore
     * @return int Points awarded
     */
    public function awardTajweedMastery(User $user, string $tajweedRule, float $masteryScore): int
    {
        if ($masteryScore < 90.0) {
            return 0;
        }
        
        $points = 150;
        
        $user->awardHasanat(
            Hasanat::ACTIVITY_TAJWEED_MASTERY,
            $points,
            "Mastered tajweed rule: {$tajweedRule}",
            [
                'rule' => $tajweedRule,
                'mastery_score' => $masteryScore,
            ]
        );
        
        $this->checkAndAwardAchievements($user);
        
        return $points;
    }

    /**
     * Award daily practice bonus
     * 
     * @param User $user
     * @return int Points awarded
     */
    public function awardDailyPractice(User $user): int
    {
        $cacheKey = "daily_practice:{$user->id}:" . now()->format('Y-m-d');
        
        // Check if already awarded today
        if (Cache::has($cacheKey)) {
            return 0;
        }
        
        $points = 50;
        
        $user->awardHasanat(
            Hasanat::ACTIVITY_DAILY_PRACTICE,
            $points,
            "Daily practice completed",
            ['date' => now()->format('Y-m-d')]
        );
        
        // Cache to prevent multiple awards per day
        Cache::put($cacheKey, true, now()->endOfDay());
        
        $this->checkAndAwardAchievements($user);
        
        return $points;
    }

    /**
     * Get user's hasanat progress and statistics
     * 
     * @param User $user
     * @return array
     */
    public function getUserProgress(User $user): array
    {
        $totalHasanat = $user->total_hasanat;
        $currentLevel = $user->current_level;
        $hasanatToNextLevel = $user->hasanat_to_next_level;
        
        // Get recent activity
        $recentActivity = $user->hasanat()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get activity breakdown
        $activityBreakdown = $user->hasanat()
            ->selectRaw('activity_type, SUM(points) as total_points, COUNT(*) as count')
            ->groupBy('activity_type')
            ->get()
            ->keyBy('activity_type');
        
        return [
            'total_hasanat' => $totalHasanat,
            'current_level' => $currentLevel,
            'hasanat_to_next_level' => $hasanatToNextLevel,
            'progress_percentage' => $hasanatToNextLevel > 0 
                ? round((($currentLevel - 1) * 1000 + (1000 - $hasanatToNextLevel)) / ($currentLevel * 1000) * 100, 1)
                : 100,
            'recent_activity' => $recentActivity,
            'activity_breakdown' => $activityBreakdown,
            'achievements_count' => $user->achievements()->count(),
        ];
    }

    /**
     * Check and award achievements for a user
     * 
     * @param User $user
     * @return array Newly earned achievements
     */
    public function checkAndAwardAchievements(User $user): array
    {
        $newAchievements = [];
        $availableAchievements = Achievement::getActive();
        
        foreach ($availableAchievements as $achievement) {
            if (!$user->hasAchievement($achievement->id) && $achievement->checkCriteria($user)) {
                if ($achievement->awardTo($user->id)) {
                    $newAchievements[] = $achievement;
                    
                    // Award bonus hasanat for earning achievement
                    $user->awardHasanat(
                        Hasanat::ACTIVITY_ACHIEVEMENT_UNLOCK,
                        $achievement->points_required,
                        "Achievement unlocked: {$achievement->name}",
                        ['achievement_id' => $achievement->id]
                    );
                    
                    Log::info("Achievement earned", [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                        'achievement_name' => $achievement->name,
                    ]);
                }
            }
        }
        
        return $newAchievements;
    }

    /**
     * Calculate quality bonus based on submission metrics
     * 
     * @param Submission $submission
     * @return int Bonus points
     */
    private function calculateQualityBonus(Submission $submission): int
    {
        $bonus = 0;
        $feedback = $submission->feedback;
        
        if (!$feedback) {
            return $bonus;
        }
        
        // Accuracy bonus
        if (isset($feedback['accuracy_score'])) {
            $accuracyScore = $feedback['accuracy_score'];
            if ($accuracyScore >= 95) {
                $bonus += 50;
            } elseif ($accuracyScore >= 85) {
                $bonus += 25;
            }
        }
        
        // Fluency bonus
        if (isset($feedback['fluency_score']) && $feedback['fluency_score'] >= 90) {
            $bonus += 30;
        }
        
        // Tajweed bonus
        if (isset($feedback['tajweed_score']) && $feedback['tajweed_score'] >= 85) {
            $bonus += 40;
        }
        
        return $bonus;
    }

    /**
     * Normalize Arabic text by removing diacritics
     * 
     * @param string $text
     * @return string
     */
    private function normalizeArabicText(string $text): string
    {
        // Remove Arabic diacritics (harakat)
        $text = preg_replace('/[\u064B-\u065F\u0670\u06D6-\u06ED]/u', '', $text);
        
        // Normalize and trim
        return trim($text);
    }

    /**
     * Get leaderboard data
     * 
     * @param string|null $period
     * @param int $limit
     * @return array
     */
    public function getLeaderboard(?string $period = null, int $limit = 10): array
    {
        $leaderboard = Hasanat::getLeaderboard($limit, $period);
        
        return $leaderboard->map(function ($entry) {
            return [
                'user' => [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'email' => $entry->user->email,
                ],
                'total_points' => $entry->total_points,
                'level' => max(1, floor($entry->total_points / 1000) + 1),
            ];
        })->toArray();
    }
}