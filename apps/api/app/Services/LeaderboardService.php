<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use App\Models\GamificationEvent;
use App\Models\LeaderboardSnapshot;
use Illuminate\Support\Carbon;

/**
 * LeaderboardService aggregates points into weekly/monthly snapshots.
 */
class LeaderboardService
{
    /**
     * build aggregates points for a period ("weekly"|"monthly") and returns a payload.
     * @param string $period
     * @return array
     */
    public function build(string $period): array
    {
        $now = now();
        $start = $period === 'monthly'
            ? $now->copy()->startOfMonth()
            : $now->copy()->startOfWeek();

        $events = GamificationEvent::query()
            ->where('created_at', '>=', $start)
            ->get(['user_id','points']);

        $totals = [];
        foreach ($events as $e) {
            $totals[$e->user_id] = ($totals[$e->user_id] ?? 0) + (int)$e->points;
        }
        arsort($totals);

        $top = [];
        foreach (array_slice($totals, 0, 100, true) as $userId=>$pts) {
            $top[] = ['user_id'=>$userId, 'points'=>$pts];
        }

        return [
            'period' => $period,
            'generated_at' => $now->toIso8601String(),
            'start' => $start->toIso8601String(),
            'top' => $top,
        ];
    }

    /**
     * persist saves/updates the snapshot row and returns it.
     * @param string $period
     * @param array $data
     * @return \App\Models\LeaderboardSnapshot
     */
    public function persist(string $period, array $data): LeaderboardSnapshot
    {
        return LeaderboardSnapshot::updateOrCreate(
            ['scope'=>'global','period'=>$period],
            ['data_json'=>$data, 'generated_at'=>now()]
        );
    }
}