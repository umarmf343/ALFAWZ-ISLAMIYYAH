<?php

namespace Database\Factories;

use App\Models\GamificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GamificationEvent>
 */
class GamificationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     * Creates gamification events for points and hasanat tracking.
     *
     * @return array<string, mixed> Default factory attributes
     */
    public function definition(): array
    {
        $eventType = fake()->randomElement(['submission_graded', 'assignment_completed', 'daily_recitation', 'streak_bonus', 'perfect_score']);
        $points = $this->getPointsForEvent($eventType);
        $hasanat = $this->getHasanatForEvent($eventType);
        
        return [
            'user_id' => User::factory()->student(),
            'event_type' => $eventType,
            'points' => $points,
            'hasanat' => $hasanat,
            'description' => $this->getDescriptionForEvent($eventType),
            'metadata' => $this->getMetadataForEvent($eventType),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Get points value based on event type.
     *
     * @param string $eventType
     * @return int
     */
    private function getPointsForEvent(string $eventType): int
    {
        return match ($eventType) {
            'submission_graded' => fake()->numberBetween(10, 50),
            'assignment_completed' => fake()->numberBetween(20, 40),
            'daily_recitation' => fake()->numberBetween(5, 15),
            'streak_bonus' => fake()->numberBetween(25, 75),
            'perfect_score' => fake()->numberBetween(50, 100),
            default => fake()->numberBetween(5, 25),
        };
    }

    /**
     * Get hasanat value based on event type.
     *
     * @param string $eventType
     * @return int
     */
    private function getHasanatForEvent(string $eventType): int
    {
        return match ($eventType) {
            'submission_graded' => fake()->numberBetween(100, 500),
            'assignment_completed' => fake()->numberBetween(200, 400),
            'daily_recitation' => fake()->numberBetween(50, 150),
            'streak_bonus' => fake()->numberBetween(250, 750),
            'perfect_score' => fake()->numberBetween(500, 1000),
            default => fake()->numberBetween(50, 250),
        };
    }

    /**
     * Get description for event type.
     *
     * @param string $eventType
     * @return string
     */
    private function getDescriptionForEvent(string $eventType): string
    {
        return match ($eventType) {
            'submission_graded' => 'Earned points for graded submission',
            'assignment_completed' => 'Completed assignment successfully',
            'daily_recitation' => 'Daily Quran recitation practice',
            'streak_bonus' => 'Bonus for consecutive days of practice',
            'perfect_score' => 'Perfect score achievement bonus',
            default => 'General gamification event',
        };
    }

    /**
     * Get metadata for event type.
     *
     * @param string $eventType
     * @return array
     */
    private function getMetadataForEvent(string $eventType): array
    {
        return match ($eventType) {
            'submission_graded' => [
                'assignment_id' => fake()->numberBetween(1, 50),
                'score' => fake()->numberBetween(65, 100),
                'grade' => fake()->randomElement(['A', 'B+', 'B', 'C+']),
            ],
            'assignment_completed' => [
                'assignment_id' => fake()->numberBetween(1, 50),
                'completion_time' => fake()->numberBetween(300, 1800), // seconds
            ],
            'daily_recitation' => [
                'surah_id' => fake()->numberBetween(1, 114),
                'verses_count' => fake()->numberBetween(3, 20),
                'duration' => fake()->numberBetween(120, 600), // seconds
            ],
            'streak_bonus' => [
                'streak_days' => fake()->numberBetween(3, 30),
                'bonus_multiplier' => fake()->randomFloat(1, 1.5, 3.0),
            ],
            'perfect_score' => [
                'assignment_id' => fake()->numberBetween(1, 50),
                'achievement_type' => 'perfect_recitation',
            ],
            default => [],
        };
    }

    /**
     * Create a high-value event.
     *
     * @return static
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => fake()->randomElement(['perfect_score', 'streak_bonus']),
            'points' => fake()->numberBetween(75, 150),
            'hasanat' => fake()->numberBetween(750, 1500),
        ]);
    }

    /**
     * Create a daily recitation event.
     *
     * @return static
     */
    public function dailyRecitation(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'daily_recitation',
            'points' => fake()->numberBetween(5, 15),
            'hasanat' => fake()->numberBetween(50, 150),
            'description' => 'Daily Quran recitation practice',
        ]);
    }
}