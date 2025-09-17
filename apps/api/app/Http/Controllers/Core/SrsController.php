<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\SrsQueue;
use App\Models\QuranProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SrsController extends Controller
{
    /**
     * Get items due for review for the authenticated user.
     *
     * @param Request $request HTTP request with optional filters
     * @return JsonResponse Items due for review
     */
    public function getDueItems(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
            'surah_id' => 'integer|min:1|max:114',
            'difficulty' => Rule::in(['easy', 'medium', 'hard', 'very_hard'])
        ]);

        $query = SrsQueue::where('user_id', Auth::id())
            ->due()
            ->byPriority()
            ->with(['user', 'quranProgress']);

        // Apply filters
        if ($request->has('surah_id')) {
            $query->bySurah($request->surah_id);
        }

        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        $limit = $request->get('limit', 20);
        $items = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'surah_id' => $item->surah_id,
                    'ayah_id' => $item->ayah_id,
                    'ayah_identifier' => $item->getAyahIdentifier(),
                    'interval_days' => $item->interval_days,
                    'ease_factor' => $item->ease_factor,
                    'repetitions' => $item->repetitions,
                    'next_review_at' => $item->next_review_at,
                    'days_until_review' => $item->getDaysUntilReview(),
                    'priority_score' => $item->getPriorityScore(),
                    'is_due' => $item->isDue(),
                    'is_overdue' => $item->isOverdue(),
                    'quran_progress' => $item->quranProgress ? [
                        'memorized_confidence' => $item->quranProgress->memorized_confidence,
                        'recited_count' => $item->quranProgress->recited_count,
                        'hasanat' => $item->quranProgress->hasanat
                    ] : null
                ];
            }),
            'meta' => [
                'total_due' => SrsQueue::where('user_id', Auth::id())->due()->count(),
                'total_overdue' => SrsQueue::where('user_id', Auth::id())
                    ->where('next_review_at', '<', now()->subDay())->count()
            ]
        ]);
    }

    /**
     * Get review statistics for the authenticated user.
     *
     * @return JsonResponse Review statistics
     */
    public function getStats(): JsonResponse
    {
        $userId = Auth::id();
        
        $stats = [
            'total_items' => SrsQueue::where('user_id', $userId)->count(),
            'due_today' => SrsQueue::where('user_id', $userId)->due()->count(),
            'overdue' => SrsQueue::where('user_id', $userId)
                ->where('next_review_at', '<', now()->subDay())->count(),
            'completed_today' => SrsQueue::where('user_id', $userId)
                ->whereDate('updated_at', today())
                ->where('repetitions', '>', 0)->count(),
            'streak_days' => $this->calculateStreakDays($userId),
            'difficulty_breakdown' => [
                'easy' => SrsQueue::where('user_id', $userId)->byDifficulty('easy')->count(),
                'medium' => SrsQueue::where('user_id', $userId)->byDifficulty('medium')->count(),
                'hard' => SrsQueue::where('user_id', $userId)->byDifficulty('hard')->count(),
                'very_hard' => SrsQueue::where('user_id', $userId)->byDifficulty('very_hard')->count()
            ],
            'upcoming_reviews' => [
                'tomorrow' => SrsQueue::where('user_id', $userId)
                    ->whereDate('next_review_at', tomorrow())->count(),
                'this_week' => SrsQueue::where('user_id', $userId)
                    ->whereBetween('next_review_at', [now(), now()->endOfWeek()])->count(),
                'next_week' => SrsQueue::where('user_id', $userId)
                    ->whereBetween('next_review_at', [now()->startOfWeek()->addWeek(), now()->endOfWeek()->addWeek()])->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Submit a review for an SRS item.
     *
     * @param Request $request HTTP request with review data
     * @param int $id SRS queue item ID
     * @return JsonResponse Review submission result
     */
    public function submitReview(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quality' => 'required|integer|min:0|max:5',
            'time_taken' => 'integer|min:1', // seconds
            'notes' => 'string|max:500'
        ]);

        $srsItem = SrsQueue::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$srsItem->isDue()) {
            return response()->json([
                'success' => false,
                'message' => 'This item is not due for review yet'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update SRS parameters based on quality
            $srsItem->updateSrsParameters($request->quality);

            // Update Quran progress if exists
            $quranProgress = QuranProgress::where('user_id', Auth::id())
                ->where('surah_id', $srsItem->surah_id)
                ->where('ayah_id', $srsItem->ayah_id)
                ->first();

            if ($quranProgress) {
                $quranProgress->increment('recited_count');
                $quranProgress->last_seen_at = now();
                
                // Update confidence based on review quality
                if ($request->quality >= 4) {
                    $quranProgress->memorized_confidence = min(1.0, $quranProgress->memorized_confidence + 0.1);
                } elseif ($request->quality <= 2) {
                    $quranProgress->memorized_confidence = max(0.0, $quranProgress->memorized_confidence - 0.2);
                }
                
                $quranProgress->save();
            }

            // Log the review for analytics
            Log::info('SRS review submitted', [
                'user_id' => Auth::id(),
                'srs_item_id' => $id,
                'surah_id' => $srsItem->surah_id,
                'ayah_id' => $srsItem->ayah_id,
                'quality' => $request->quality,
                'time_taken' => $request->get('time_taken'),
                'new_interval' => $srsItem->interval_days,
                'new_ease_factor' => $srsItem->ease_factor
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => [
                    'next_review_at' => $srsItem->next_review_at,
                    'interval_days' => $srsItem->interval_days,
                    'ease_factor' => $srsItem->ease_factor,
                    'repetitions' => $srsItem->repetitions
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SRS review submission failed', [
                'user_id' => Auth::id(),
                'srs_item_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review'
            ], 500);
        }
    }

    /**
     * Add a new ayah to the SRS queue.
     *
     * @param Request $request HTTP request with ayah data
     * @return JsonResponse Addition result
     */
    public function addToQueue(Request $request): JsonResponse
    {
        $request->validate([
            'surah_id' => 'required|integer|min:1|max:114',
            'ayah_id' => 'required|integer|min:1'
        ]);

        $userId = Auth::id();
        
        // Check if already exists
        $existing = SrsQueue::where('user_id', $userId)
            ->where('surah_id', $request->surah_id)
            ->where('ayah_id', $request->ayah_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This ayah is already in your review queue'
            ], 422);
        }

        $srsItem = SrsQueue::create([
            'user_id' => $userId,
            'surah_id' => $request->surah_id,
            'ayah_id' => $request->ayah_id,
            'interval_days' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'next_review_at' => now()->addDay()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ayah added to review queue successfully',
            'data' => [
                'id' => $srsItem->id,
                'ayah_identifier' => $srsItem->getAyahIdentifier(),
                'next_review_at' => $srsItem->next_review_at
            ]
        ]);
    }

    /**
     * Remove an ayah from the SRS queue.
     *
     * @param int $id SRS queue item ID
     * @return JsonResponse Removal result
     */
    public function removeFromQueue(int $id): JsonResponse
    {
        $srsItem = SrsQueue::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $srsItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ayah removed from review queue successfully'
        ]);
    }

    /**
     * Reset an SRS item to initial parameters.
     *
     * @param int $id SRS queue item ID
     * @return JsonResponse Reset result
     */
    public function resetItem(int $id): JsonResponse
    {
        $srsItem = SrsQueue::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $srsItem->reset();

        return response()->json([
            'success' => true,
            'message' => 'SRS item reset successfully',
            'data' => [
                'interval_days' => $srsItem->interval_days,
                'ease_factor' => $srsItem->ease_factor,
                'repetitions' => $srsItem->repetitions,
                'next_review_at' => $srsItem->next_review_at
            ]
        ]);
    }

    /**
     * Calculate streak days for a user.
     *
     * @param int $userId User ID
     * @return int Number of consecutive days with reviews
     */
    private function calculateStreakDays(int $userId): int
    {
        $streak = 0;
        $currentDate = today();
        
        while (true) {
            $hasReview = SrsQueue::where('user_id', $userId)
                ->whereDate('updated_at', $currentDate)
                ->where('repetitions', '>', 0)
                ->exists();
                
            if (!$hasReview) {
                break;
            }
            
            $streak++;
            $currentDate = $currentDate->subDay();
            
            // Prevent infinite loop
            if ($streak > 365) {
                break;
            }
        }
        
        return $streak;
    }
}