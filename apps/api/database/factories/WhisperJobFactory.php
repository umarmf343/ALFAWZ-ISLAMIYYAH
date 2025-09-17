<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * Factory for generating WhisperJob model instances with realistic demo data.
 * Creates AI transcription jobs with mock feedback and analysis results.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhisperJob>
 */
class WhisperJobFactory extends Factory
{
    /**
     * Define the model's default state.
     * Creates Whisper jobs with realistic states and data.
     *
     * @return array<string, mixed> Default factory attributes
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['completed', 'failed', 'processing', 'pending']);
        $isCompleted = $status === 'completed';
        
        return [
            'user_id' => User::factory()->student(),
            'submission_id' => null, // Can be null for standalone jobs
            'status' => $status,
            'audio_s3_url' => 'https://alfawz-quran.s3.amazonaws.com/whisper/' . fake()->uuid() . '.mp3',
            'transcription' => $isCompleted ? $this->generateArabicText() : null,
            'confidence_score' => $isCompleted ? fake()->randomFloat(2, 0.75, 0.98) : null,
            'ai_feedback' => $isCompleted ? [
                'pronunciation_score' => fake()->numberBetween(70, 95),
                'tajweed_score' => fake()->numberBetween(65, 90),
                'fluency_score' => fake()->numberBetween(60, 95),
                'suggestions' => fake()->randomElements([
                    'Focus on heavy letters',
                    'Improve Madd application',
                    'Work on rhythm',
                    'Practice Ghunnah',
                ], fake()->numberBetween(1, 3)),
            ] : null,
            'processing_time_ms' => $isCompleted ? fake()->numberBetween(2000, 15000) : null,
            'error_message' => $status === 'failed' ? fake()->randomElement([
                'Audio file corrupted',
                'Processing timeout',
                'Insufficient audio quality',
                'Network error during processing',
            ]) : null,
            'created_at' => fake()->dateTimeBetween('-2 hours', 'now'),
        ];
    }

    /**
     * Generate sample Arabic text for transcription.
     *
     * @return string
     */
    private function generateArabicText(): string
    {
        $verses = [
            'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
            'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ',
            'الرَّحْمَٰنِ الرَّحِيمِ',
            'مَالِكِ يَوْمِ الدِّينِ',
            'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ',
            'قُلْ هُوَ اللَّهُ أَحَدٌ',
            'اللَّهُ الصَّمَدُ',
            'لَمْ يَلِدْ وَلَمْ يُولَدْ',
        ];
        
        return fake()->randomElement($verses);
    }

    /**
     * Create a completed job.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'transcription' => $this->generateArabicText(),
            'confidence_score' => fake()->randomFloat(2, 0.85, 0.98),
            'ai_feedback' => [
                'pronunciation_score' => fake()->numberBetween(80, 95),
                'tajweed_score' => fake()->numberBetween(75, 90),
                'fluency_score' => fake()->numberBetween(70, 95),
                'suggestions' => ['Excellent recitation'],
            ],
        ]);
    }

    /**
     * Create a failed job.
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'transcription' => null,
            'confidence_score' => null,
            'ai_feedback' => null,
            'error_message' => 'Processing failed due to audio quality issues',
        ]);
    }
}