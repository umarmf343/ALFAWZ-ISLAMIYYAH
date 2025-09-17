<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyStats;
use App\Models\QuranProgress;
use App\Models\User;
use App\Models\ClassMember;
use App\Models\MemorizationPlan;
use App\Models\SrsQueue;
use App\Models\LeaderboardEntry;
use App\Models\LeaderboardInvite;
use App\Services\WhisperTajweedService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use App\Notifications\StudentProgressUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use GuzzleHttp\Client;
use Carbon\Carbon;

/**
 * Student Dashboard API Controller.
 * Handles student-specific dashboard data, progress tracking, and recommendations.
 */
class StudentController extends Controller
{
    /**
     * Get comprehensive dashboard data for student.
     */
    public function getDashboard(Request $request)
    {
        $user = auth()->user();
        $today = now()->startOfDay();
        
        $stats = Cache::remember("dashboard:{$user->id}:{$today->format('Y-m-d')}", 300, function () use ($user, $today) {
            $daily = DailyStats::where('user_id', $user->id)
                ->where('date', $today)
                ->first();
            
            $weekly = DailyStats::where('user_id', $user->id)
                ->whereBetween('date', [now()->subWeek(), $today])
                ->get();
            
            $recentSurahs = QuranProgress::where('user_id', $user->id)
                ->where('last_seen_at', '>=', now()->subDay())
                ->orderBy('last_seen_at', 'desc')
                ->take(10)
                ->get(['surah_id', 'ayah_number', 'last_seen_at', 'hasanat']);
            
            // Get yesterday's hasanat for animation
            $yesterday = now()->subDay()->startOfDay();
            $yesterdayStats = DailyStats::where('user_id', $user->id)
                ->where('date', $yesterday)
                ->first();
            $previousHasanat = $user->hasanat_total - ($daily?->hasanat_earned ?? 0);
            
            return [
                'greeting' => "Assalamu Alaikum, {$user->name}",
                'hasanat_total' => $user->hasanat_total ?? 0,
                'previous_hasanat' => max(0, $previousHasanat),
                'daily_progress' => [
                    'verses_read' => $daily?->verses_read ?? 0,
                    'goal' => $daily?->daily_goal ?? 10,
                    'time_spent' => $daily?->time_spent ?? 0,
                    'streak' => $daily?->streak_days ?? 0,
                    'goal_achieved' => $daily?->goal_achieved ?? false,
                    'progress_percentage' => $daily?->getProgressPercentage() ?? 0,
                ],
                'weekly_stats' => [
                    'verses' => $weekly->sum('verses_read'),
                    'hasanat' => $weekly->sum('hasanat_earned'),
                    'time_spent' => $weekly->sum('time_spent'),
                    'days_active' => $weekly->where('verses_read', '>', 0)->count(),
                ],
                'recent_surahs' => $recentSurahs,
                'badges' => $this->getUserBadges($user),
            ];
        });
        
        return response()->json($stats);
    }

    /**
     * Get approximate Hijri month (simplified calculation).
     */
    private function getHijriMonth(): int
    {
        // Simplified Hijri calculation - in production, use proper Hijri calendar library
        $gregorianMonth = now()->month;
        $hijriOffset = -1; // Approximate offset
        $hijriMonth = $gregorianMonth + $hijriOffset;
        
        if ($hijriMonth <= 0) {
            $hijriMonth += 12;
        }
        
        return $hijriMonth;
    }
    
    /**
     * Determine user's skill level based on their progress.
     */
    private function getUserLevel(User $user): string
    {
        $totalHasanat = $user->hasanat_total ?? 0;
        $recentActivity = DailyStats::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(30))
            ->avg('verses_read') ?? 0;
        
        if ($totalHasanat < 10000 || $recentActivity < 5) {
            return 'beginner';
        } elseif ($totalHasanat < 100000 || $recentActivity < 20) {
            return 'intermediate';
        } else {
            return 'advanced';
        }
    });
        
        return response()->json($stats);
    }

    /**
     * Update recitation progress and calculate hasanat.
     */
    public function updateRecitation(Request $request)
    {
        $validated = $request->validate([
            'surah_id' => 'required|integer|min:1|max:114',
            'ayah_number' => 'required|integer|min:1',
            'time_spent' => 'integer|min:0|max:3600', // Max 1 hour per ayah
        ]);
        
        $user = auth()->user();
        
        // Update or create progress record
        $progress = QuranProgress::firstOrCreate([
            'user_id' => $user->id,
            'surah_id' => $validated['surah_id'],
            'ayah_number' => $validated['ayah_number'],
        ]);
        
        $progress->recited_count++;
        $progress->last_seen_at = now();
        
        // Calculate hasanat for this ayah
        $letterCount = $this->getAyahLetterCount($validated['surah_id'], $validated['ayah_number']);
        $hasanat = $letterCount * 10;
        $progress->hasanat += $hasanat;
        $progress->save();
        
        // Update user's total hasanat
        $user->increment('hasanat_total', $hasanat);
        
        // Update daily stats
        $today = now()->startOfDay();
        $daily = DailyStats::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['daily_goal' => 10]
        );
        
        $daily->verses_read++;
        $daily->hasanat_earned += $hasanat;
        $daily->time_spent += $validated['time_spent'] ?? 60;
        $daily->streak_days = $this->calculateStreak($user->id);
        $daily->checkGoalAchievement();
        $daily->save();
        
        // Notify teacher if student is in a class
        $this->notifyTeacher($user, $progress);
        
        // Update leaderboard entry
        $this->updateUserLeaderboardEntry($user);
        
        // Clear cache
        Cache::forget("dashboard:{$user->id}:{$today->format('Y-m-d')}");
        
        return response()->json([
            'hasanat_added' => $hasanat,
            'daily_progress' => $daily,
            'goal_achieved' => $daily->goal_achieved,
            'new_badge' => $this->checkForNewBadge($user),
        ]);
    }

    /**
     * Get Ayah of the Day with translation.
     */
    public function getAyahOfDay()
    {
        $seed = now()->format('Ymd');
        
        return Cache::remember("ayah:day:{$seed}", 3600 * 24, function () use ($seed) {
            srand($seed);
            $surahId = rand(1, 114);
            
            try {
                $client = new Client();
                
                // Get surah info to determine ayah count
                $surahResponse = $client->get("https://api.alquran.cloud/v1/surah/{$surahId}/uthmani");
                $surahData = json_decode($surahResponse->getBody(), true)['data'];
                
                $ayahNumber = rand(1, $surahData['numberOfAyahs']);
                
                // Get ayah with translation
                $ayahResponse = $client->get("https://api.alquran.cloud/v1/ayah/{$surahId}:{$ayahNumber}/editions/quran-uthmani,en.sahih");
                $ayahData = json_decode($ayahResponse->getBody(), true)['data'];
                
                return [
                    'surah_id' => $surahId,
                    'ayah_number' => $ayahNumber,
                    'surah_name' => $surahData['englishName'],
                    'surah_name_arabic' => $surahData['name'],
                    'text_arabic' => $ayahData[0]['text'],
                    'text_english' => $ayahData[1]['text'],
                    'reference' => "{$surahData['englishName']} {$ayahNumber}",
                ];
            } catch (\Exception $e) {
                // Fallback ayah
                return [
                    'surah_id' => 1,
                    'ayah_number' => 1,
                    'surah_name' => 'Al-Fatihah',
                    'surah_name_arabic' => 'الفاتحة',
                    'text_arabic' => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
                    'text_english' => 'In the name of Allah, the Entirely Merciful, the Especially Merciful.',
                    'reference' => 'Al-Fatihah 1',
                ];
            }
        });
    }

    /**
     * Get smart daily recommendations.
     */
    public function getRecommendations(Request $request)
    {
        $user = auth()->user();
        
        return Cache::remember("recommendations:{$user->id}:" . now()->format('Y-m-d'), 1800, function () use ($user) {
            $recommendations = [];
            
            // Islamic calendar based recommendations
            $recommendations = array_merge($recommendations, $this->getIslamicCalendarRecommendations());
            
            // User progress based recommendations
            $recommendations = array_merge($recommendations, $this->getUserProgressRecommendations($user));
            
            // Memorization recommendations
            $recommendations = array_merge($recommendations, $this->getMemorizationRecommendations($user));
            
            return array_slice($recommendations, 0, 5); // Limit to 5 recommendations
        });
    }

    /**
     * Get leaderboard data with rankings and community features.
     */
    public function getLeaderboard(Request $request)
    {
        $user = auth()->user();
        $timeframe = $request->get('timeframe', 'all_time'); // all_time, monthly, weekly
        
        $query = LeaderboardEntry::with('user:id,name,avatar')
            ->public()
            ->active();
            
        // Apply timeframe filter
        if ($timeframe === 'monthly') {
            $query->where('last_active', '>=', now()->startOfMonth());
        } elseif ($timeframe === 'weekly') {
            $query->where('last_active', '>=', now()->startOfWeek());
        }
        
        $leaderboard = $query->orderByDesc('hasanat')
            ->orderByDesc('surahs_completed')
            ->take(50)
            ->get();
            
        // Get user's own ranking
        $userEntry = LeaderboardEntry::where('user_id', $user->id)->first();
        $userRank = null;
        
        if ($userEntry && $userEntry->is_public) {
            $userRank = LeaderboardEntry::public()
                ->active()
                ->where('hasanat', '>', $userEntry->hasanat)
                ->orWhere(function($q) use ($userEntry) {
                    $q->where('hasanat', $userEntry->hasanat)
                      ->where('surahs_completed', '>', $userEntry->surahs_completed);
                })
                ->count() + 1;
        }
        
        return response()->json([
            'leaderboard' => $leaderboard->map(function($entry, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'avatar' => $entry->user->avatar,
                    ],
                    'hasanat' => $entry->hasanat,
                    'surahs_completed' => $entry->surahs_completed,
                    'total_score' => $entry->getTotalScore(),
                    'last_active' => $entry->last_active->diffForHumans(),
                ];
            }),
            'user_rank' => $userRank,
            'user_entry' => $userEntry ? [
                'hasanat' => $userEntry->hasanat,
                'surahs_completed' => $userEntry->surahs_completed,
                'total_score' => $userEntry->getTotalScore(),
                'is_public' => $userEntry->is_public,
            ] : null,
            'timeframe' => $timeframe,
        ]);
    }
    
    /**
     * Send leaderboard invite to another user.
     */
    public function sendLeaderboardInvite(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);
        
        $user = auth()->user();
        
        // Check if invite already exists
        $existingInvite = LeaderboardInvite::where('sender_id', $user->id)
            ->where('receiver_id', $validated['receiver_id'])
            ->where('status', 'pending')
            ->first();
            
        if ($existingInvite) {
            return response()->json(['message' => 'Invite already sent'], 409);
        }
        
        // Create new invite
        $invite = LeaderboardInvite::create([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);
        
        return response()->json([
            'message' => 'Leaderboard invite sent successfully',
            'invite' => $invite->load(['sender:id,name', 'receiver:id,name']),
        ]);
    }
    
    /**
     * Get received leaderboard invites.
     */
    public function getLeaderboardInvites(Request $request)
    {
        $user = auth()->user();
        
        $invites = LeaderboardInvite::with(['sender:id,name,avatar'])
            ->where('receiver_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);
            
        return response()->json($invites);
    }
    
    /**
     * Respond to leaderboard invite.
     */
    public function respondToLeaderboardInvite(Request $request, $inviteId)
    {
        $validated = $request->validate([
            'action' => 'required|in:accept,decline',
        ]);
        
        $user = auth()->user();
        $invite = LeaderboardInvite::where('id', $inviteId)
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();
            
        if ($validated['action'] === 'accept') {
            $invite->accept();
            
            // Create or update leaderboard entry for user if accepting
            $this->updateUserLeaderboardEntry($user);
            
            $message = 'Leaderboard invite accepted';
        } else {
            $invite->decline();
            $message = 'Leaderboard invite declined';
        }
        
        return response()->json([
            'message' => $message,
            'invite' => $invite->fresh()->load(['sender:id,name', 'receiver:id,name']),
        ]);
    }
    
    /**
     * Update user's leaderboard preferences.
     */
    public function updateLeaderboardPreferences(Request $request)
    {
        $validated = $request->validate([
            'is_public' => 'required|boolean',
            'show_progress' => 'boolean',
            'allow_invites' => 'boolean',
            'notification_settings' => 'array',
        ]);
        
        $user = auth()->user();
        
        // Update user preferences
        $preferences = array_merge(
            $user->leaderboard_preferences ?? [],
            $validated
        );
        
        $user->update(['leaderboard_preferences' => $preferences]);
        
        // Update or create leaderboard entry
        $this->updateUserLeaderboardEntry($user, $validated['is_public']);
        
        return response()->json([
            'message' => 'Leaderboard preferences updated successfully',
            'preferences' => $preferences,
        ]);
    }
    
    /**
     * Get leaderboard community features and stats.
     */
    public function getLeaderboardCommunity(Request $request)
    {
        $user = auth()->user();
        
        $stats = [
            'total_participants' => LeaderboardEntry::public()->count(),
            'active_this_week' => LeaderboardEntry::public()
                ->where('last_active', '>=', now()->startOfWeek())
                ->count(),
            'top_performer_this_month' => LeaderboardEntry::with('user:id,name')
                ->public()
                ->where('last_active', '>=', now()->startOfMonth())
                ->orderByDesc('hasanat')
                ->first(),
            'user_invites_sent' => LeaderboardInvite::where('sender_id', $user->id)->count(),
            'user_invites_received' => LeaderboardInvite::where('receiver_id', $user->id)
                ->where('status', 'pending')
                ->count(),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Private helper to update user's leaderboard entry.
     */
    private function updateUserLeaderboardEntry(User $user, ?bool $isPublic = null)
    {
        $entry = LeaderboardEntry::firstOrCreate(
            ['user_id' => $user->id],
            [
                'hasanat' => $user->hasanat_total ?? 0,
                'surahs_completed' => $this->getUserSurahsCompleted($user),
                'is_public' => $isPublic ?? ($user->leaderboard_preferences['is_public'] ?? false),
                'last_active' => now(),
            ]
        );
        
        // Update existing entry
        $entry->update([
            'hasanat' => $user->hasanat_total ?? 0,
            'surahs_completed' => $this->getUserSurahsCompleted($user),
            'is_public' => $isPublic ?? $entry->is_public,
            'last_active' => now(),
        ]);
        
        return $entry;
    }
    
    /**
     * Get count of surahs completed by user.
     */
    private function getUserSurahsCompleted(User $user): int
    {
        return QuranProgress::where('user_id', $user->id)
            ->selectRaw('surah_id, COUNT(*) as ayah_count')
            ->groupBy('surah_id')
            ->havingRaw('ayah_count >= (SELECT ayah_count FROM surahs WHERE id = surah_id)')
            ->count();
    }

    /**
     * Get user's weekly progress summary.
     */
    public function getWeeklyProgress(Request $request)
    {
        $user = auth()->user();
        $startOfWeek = now()->startOfWeek();
        
        $weeklyStats = DailyStats::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek, now()])
            ->orderBy('date')
            ->get();
        
        $summary = [
            'total_verses' => $weeklyStats->sum('verses_read'),
            'total_hasanat' => $weeklyStats->sum('hasanat_earned'),
            'total_time' => $weeklyStats->sum('time_spent'),
            'days_active' => $weeklyStats->where('verses_read', '>', 0)->count(),
            'goals_achieved' => $weeklyStats->where('goal_achieved', true)->count(),
            'daily_breakdown' => $weeklyStats->map(function ($stat) {
                return [
                    'date' => $stat->date->format('Y-m-d'),
                    'verses_read' => $stat->verses_read,
                    'hasanat_earned' => $stat->hasanat_earned,
                    'goal_achieved' => $stat->goal_achieved,
                    'progress_percentage' => $stat->getProgressPercentage(),
                ];
            }),
        ];
        
        return response()->json($summary);
    }

    /**
     * Private helper methods
     */
    private function getAyahLetterCount(int $surahId, int $ayahNumber): int
    {
        return Cache::remember("quran:ayah:{$surahId}:{$ayahNumber}:letters", 3600 * 24, function () use ($surahId, $ayahNumber) {
            try {
                $client = new Client();
                $response = $client->get("https://api.alquran.cloud/v1/ayah/{$surahId}:{$ayahNumber}/uthmani");
                $text = json_decode($response->getBody(), true)['data']['text'];
                
                // Count Arabic letters only (excluding diacritics and spaces)
                return mb_strlen(preg_replace('/[^\p{Arabic}]/u', '', $text), 'UTF-8');
            } catch (\Exception $e) {
                return 50; // Default letter count
            }
        });
    }

    private function calculateStreak(int $userId): int
    {
        $days = DailyStats::where('user_id', $userId)
            ->where('verses_read', '>', 0)
            ->orderByDesc('date')
            ->pluck('date')
            ->take(30)
            ->toArray();
        
        $streak = 0;
        $today = now()->startOfDay();
        
        for ($i = 0; $i < 30; $i++) {
            $checkDate = $today->copy()->subDays($i)->format('Y-m-d');
            if (in_array($checkDate, array_map(fn($d) => $d->format('Y-m-d'), $days))) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }

    private function notifyTeacher(User $user, QuranProgress $progress): void
    {
        $classMember = ClassMember::where('user_id', $user->id)->first();
        if ($classMember && $classMember->class && $classMember->class->teacher) {
            Notification::send(
                $classMember->class->teacher,
                new StudentProgressUpdated($user, $progress)
            );
        }
    }

    private function getUserBadges(User $user): array
    {
        $badges = [];
        $hasanat = $user->hasanat_total ?? 0;
        
        if ($hasanat >= 1000000) $badges[] = ['name' => 'Diamond Reciter', 'icon' => '💎'];
        if ($hasanat >= 500000) $badges[] = ['name' => 'Gold Reciter', 'icon' => '🥇'];
        if ($hasanat >= 100000) $badges[] = ['name' => 'Silver Reciter', 'icon' => '🥈'];
        if ($hasanat >= 50000) $badges[] = ['name' => 'Bronze Reciter', 'icon' => '🥉'];
        if ($hasanat >= 10000) $badges[] = ['name' => 'Dedicated Student', 'icon' => '📚'];
        
        return $badges;
    }

    private function checkForNewBadge(User $user): ?array
    {
        // Implementation for checking if user earned a new badge
        return null; // Placeholder
    }

    private function getIslamicCalendarRecommendations(): array
    {
        $recommendations = [];
        $today = now();
        $hijriMonth = $this->getHijriMonth();
        
        // Friday - Surah Al-Kahf
        if ($today->isFriday()) {
            $recommendations[] = [
                'surah_id' => 18,
                'title' => 'Surah Al-Kahf',
                'reason' => 'Blessed reading on Fridays - brings light between two Fridays',
                'priority' => 'high',
                'hasanat_multiplier' => 2.0
            ];
        }
        
        // Ramadan - Surah Al-Baqarah
        if ($hijriMonth === 9) {
            $recommendations[] = [
                'surah_id' => 2,
                'title' => 'Surah Al-Baqarah',
                'reason' => 'The house that recites Al-Baqarah cannot be entered by Satan',
                'priority' => 'high',
                'hasanat_multiplier' => 10.0
            ];
        }
        
        // Dhul Hijjah - Surah Al-Hajj
        if ($hijriMonth === 12) {
            $recommendations[] = [
                'surah_id' => 22,
                'title' => 'Surah Al-Hajj',
                'reason' => 'Perfect for the blessed month of Hajj',
                'priority' => 'high',
                'hasanat_multiplier' => 3.0
            ];
        }
        
        // Night time - Surah Al-Mulk
        if ($today->hour >= 20 || $today->hour <= 5) {
            $recommendations[] = [
                'surah_id' => 67,
                'title' => 'Surah Al-Mulk',
                'reason' => 'Protects from punishment of the grave when read before sleep',
                'priority' => 'medium',
                'hasanat_multiplier' => 1.5
            ];
        }
        
        // Morning time - Surah Yasin
        if ($today->hour >= 6 && $today->hour <= 10) {
            $recommendations[] = [
                'surah_id' => 36,
                'title' => 'Surah Yasin',
                'reason' => 'The heart of the Quran - blessed morning recitation',
                'priority' => 'medium',
                'hasanat_multiplier' => 2.0
            ];
        }
        
        return $recommendations;
    }

    private function getUserProgressRecommendations(User $user): array
    {
        $recommendations = [];
        
        // Get user's recent activity
        $recentProgress = QuranProgress::where('user_id', $user->id)
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->orderBy('last_seen_at', 'desc')
            ->take(5)
            ->get();
        
        // Get user's weakest areas (low confidence)
        $weakAreas = QuranProgress::where('user_id', $user->id)
            ->where('memorized_confidence', '<', 0.5)
            ->orderBy('memorized_confidence')
            ->take(3)
            ->get();
        
        // Recommend continuation of recent surahs
        if ($recentProgress->isNotEmpty()) {
            $lastSurah = $recentProgress->first();
            $recommendations[] = [
                'surah_id' => $lastSurah->surah_id,
                'title' => 'Continue Your Journey',
                'reason' => 'Build upon your recent progress in this surah',
                'priority' => 'high',
                'hasanat_multiplier' => 1.2
            ];
        }
        
        // Recommend improvement for weak areas
        foreach ($weakAreas as $weak) {
            $recommendations[] = [
                'surah_id' => $weak->surah_id,
                'title' => 'Strengthen Your Foundation',
                'reason' => 'Improve your confidence in this surah',
                'priority' => 'medium',
                'hasanat_multiplier' => 1.5
            ];
        }
        
        // Progressive difficulty recommendations
        $userLevel = $this->getUserLevel($user);
        if ($userLevel === 'beginner') {
            $recommendations[] = [
                'surah_id' => 114, // An-Nas
                'title' => 'Perfect for Beginners',
                'reason' => 'Short and easy to memorize',
                'priority' => 'medium'
            ];
        } elseif ($userLevel === 'intermediate') {
            $recommendations[] = [
                'surah_id' => 55, // Ar-Rahman
                'title' => 'Challenge Yourself',
                'reason' => 'Beautiful rhythm perfect for intermediate level',
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }

    private function getMemorizationRecommendations(User $user): array
    {
        $recommendations = [];
        $userStats = DailyStats::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();
        
        $averageDaily = $userStats->avg('verses_read') ?? 0;
        
        // Adaptive recommendations based on user's capacity
        if ($averageDaily < 5) {
            // Beginner level - very short surahs
            $surahs = [114, 113, 112, 111, 110]; // Last 5 surahs
            $recommendations[] = [
                'surah_id' => $surahs[array_rand($surahs)],
                'title' => 'Start Small, Dream Big',
                'reason' => 'Perfect bite-sized surah for building consistency',
                'priority' => 'high'
            ];
        } elseif ($averageDaily < 15) {
            // Intermediate level - medium surahs
            $surahs = [109, 108, 107, 106, 105]; // Medium short surahs
            $recommendations[] = [
                'surah_id' => $surahs[array_rand($surahs)],
                'title' => 'Level Up Your Practice',
                'reason' => 'Ready for slightly longer surahs',
                'priority' => 'medium'
            ];
        } else {
            // Advanced level - longer surahs
            $surahs = [18, 36, 55, 67, 78]; // Longer popular surahs
            $recommendations[] = [
                'surah_id' => $surahs[array_rand($surahs)],
                'title' => 'Master the Classics',
                'reason' => 'Challenge yourself with beloved longer surahs',
                'priority' => 'medium'
            ];
        }
        
        // Special themed recommendations
        $dayOfWeek = now()->dayOfWeek;
        $themedSurahs = [
            0 => [1, 'Al-Fatiha', 'Sunday - The Opening'], // Sunday
            1 => [2, 'Al-Baqarah', 'Monday - The Foundation'], // Monday
            2 => [3, 'Al-Imran', 'Tuesday - The Family'], // Tuesday
            3 => [4, 'An-Nisa', 'Wednesday - The Women'], // Wednesday
            4 => [5, 'Al-Maidah', 'Thursday - The Table'], // Thursday
            5 => [18, 'Al-Kahf', 'Friday - The Cave'], // Friday
            6 => [36, 'Yasin', 'Saturday - The Heart'] // Saturday
        ];
        
        if (isset($themedSurahs[$dayOfWeek])) {
            $themed = $themedSurahs[$dayOfWeek];
            $recommendations[] = [
                'surah_id' => $themed[0],
                'title' => $themed[2],
                'reason' => 'Weekly themed recitation for spiritual growth',
                'priority' => 'low'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Create a new memorization plan.
     */
    public function createPlan(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'surahs' => 'required|array|min:1',
            'surahs.*' => 'integer|min:1|max:114',
            'daily_target' => 'required|integer|min:1|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'is_teacher_visible' => 'boolean'
        ]);

        $user = auth()->user();

        $plan = MemorizationPlan::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'surahs' => $validated['surahs'],
            'daily_target' => $validated['daily_target'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_teacher_visible' => $validated['is_teacher_visible'] ?? true,
            'status' => 'active'
        ]);

        // Initialize SRS queue for the plan
        $this->initializeSrsQueue($plan);

        return response()->json([
            'message' => 'Memorization plan created successfully',
            'plan' => $plan->load('srsQueues')
        ], 201);
    }

    /**
     * Submit a review for an ayah with SRS algorithm and Whisper Tajweed analysis.
     */
    public function reviewAyah(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:memorization_plans,id',
            'surah_id' => 'required|integer|min:1|max:114',
            'ayah_id' => 'required|integer|min:1',
            'confidence_score' => 'required|numeric|min:0|max:1',
            'time_spent' => 'nullable|integer|min:1',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:10240' // 10MB max
        ]);

        $user = auth()->user();
        $tajweedAnalysis = null;
        $audioPath = null;

        // Process audio file if provided
        if ($request->hasFile('audio_file')) {
            $audioFile = $request->file('audio_file');
            $audioPath = $audioFile->store('memorization-audio/' . $user->id, 's3');
            
            // Analyze audio with Whisper Tajweed service
            try {
                $whisperService = new WhisperTajweedService();
                $tajweedAnalysis = $whisperService->analyzeRecitation(
                    Storage::disk('s3')->url($audioPath),
                    $validated['surah_id'],
                    $validated['ayah_id']
                );
                
                // Adjust confidence score based on Whisper analysis
                if ($tajweedAnalysis && isset($tajweedAnalysis['tajweed_score'])) {
                    $whisperConfidence = $tajweedAnalysis['tajweed_score'] / 100; // Convert to 0-1 scale
                    $validated['confidence_score'] = ($validated['confidence_score'] + $whisperConfidence) / 2;
                }
            } catch (\Exception $e) {
                \Log::warning('Whisper Tajweed analysis failed: ' . $e->getMessage());
            }
        }
        
        // Find or create SRS queue entry
        $srsEntry = SrsQueue::firstOrCreate([
            'user_id' => $user->id,
            'plan_id' => $validated['plan_id'],
            'surah_id' => $validated['surah_id'],
            'ayah_id' => $validated['ayah_id']
        ], [
            'ease_factor' => 2.5,
            'interval' => 1,
            'repetitions' => 0,
            'confidence_score' => 0,
            'due_at' => now()
        ]);

        // Update SRS algorithm based on confidence
        $srsEntry->updateSM2Algorithm($validated['confidence_score']);
        $srsEntry->confidence_score = $validated['confidence_score'];
        $srsEntry->last_reviewed_at = now();
        $srsEntry->save();

        // Update daily stats
        $today = now()->startOfDay();
        $daily = DailyStats::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['daily_goal' => 10]
        );
        
        $daily->verses_read++;
        $daily->time_spent += $validated['time_spent'] ?? 60;
        $daily->save();

        // Calculate hasanat for memorization review
        $hasanat = $this->calculateMemorizationHasanat($validated['confidence_score']);
        $user->increment('hasanat_total', $hasanat);
        $daily->increment('hasanat_earned', $hasanat);

        // Send notification to teachers if audio was provided
        if ($audioPath) {
            try {
                $notificationService = new NotificationService();
                $notificationService->notifyTeachersOfCompletedReview(
                    $user,
                    $validated['surah_id'],
                    $validated['ayah_id'],
                    $validated['confidence_score'],
                    $audioPath,
                    $tajweedAnalysis
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to send review notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Review completed successfully',
            'hasanat_earned' => $hasanat,
            'next_review' => $srsEntry->due_at,
            'mastery_level' => $srsEntry->getConfidencePercentage(),
            'audio_path' => $audioPath,
            'tajweed_analysis' => $tajweedAnalysis
        ]);
    }

    /**
     * Get due reviews for memorization.
     */
    public function getDueReviews(Request $request)
    {
        $user = auth()->user();
        $planId = $request->query('plan_id');
        
        $query = SrsQueue::where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->orderBy('due_at');
            
        if ($planId) {
            $query->where('plan_id', $planId);
        }
        
        $dueReviews = $query->with('plan')->get();
        
        $reviewsData = $dueReviews->map(function ($review) {
            return [
                'id' => $review->id,
                'plan_title' => $review->plan->title,
                'surah_id' => $review->surah_id,
                'ayah_id' => $review->ayah_id,
                'confidence_score' => $review->confidence_score,
                'repetitions' => $review->repetitions,
                'due_at' => $review->due_at,
                'overdue_hours' => max(0, now()->diffInHours($review->due_at, false))
            ];
        });
        
        return response()->json([
            'due_reviews' => $reviewsData,
            'total_due' => $dueReviews->count(),
            'overdue_count' => $dueReviews->where('due_at', '<', now()->subHours(1))->count()
        ]);
    }

    /**
     * Initialize SRS queue for a memorization plan.
     */
    private function initializeSrsQueue(MemorizationPlan $plan): void
    {
        foreach ($plan->surahs as $surahId) {
            // Get ayah count for the surah (simplified - in production use proper Quran API)
            $ayahCount = $this->getSurahAyahCount($surahId);
            
            for ($ayahId = 1; $ayahId <= min($ayahCount, $plan->daily_target); $ayahId++) {
                SrsQueue::create([
                    'user_id' => $plan->user_id,
                    'plan_id' => $plan->id,
                    'surah_id' => $surahId,
                    'ayah_id' => $ayahId,
                    'ease_factor' => 2.5,
                    'interval' => 1,
                    'repetitions' => 0,
                    'confidence_score' => 0,
                    'due_at' => $plan->start_date
                ]);
            }
        }
    }

    /**
     * Calculate hasanat for memorization review based on confidence.
     */
    private function calculateMemorizationHasanat(float $confidence): int
    {
        $baseHasanat = 100; // Base hasanat for memorization review
        $confidenceMultiplier = 1 + ($confidence * 2); // 1x to 3x multiplier
        
        return (int) ($baseHasanat * $confidenceMultiplier);
    }

    /**
     * Get ayah count for a surah (simplified implementation).
     */
    private function getSurahAyahCount(int $surahId): int
    {
        // Simplified ayah counts - in production, use proper Quran database
        $ayahCounts = [
            1 => 7, 2 => 286, 3 => 200, 4 => 176, 5 => 120,
            // Add more as needed or use API call
        ];
        
        return $ayahCounts[$surahId] ?? 10; // Default to 10 if not found
    }

    /**
     * Get memorization students for teacher oversight
     */
    public function getMemorizationStudents(Request $request)
    {
        $user = auth()->user();
        
        // Get students from teacher's classes
        $students = User::whereHas('classMemberships', function ($query) use ($user) {
            $query->whereHas('class', function ($classQuery) use ($user) {
                $classQuery->where('teacher_id', $user->id);
            });
        })
        ->with(['memorizationPlans' => function ($query) {
            $query->latest()->limit(3);
        }])
        ->get()
        ->map(function ($student) {
            $memorizationStats = $this->getStudentMemorizationStats($student->id);
            $alerts = $this->getStudentMemorizationAlerts($student->id);
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'avatar' => $student->avatar,
                'memorization_stats' => $memorizationStats,
                'recent_plans' => $student->memorizationPlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'title' => $plan->title,
                        'surahs' => $plan->surahs,
                        'progress_percentage' => $this->calculatePlanProgress($plan),
                        'status' => $plan->status,
                        'created_at' => $plan->created_at->toISOString(),
                    ];
                }),
                'alerts' => $alerts,
                'performance_trend' => $this->getPerformanceTrend($student->id),
            ];
        });

        return response()->json($students);
    }

    /**
     * Get memorization analytics for teacher dashboard
     */
    public function getMemorizationAnalytics(Request $request)
    {
        $user = auth()->user();
        
        // Get students from teacher's classes
        $studentIds = User::whereHas('classMemberships', function ($query) use ($user) {
            $query->whereHas('class', function ($classQuery) use ($user) {
                $classQuery->where('teacher_id', $user->id);
            });
        })->pluck('id');

        $totalStudents = $studentIds->count();
        $activeMemorizers = MemorizationPlan::whereIn('user_id', $studentIds)
            ->where('status', 'active')
            ->distinct('user_id')
            ->count();

        $totalAyahsMemorized = SrsQueue::whereIn('user_id', $studentIds)
            ->where('repetitions', '>', 0)
            ->count();

        $averageConfidence = SrsQueue::whereIn('user_id', $studentIds)
            ->whereNotNull('confidence_score')
            ->avg('confidence_score') ?? 0;

        $completionRate = $totalStudents > 0 
            ? round(($activeMemorizers / $totalStudents) * 100, 1)
            : 0;

        $averageProgress = MemorizationPlan::whereIn('user_id', $studentIds)
            ->get()
            ->avg(function ($plan) {
                return $this->calculatePlanProgress($plan);
            }) ?? 0;

        $weeklyReviews = SrsQueue::whereIn('user_id', $studentIds)
            ->where('last_reviewed_at', '>=', now()->subWeek())
            ->count();

        $monthlyCompletions = MemorizationPlan::whereIn('user_id', $studentIds)
            ->where('status', 'completed')
            ->where('updated_at', '>=', now()->subMonth())
            ->count();

        return response()->json([
            'total_students' => $totalStudents,
            'active_memorizers' => $activeMemorizers,
            'average_progress' => round($averageProgress, 1),
            'total_ayahs_memorized' => $totalAyahsMemorized,
            'average_confidence' => round($averageConfidence * 100, 1),
            'completion_rate' => $completionRate,
            'weekly_reviews' => $weeklyReviews,
            'monthly_completions' => $monthlyCompletions,
        ]);
    }

    /**
     * Get audio reviews for teacher oversight
     */
    public function getAudioReviews(Request $request)
    {
        $user = auth()->user();
        
        // Get students from teacher's classes
        $studentIds = User::whereHas('classMemberships', function ($query) use ($user) {
            $query->whereHas('class', function ($classQuery) use ($user) {
                $classQuery->where('teacher_id', $user->id);
            });
        })->pluck('id');

        // Get audio submissions that need review
        $audioReviews = SrsQueue::whereIn('user_id', $studentIds)
            ->whereNotNull('audio_path')
            ->with(['user'])
            ->latest('last_reviewed_at')
            ->limit(50)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'student_name' => $review->user->name,
                    'surah_id' => $review->surah_id,
                    'ayah_id' => $review->ayah_id,
                    'audio_url' => Storage::disk('s3')->url($review->audio_path),
                    'confidence_score' => $review->confidence_score,
                    'submitted_at' => $review->last_reviewed_at->toISOString(),
                    'status' => $review->review_status ?? 'pending',
                    'teacher_feedback' => $review->teacher_feedback,
                ];
            });

        return response()->json($audioReviews);
    }

    /**
     * Approve or reject audio review
     */
    public function reviewAudioSubmission(Request $request, $reviewId, $action)
    {
        $request->validate([
            'feedback' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        
        // Get students from teacher's classes
        $studentIds = User::whereHas('classMemberships', function ($query) use ($user) {
            $query->whereHas('class', function ($classQuery) use ($user) {
                $classQuery->where('teacher_id', $user->id);
            });
        })->pluck('id');

        $review = SrsQueue::whereIn('user_id', $studentIds)
            ->where('id', $reviewId)
            ->firstOrFail();

        $status = $action === 'approve' ? 'approved' : 'reviewed';
        $review->update([
            'review_status' => $status,
            'teacher_feedback' => $request->feedback,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Review {$action}d successfully",
        ]);
    }

    /**
     * Helper methods for teacher oversight
     */
    private function getStudentMemorizationStats(int $studentId): array
    {
        $activePlans = MemorizationPlan::where('user_id', $studentId)
            ->where('status', 'active')
            ->count();
            
        $completedPlans = MemorizationPlan::where('user_id', $studentId)
            ->where('status', 'completed')
            ->count();
            
        $totalReviews = SrsQueue::where('user_id', $studentId)
            ->where('repetitions', '>', 0)
            ->count();
            
        $averageConfidence = SrsQueue::where('user_id', $studentId)
            ->avg('confidence_score') ?? 0;
            
        return [
            'active_plans' => $activePlans,
            'completed_plans' => $completedPlans,
            'total_reviews' => $totalReviews,
            'average_confidence' => round($averageConfidence * 100, 1),
        ];
    }

    private function getStudentMemorizationAlerts(int $studentId): array
    {
        $alerts = [];
        
        // Check for overdue reviews
        $overdueCount = SrsQueue::where('user_id', $studentId)
            ->where('due_at', '<', now()->subHours(24))
            ->count();
            
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'overdue',
                'message' => "{$overdueCount} reviews overdue",
                'severity' => 'high'
            ];
        }
        
        // Check for low confidence scores
        $lowConfidenceCount = SrsQueue::where('user_id', $studentId)
            ->where('confidence_score', '<', 0.5)
            ->count();
            
        if ($lowConfidenceCount > 5) {
            $alerts[] = [
                'type' => 'low_confidence',
                'message' => 'Multiple ayahs with low confidence',
                'severity' => 'medium'
            ];
        }
        
        return $alerts;
    }

    private function getPerformanceTrend(int $studentId): string
    {
        $recentReviews = SrsQueue::where('user_id', $studentId)
            ->where('last_reviewed_at', '>=', now()->subWeeks(2))
            ->orderBy('last_reviewed_at')
            ->pluck('confidence_score')
            ->toArray();
            
        if (count($recentReviews) < 3) {
            return 'insufficient_data';
        }
        
        $firstHalf = array_slice($recentReviews, 0, count($recentReviews) / 2);
        $secondHalf = array_slice($recentReviews, count($recentReviews) / 2);
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        if ($secondAvg > $firstAvg + 0.1) {
            return 'improving';
        } elseif ($secondAvg < $firstAvg - 0.1) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    private function calculatePlanProgress(MemorizationPlan $plan): float
    {
        $totalAyahs = 0;
        foreach ($plan->surahs as $surahId) {
            $totalAyahs += $this->getSurahAyahCount($surahId);
        }
        
        $completedAyahs = SrsQueue::where('user_id', $plan->user_id)
            ->where('plan_id', $plan->id)
            ->where('confidence_score', '>=', 0.8)
            ->count();
            
        return $totalAyahs > 0 ? ($completedAyahs / $totalAyahs) * 100 : 0;
    }
}