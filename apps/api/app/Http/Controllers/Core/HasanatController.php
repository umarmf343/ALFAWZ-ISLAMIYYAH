<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\QuranProgress;
use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HasanatController extends Controller
{
    /**
     * Get user's hasanat progress and statistics.
     *
     * @param Request $request HTTP request with optional user_id parameter
     * @return JsonResponse JSON response with hasanat progress data
     */
    public function getProgress(Request $request): JsonResponse
    {
        $userId = $request->query('user_id', Auth::id());
        
        // Ensure user can only access their own data unless they're a teacher
        if ($userId != Auth::id() && !Auth::user()->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cacheKey = "hasanat_progress_{$userId}";
        
        $progress = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($userId) {
            return $this->calculateUserProgress($userId);
        });

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /**
     * Get user's achievements and milestones.
     *
     * @param Request $request HTTP request with optional user_id parameter
     * @return JsonResponse JSON response with achievements data
     */
    public function getAchievements(Request $request): JsonResponse
    {
        $userId = $request->query('user_id', Auth::id());
        
        if ($userId != Auth::id() && !Auth::user()->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $achievements = $this->calculateAchievements($userId);

        return response()->json([
            'success' => true,
            'data' => $achievements
        ]);
    }

    /**
     * Get recent hasanat activities for a user.
     *
     * @param Request $request HTTP request with optional user_id and limit parameters
     * @return JsonResponse JSON response with recent activities
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        $userId = $request->query('user_id', Auth::id());
        $limit = $request->query('limit', 20);
        
        if ($userId != Auth::id() && !Auth::user()->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = $this->getHasanatActivities($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Award hasanat for a specific activity.
     *
     * @param Request $request HTTP request with activity details
     * @return JsonResponse JSON response with awarded hasanat
     */
    public function awardHasanat(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:recitation,assignment,bonus,streak,achievement',
            'hasanat' => 'required|integer|min:1|max:10000',
            'description' => 'required|string|max:255',
            'reference_id' => 'nullable|string',
            'surah_id' => 'nullable|integer|min:1|max:114',
            'ayah_count' => 'nullable|integer|min:1'
        ]);

        // Only teachers and admins can award hasanat
        if (!Auth::user()->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hasanatAwarded = $this->processHasanatAward(
            $request->user_id,
            $request->type,
            $request->hasanat,
            $request->description,
            $request->reference_id,
            $request->surah_id,
            $request->ayah_count
        );

        return response()->json([
            'success' => true,
            'data' => [
                'hasanat_awarded' => $hasanatAwarded,
                'new_total' => $this->getUserTotalHasanat($request->user_id),
                'level_up' => $this->checkLevelUp($request->user_id)
            ]
        ]);
    }

    /**
     * Get leaderboard data for hasanat rankings.
     *
     * @param Request $request HTTP request with optional scope and period parameters
     * @return JsonResponse JSON response with leaderboard data
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'global'); // global, class
        $period = $request->query('period', 'all_time'); // all_time, monthly, weekly
        $classId = $request->query('class_id');
        $limit = $request->query('limit', 50);

        $leaderboard = $this->calculateLeaderboard($scope, $period, $classId, $limit);

        return response()->json([
            'success' => true,
            'data' => $leaderboard
        ]);
    }

    /**
     * Calculate comprehensive user progress including hasanat, streaks, and levels.
     *
     * @param int $userId User ID to calculate progress for
     * @return array Comprehensive progress data
     */
    private function calculateUserProgress(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Get total hasanat from quran_progress table
        $totalHasanat = QuranProgress::where('user_id', $userId)->sum('hasanat');
        
        // Calculate daily hasanat (today)
        $dailyHasanat = QuranProgress::where('user_id', $userId)
            ->whereDate('updated_at', today())
            ->sum('hasanat');
        
        // Calculate weekly hasanat
        $weeklyHasanat = QuranProgress::where('user_id', $userId)
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('hasanat');
        
        // Calculate monthly hasanat
        $monthlyHasanat = QuranProgress::where('user_id', $userId)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('hasanat');
        
        // Calculate current streak
        $streak = $this->calculateStreak($userId);
        
        // Calculate level and next level threshold
        $level = $this->calculateLevel($totalHasanat);
        $nextLevelThreshold = $this->getLevelThreshold($level + 1);
        
        return [
            'total_hasanat' => $totalHasanat,
            'daily_hasanat' => $dailyHasanat,
            'weekly_hasanat' => $weeklyHasanat,
            'monthly_hasanat' => $monthlyHasanat,
            'streak' => $streak,
            'level' => $level,
            'next_level_threshold' => $nextLevelThreshold,
            'progress_to_next_level' => ($totalHasanat / $nextLevelThreshold) * 100
        ];
    }

    /**
     * Calculate user achievements based on their activity and progress.
     *
     * @param int $userId User ID to calculate achievements for
     * @return array Array of achievements with unlock status
     */
    private function calculateAchievements(int $userId): array
    {
        $progress = QuranProgress::where('user_id', $userId)->get();
        $submissions = Submission::where('student_id', $userId)->get();
        $totalHasanat = $progress->sum('hasanat');
        $streak = $this->calculateStreak($userId);
        
        $achievements = [
            [
                'id' => 'first_recitation',
                'title' => 'First Steps',
                'description' => 'Complete your first Qur\'an recitation',
                'icon' => 'BookOpen',
                'hasanat' => 100,
                'category' => 'milestone',
                'is_unlocked' => $progress->count() > 0,
                'unlocked_at' => $progress->count() > 0 ? $progress->first()->created_at : null
            ],
            [
                'id' => 'first_surah',
                'title' => 'Surah Master',
                'description' => 'Complete recitation of an entire surah',
                'icon' => 'Award',
                'hasanat' => 500,
                'category' => 'recitation',
                'is_unlocked' => $this->hasCompletedSurah($userId),
                'unlocked_at' => $this->getFirstSurahCompletionDate($userId)
            ],
            [
                'id' => 'week_streak',
                'title' => 'Consistent Reader',
                'description' => 'Maintain a 7-day recitation streak',
                'icon' => 'Flame',
                'hasanat' => 300,
                'category' => 'consistency',
                'is_unlocked' => $streak >= 7,
                'unlocked_at' => $streak >= 7 ? $this->getStreakAchievementDate($userId, 7) : null
            ],
            [
                'id' => 'month_streak',
                'title' => 'Devoted Student',
                'description' => 'Maintain a 30-day recitation streak',
                'icon' => 'Crown',
                'hasanat' => 1000,
                'category' => 'consistency',
                'is_unlocked' => $streak >= 30,
                'unlocked_at' => $streak >= 30 ? $this->getStreakAchievementDate($userId, 30) : null
            ],
            [
                'id' => 'hasanat_1000',
                'title' => 'Blessed Reader',
                'description' => 'Earn 1,000 total hasanat',
                'icon' => 'Star',
                'hasanat' => 200,
                'category' => 'milestone',
                'is_unlocked' => $totalHasanat >= 1000,
                'unlocked_at' => $this->getHasanatMilestoneDate($userId, 1000)
            ],
            [
                'id' => 'hasanat_10000',
                'title' => 'Spiritual Warrior',
                'description' => 'Earn 10,000 total hasanat',
                'icon' => 'Trophy',
                'hasanat' => 1000,
                'category' => 'milestone',
                'is_unlocked' => $totalHasanat >= 10000,
                'unlocked_at' => $this->getHasanatMilestoneDate($userId, 10000)
            ],
            [
                'id' => 'perfect_assignment',
                'title' => 'Excellence Seeker',
                'description' => 'Score 100% on an assignment',
                'icon' => 'Target',
                'hasanat' => 500,
                'category' => 'accuracy',
                'is_unlocked' => $submissions->where('score', 100)->count() > 0,
                'unlocked_at' => $submissions->where('score', 100)->first()?->created_at
            ],
            [
                'id' => 'ten_assignments',
                'title' => 'Dedicated Learner',
                'description' => 'Complete 10 assignments',
                'icon' => 'Medal',
                'hasanat' => 750,
                'category' => 'milestone',
                'is_unlocked' => $submissions->count() >= 10,
                'unlocked_at' => $submissions->count() >= 10 ? $submissions->skip(9)->first()?->created_at : null
            ]
        ];

        return $achievements;
    }

    /**
     * Get recent hasanat earning activities for a user.
     *
     * @param int $userId User ID to get activities for
     * @param int $limit Maximum number of activities to return
     * @return array Array of recent hasanat activities
     */
    private function getHasanatActivities(int $userId, int $limit = 20): array
    {
        // Get recent quran progress entries
        $recentProgress = QuranProgress::where('user_id', $userId)
            ->where('hasanat', '>', 0)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
        
        // Get recent submissions
        $recentSubmissions = Submission::where('student_id', $userId)
            ->with('assignment')
            ->orderBy('created_at', 'desc')
            ->limit($limit / 2)
            ->get();
        
        $activities = [];
        
        // Add progress activities
        foreach ($recentProgress as $progress) {
            $activities[] = [
                'id' => 'progress_' . $progress->id,
                'type' => 'recitation',
                'hasanat' => $progress->hasanat,
                'description' => "Recited Surah {$progress->surah_id}, Ayah {$progress->ayah_id}",
                'timestamp' => $progress->updated_at->toISOString(),
                'surah_id' => $progress->surah_id,
                'ayah_id' => $progress->ayah_id
            ];
        }
        
        // Add submission activities
        foreach ($recentSubmissions as $submission) {
            $hasanat = $this->calculateSubmissionHasanat($submission);
            $activities[] = [
                'id' => 'submission_' . $submission->id,
                'type' => 'assignment',
                'hasanat' => $hasanat,
                'description' => "Completed assignment: {$submission->assignment->title}",
                'timestamp' => $submission->created_at->toISOString(),
                'assignment_id' => $submission->assignment_id,
                'score' => $submission->score
            ];
        }
        
        // Sort by timestamp and limit
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activities, 0, $limit);
    }

    /**
     * Process hasanat award and update user progress.
     *
     * @param int $userId User ID to award hasanat to
     * @param string $type Type of activity
     * @param int $hasanat Amount of hasanat to award
     * @param string $description Description of the activity
     * @param string|null $referenceId Optional reference ID
     * @param int|null $surahId Optional surah ID
     * @param int|null $ayahCount Optional ayah count
     * @return int Amount of hasanat awarded
     */
    private function processHasanatAward(
        int $userId,
        string $type,
        int $hasanat,
        string $description,
        ?string $referenceId = null,
        ?int $surahId = null,
        ?int $ayahCount = null
    ): int {
        DB::beginTransaction();
        
        try {
            // Create or update quran progress entry
            if ($type === 'recitation' && $surahId) {
                $progress = QuranProgress::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'surah_id' => $surahId,
                        'ayah_id' => 1 // Default to first ayah for surah-level tracking
                    ],
                    [
                        'recited_count' => 0,
                        'memorized_confidence' => 0,
                        'hasanat' => 0
                    ]
                );
                
                $progress->increment('hasanat', $hasanat);
                $progress->increment('recited_count');
                $progress->touch('last_seen_at');
            } else {
                // For non-recitation activities, create a general progress entry
                QuranProgress::create([
                    'user_id' => $userId,
                    'surah_id' => $surahId ?? 1,
                    'ayah_id' => 1,
                    'recited_count' => 0,
                    'memorized_confidence' => 0,
                    'hasanat' => $hasanat,
                    'last_seen_at' => now()
                ]);
            }
            
            // Clear user's hasanat cache
            Cache::forget("hasanat_progress_{$userId}");
            
            // Update leaderboard entry
            $this->updateLeaderboardEntry($userId, $hasanat);
            
            DB::commit();
            
            return $hasanat;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate leaderboard rankings based on scope and period.
     *
     * @param string $scope Leaderboard scope (global, class)
     * @param string $period Time period (all_time, monthly, weekly)
     * @param int|null $classId Class ID for class-scoped leaderboards
     * @param int $limit Maximum number of entries to return
     * @return array Leaderboard data
     */
    private function calculateLeaderboard(
        string $scope,
        string $period,
        ?int $classId = null,
        int $limit = 50
    ): array {
        $query = DB::table('quran_progress')
            ->select('user_id', DB::raw('SUM(hasanat) as total_hasanat'))
            ->groupBy('user_id');
        
        // Apply time period filter
        if ($period === 'weekly') {
            $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'monthly') {
            $query->whereMonth('updated_at', now()->month)
                  ->whereYear('updated_at', now()->year);
        }
        
        // Apply scope filter
        if ($scope === 'class' && $classId) {
            $classUserIds = DB::table('class_members')
                ->where('class_id', $classId)
                ->pluck('user_id');
            $query->whereIn('user_id', $classUserIds);
        }
        
        $rankings = $query->orderBy('total_hasanat', 'desc')
            ->limit($limit)
            ->get();
        
        // Enrich with user data
        $leaderboard = [];
        foreach ($rankings as $index => $ranking) {
            $user = User::find($ranking->user_id);
            if ($user) {
                $leaderboard[] = [
                    'rank' => $index + 1,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_hasanat' => $ranking->total_hasanat,
                    'level' => $this->calculateLevel($ranking->total_hasanat),
                    'streak' => $this->calculateStreak($user->id)
                ];
            }
        }
        
        return $leaderboard;
    }

    /**
     * Calculate user's current recitation streak.
     *
     * @param int $userId User ID to calculate streak for
     * @return int Current streak in days
     */
    private function calculateStreak(int $userId): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        while (true) {
            $hasActivity = QuranProgress::where('user_id', $userId)
                ->whereDate('updated_at', $currentDate)
                ->exists();
            
            if (!$hasActivity) {
                break;
            }
            
            $streak++;
            $currentDate->subDay();
            
            // Prevent infinite loops
            if ($streak > 365) {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Calculate user level based on total hasanat.
     *
     * @param int $totalHasanat Total hasanat earned
     * @return int User level
     */
    private function calculateLevel(int $totalHasanat): int
    {
        // Level formula: every 1000 hasanat = 1 level, with exponential scaling
        if ($totalHasanat < 100) return 1;
        if ($totalHasanat < 500) return 2;
        if ($totalHasanat < 1000) return 3;
        
        // After level 3, each level requires 1000 more hasanat than the previous
        return min(100, 3 + floor(($totalHasanat - 1000) / 1000));
    }

    /**
     * Get hasanat threshold for a specific level.
     *
     * @param int $level Target level
     * @return int Hasanat threshold for the level
     */
    private function getLevelThreshold(int $level): int
    {
        if ($level <= 1) return 100;
        if ($level <= 2) return 500;
        if ($level <= 3) return 1000;
        
        return 1000 + (($level - 3) * 1000);
    }

    /**
     * Get total hasanat for a user.
     *
     * @param int $userId User ID
     * @return int Total hasanat
     */
    private function getUserTotalHasanat(int $userId): int
    {
        return QuranProgress::where('user_id', $userId)->sum('hasanat');
    }

    /**
     * Check if user leveled up after hasanat award.
     *
     * @param int $userId User ID
     * @return bool Whether user leveled up
     */
    private function checkLevelUp(int $userId): bool
    {
        $totalHasanat = $this->getUserTotalHasanat($userId);
        $currentLevel = $this->calculateLevel($totalHasanat);
        
        // Get previous level from cache or calculate
        $previousLevel = Cache::get("user_level_{$userId}", $currentLevel - 1);
        Cache::put("user_level_{$userId}", $currentLevel, now()->addHours(24));
        
        return $currentLevel > $previousLevel;
    }

    /**
     * Calculate hasanat for a submission based on score and assignment type.
     *
     * @param Submission $submission Submission to calculate hasanat for
     * @return int Calculated hasanat
     */
    private function calculateSubmissionHasanat(Submission $submission): int
    {
        $baseHasanat = 100;
        $scoreMultiplier = ($submission->score ?? 50) / 100;
        
        return (int) ($baseHasanat * $scoreMultiplier);
    }

    /**
     * Check if user has completed a full surah.
     *
     * @param int $userId User ID
     * @return bool Whether user has completed a surah
     */
    private function hasCompletedSurah(int $userId): bool
    {
        // This would need more complex logic based on ayah completion tracking
        // For now, simplified to check if user has significant progress in any surah
        return QuranProgress::where('user_id', $userId)
            ->where('recited_count', '>=', 5)
            ->exists();
    }

    /**
     * Get date when user first completed a surah.
     *
     * @param int $userId User ID
     * @return string|null Completion date
     */
    private function getFirstSurahCompletionDate(int $userId): ?string
    {
        $firstCompletion = QuranProgress::where('user_id', $userId)
            ->where('recited_count', '>=', 5)
            ->orderBy('created_at')
            ->first();
        
        return $firstCompletion?->created_at?->toISOString();
    }

    /**
     * Get date when user achieved a specific streak milestone.
     *
     * @param int $userId User ID
     * @param int $streakDays Streak milestone
     * @return string|null Achievement date
     */
    private function getStreakAchievementDate(int $userId, int $streakDays): ?string
    {
        // This would require more sophisticated streak tracking
        // For now, estimate based on recent activity
        $recentActivity = QuranProgress::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->first();
        
        return $recentActivity?->updated_at?->subDays($streakDays - 1)?->toISOString();
    }

    /**
     * Get date when user reached a hasanat milestone.
     *
     * @param int $userId User ID
     * @param int $milestone Hasanat milestone
     * @return string|null Milestone date
     */
    private function getHasanatMilestoneDate(int $userId, int $milestone): ?string
    {
        // This would require tracking cumulative hasanat over time
        // For now, estimate based on progress entries
        $progress = QuranProgress::where('user_id', $userId)
            ->orderBy('created_at')
            ->get();
        
        $cumulative = 0;
        foreach ($progress as $entry) {
            $cumulative += $entry->hasanat;
            if ($cumulative >= $milestone) {
                return $entry->created_at->toISOString();
            }
        }
        
        return null;
    }

    /**
     * Update leaderboard entry for the user.
     *
     * @param int $userId User ID
     * @param int $hasanatEarned Amount of hasanat earned
     * @return void
     */
    private function updateLeaderboardEntry(int $userId, int $hasanatEarned): void
    {
        if ($hasanatEarned <= 0) {
            return;
        }

        // Get or create leaderboard entry for this week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $leaderboardEntry = \App\Models\LeaderboardEntry::firstOrCreate(
            [
                'user_id' => $userId,
                'period_start' => $startOfWeek,
                'period_end' => $endOfWeek,
                'period_type' => 'weekly'
            ],
            [
                'total_hasanat' => 0,
                'activities_completed' => 0,
                'streak_days' => 0,
                'last_activity_at' => now()
            ]
        );

        // Update the entry
        $leaderboardEntry->increment('total_hasanat', $hasanatEarned);
        $leaderboardEntry->increment('activities_completed');
        $leaderboardEntry->update(['last_activity_at' => now()]);

        // Update streak if this is a new day
        $lastActivity = $leaderboardEntry->last_activity_at;
        if (!$lastActivity || $lastActivity->format('Y-m-d') !== now()->format('Y-m-d')) {
            // Check if yesterday had activity to maintain streak
            $yesterday = now()->subDay();
            $yesterdayEntry = \App\Models\LeaderboardEntry::where('user_id', $userId)
                ->where('period_type', 'weekly')
                ->where('last_activity_at', '>=', $yesterday->startOfDay())
                ->where('last_activity_at', '<=', $yesterday->endOfDay())
                ->exists();

            if ($yesterdayEntry || $leaderboardEntry->streak_days === 0) {
                $leaderboardEntry->increment('streak_days');
            } else {
                $leaderboardEntry->update(['streak_days' => 1]);
            }
        }
    }
}