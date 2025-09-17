<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard rankings based on hasanat scores.
     * Supports class-specific and global leaderboards with time period filtering.
     *
     * @param Request $request HTTP request with query parameters
     * @return \Illuminate\Http\JsonResponse Leaderboard data
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'scope' => 'sometimes|in:class,global',
            'period' => 'sometimes|in:weekly,monthly,all_time',
            'class_id' => 'sometimes|exists:classes,id',
            'limit' => 'sometimes|integer|min:5|max:100'
        ]);

        $scope = $validated['scope'] ?? 'global';
        $period = $validated['period'] ?? 'weekly';
        $classId = $validated['class_id'] ?? null;
        $limit = $validated['limit'] ?? 50;

        // Determine date range based on period
        $dateFilter = $this->getDateFilter($period);

        // Base query for hasanat aggregation
        $hasanatQuery = DB::table('quran_progress')
            ->select('user_id', DB::raw('SUM(hasanat) as total_hasanat'))
            ->when($dateFilter, function ($query, $dateFilter) {
                return $query->where('updated_at', '>=', $dateFilter);
            })
            ->groupBy('user_id');

        // Build user query based on scope
        $userQuery = User::select([
            'users.id',
            'users.name',
            'users.email',
            'users.created_at'
        ])
        ->joinSub($hasanatQuery, 'hasanat_totals', function ($join) {
            $join->on('users.id', '=', 'hasanat_totals.user_id');
        })
        ->addSelect('hasanat_totals.total_hasanat')
        ->where('users.id', '!=', $user->id); // Exclude current user from main list

        // Apply scope filtering
        if ($scope === 'class' && $classId) {
            // Verify user has access to this class
            $hasAccess = false;
            if ($user->hasRole('teacher')) {
                $hasAccess = $user->teachingClasses()->where('classes.id', $classId)->exists();
            } else {
                $hasAccess = $user->enrolledClasses()->where('classes.id', $classId)->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'You do not have access to this class leaderboard'
                ], 403);
            }

            $userQuery->whereHas('enrolledClasses', function ($query) use ($classId) {
                $query->where('classes.id', $classId);
            });
        } elseif ($scope === 'class' && !$classId) {
            // If class scope but no class_id, use user's first enrolled class
            $firstClass = $user->enrolledClasses()->first();
            if (!$firstClass) {
                return response()->json([
                    'error' => 'No class found',
                    'message' => 'You are not enrolled in any classes'
                ], 404);
            }
            $classId = $firstClass->id;
            $userQuery->whereHas('enrolledClasses', function ($query) use ($classId) {
                $query->where('classes.id', $classId);
            });
        }

        // Get ranked users
        $rankedUsers = $userQuery
            ->orderBy('hasanat_totals.total_hasanat', 'desc')
            ->orderBy('users.created_at', 'asc') // Tie-breaker: earlier registration wins
            ->limit($limit)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'total_hasanat' => (int) $user->total_hasanat,
                    'badge' => $this->getBadge($index + 1)
                ];
            });

        // Get current user's position and stats
        $currentUserStats = $this->getCurrentUserStats($user, $dateFilter, $scope, $classId);

        // Get class info if applicable
        $classInfo = null;
        if ($scope === 'class' && $classId) {
            $classInfo = DB::table('classes')
                ->select('id', 'title', 'level')
                ->where('id', $classId)
                ->first();
        }

        return response()->json([
            'leaderboard' => $rankedUsers,
            'current_user' => $currentUserStats,
            'metadata' => [
                'scope' => $scope,
                'period' => $period,
                'class' => $classInfo,
                'total_participants' => $rankedUsers->count(),
                'period_label' => $this->getPeriodLabel($period)
            ]
        ]);
    }

    /**
     * Get date filter based on period selection.
     *
     * @param string $period The time period (weekly, monthly, all_time)
     * @return \Carbon\Carbon|null Date filter or null for all_time
     */
    private function getDateFilter(string $period)
    {
        switch ($period) {
            case 'weekly':
                return now()->subWeek();
            case 'monthly':
                return now()->subMonth();
            case 'all_time':
            default:
                return null;
        }
    }

    /**
     * Get current user's leaderboard statistics.
     *
     * @param User $user The authenticated user
     * @param \Carbon\Carbon|null $dateFilter Date filter for period
     * @param string $scope Leaderboard scope (class/global)
     * @param int|null $classId Class ID if applicable
     * @return array User statistics
     */
    private function getCurrentUserStats(User $user, $dateFilter, string $scope, $classId = null)
    {
        // Get user's hasanat for the period
        $userHasanat = DB::table('quran_progress')
            ->where('user_id', $user->id)
            ->when($dateFilter, function ($query, $dateFilter) {
                return $query->where('updated_at', '>=', $dateFilter);
            })
            ->sum('hasanat');

        // Get user's rank
        $rankQuery = DB::table('quran_progress')
            ->select('user_id', DB::raw('SUM(hasanat) as total_hasanat'))
            ->when($dateFilter, function ($query, $dateFilter) {
                return $query->where('updated_at', '>=', $dateFilter);
            })
            ->groupBy('user_id')
            ->having('total_hasanat', '>', $userHasanat);

        if ($scope === 'class' && $classId) {
            $rankQuery->whereIn('user_id', function ($query) use ($classId) {
                $query->select('user_id')
                    ->from('class_members')
                    ->where('class_id', $classId);
            });
        }

        $rank = $rankQuery->count() + 1;

        return [
            'rank' => $rank,
            'total_hasanat' => (int) $userHasanat,
            'badge' => $this->getBadge($rank),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ];
    }

    /**
     * Get badge/medal based on rank position.
     *
     * @param int $rank User's rank position
     * @return string|null Badge identifier
     */
    private function getBadge(int $rank): ?string
    {
        switch ($rank) {
            case 1:
                return 'gold';
            case 2:
                return 'silver';
            case 3:
                return 'bronze';
            default:
                return $rank <= 10 ? 'top_10' : null;
        }
    }

    /**
     * Get human-readable period label.
     *
     * @param string $period The time period
     * @return string Period label
     */
    private function getPeriodLabel(string $period): string
    {
        switch ($period) {
            case 'weekly':
                return 'This Week';
            case 'monthly':
                return 'This Month';
            case 'all_time':
            default:
                return 'All Time';
        }
    }
}