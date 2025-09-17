<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * Factory for generating Submission model instances with realistic demo data.
 * Creates student submissions with rubric scores, feedback, and audio recordings.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     * Creates a basic submission with pending status.
     *
     * @return array<string, mixed> Default factory attributes
     */
    public function definition(): array
    {
        $isGraded = fake()->boolean(60); // 60% chance of being graded
        
        return [
            'assignment_id' => Assignment::factory(),
            'student_id' => User::factory()->student(),
            'status' => $isGraded ? 'graded' : 'pending',
            'score' => $isGraded ? fake()->numberBetween(65, 95) : null,
            'rubric' => $isGraded ? [
                'tajweed' => fake()->numberBetween(70, 95),
                'fluency' => fake()->numberBetween(65, 90),
                'memorization' => fake()->numberBetween(60, 95),
                'pronunciation' => fake()->numberBetween(70, 90),
            ] : null,
            'audio_s3_url' => 'https://alfawz-quran.s3.amazonaws.com/submissions/' . fake()->uuid() . '.mp3',
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }



    /**
     * Create a graded submission.
     *
     * @return static
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'score' => fake()->numberBetween(65, 95),
            'rubric' => [
                'tajweed' => fake()->numberBetween(70, 95),
                'fluency' => fake()->numberBetween(65, 90),
                'memorization' => fake()->numberBetween(60, 95),
                'pronunciation' => fake()->numberBetween(70, 90),
            ],
        ]);
    }

    /**
     * Create a pending submission.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'score' => null,
            'rubric' => null,
        ]);
    }
}