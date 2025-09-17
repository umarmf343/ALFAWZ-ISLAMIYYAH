<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TeacherAnalyticsSeeder extends Seeder
{
    /**
     * Seed the teacher_analytics table with sample data.
     * Creates analytics records for existing teachers in the system.
     */
    public function run(): void
    {
        // Get all users with teacher role (assuming role-based system)
        $teachers = User::where('role', 'teacher')
            ->orWhere('email', 'like', '%teacher%')
            ->get();

        // If no teachers found, create sample teacher analytics for first 3 users
        if ($teachers->isEmpty()) {
            $teachers = User::take(3)->get();
        }

        foreach ($teachers as $teacher) {
            DB::table('teacher_analytics')->insert([
                'teacher_id' => $teacher->id,
                'total_students' => rand(15, 45),
                'completion_rate' => round(rand(65, 95) + (rand(0, 99) / 100), 2),
                'hotspot_interactions' => rand(120, 350),
                'game_sessions' => rand(80, 200),
                'high_scores' => rand(25, 75),
                'active_assignments' => rand(5, 15),
                'pending_submissions' => rand(8, 25),
                'total_classes' => rand(3, 8),
                'average_score' => round(rand(70, 95) + (rand(0, 99) / 100), 2),
                'last_updated' => now()->subDays(rand(0, 7)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create additional sample data if we have fewer than 5 teachers
        $currentCount = $teachers->count();
        if ($currentCount < 5) {
            for ($i = $currentCount; $i < 5; $i++) {
                DB::table('teacher_analytics')->insert([
                    'teacher_id' => 1, // Default to first user
                    'total_students' => rand(10, 30),
                    'completion_rate' => round(rand(60, 90) + (rand(0, 99) / 100), 2),
                    'hotspot_interactions' => rand(100, 300),
                    'game_sessions' => rand(60, 180),
                    'high_scores' => rand(20, 60),
                    'active_assignments' => rand(3, 12),
                    'pending_submissions' => rand(5, 20),
                    'total_classes' => rand(2, 6),
                    'average_score' => round(rand(65, 90) + (rand(0, 99) / 100), 2),
                    'last_updated' => now()->subDays(rand(0, 14)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}