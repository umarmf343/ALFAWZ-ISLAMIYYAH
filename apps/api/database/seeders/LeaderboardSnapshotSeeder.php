<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\LeaderboardService;
use Carbon\Carbon;

class LeaderboardSnapshotSeeder extends Seeder
{
    /**
     * Seed leaderboard snapshots for testing and development.
     * Creates weekly and monthly snapshots for the past few periods.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Creating leaderboard snapshots...');

        $leaderboardService = new LeaderboardService();

        // Generate weekly snapshots for the past 8 weeks
        $this->command->info('Generating weekly snapshots...');
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $this->command->info("Creating weekly snapshot for week of {$weekStart->format('M d, Y')}");
            
            // Build and persist weekly snapshot
            $weeklyData = $leaderboardService->build('weekly', $weekStart, $weekEnd);
            $leaderboardService->persist('weekly', $weekStart, $weekEnd, $weeklyData);
        }

        // Generate monthly snapshots for the past 6 months
        $this->command->info('Generating monthly snapshots...');
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $this->command->info("Creating monthly snapshot for {$monthStart->format('F Y')}");
            
            // Build and persist monthly snapshot
            $monthlyData = $leaderboardService->build('monthly', $monthStart, $monthEnd);
            $leaderboardService->persist('monthly', $monthStart, $monthEnd, $monthlyData);
        }

        // Generate current week snapshot (partial)
        $this->command->info('Generating current week snapshot...');
        $currentWeekStart = Carbon::now()->startOfWeek();
        $currentWeekEnd = Carbon::now(); // Current time, not end of week
        
        $currentWeeklyData = $leaderboardService->build('weekly', $currentWeekStart, $currentWeekEnd);
        $leaderboardService->persist('weekly', $currentWeekStart, $currentWeekEnd, $currentWeeklyData);

        // Generate current month snapshot (partial)
        $this->command->info('Generating current month snapshot...');
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now(); // Current time, not end of month
        
        $currentMonthlyData = $leaderboardService->build('monthly', $currentMonthStart, $currentMonthEnd);
        $leaderboardService->persist('monthly', $currentMonthStart, $currentMonthEnd, $currentMonthlyData);

        $this->command->info('Leaderboard snapshot seeding completed!');
        $this->command->info('Summary:');
        $this->command->info('- Weekly snapshots: 9 (8 complete weeks + current partial week)');
        $this->command->info('- Monthly snapshots: 7 (6 complete months + current partial month)');
    }
}