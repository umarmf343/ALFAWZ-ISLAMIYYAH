<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Submission;
use App\Models\WhisperJob;

class DemoWhisperJobsSeeder extends Seeder
{
    /**
     * Seed demo whisper jobs for UI testing.
     * Creates whisper jobs in various states to test different UI scenarios.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Creating demo whisper jobs for UI testing...');

        // Get some students and submissions for realistic data
        $students = User::role('student')->limit(10)->get();
        $submissions = Submission::whereNotNull('audio_s3_url')->limit(15)->get();

        if ($students->isEmpty()) {
            $this->command->warn('No students found. Please run other seeders first.');
            return;
        }

        // Create pending jobs (5)
        $this->command->info('Creating pending whisper jobs...');
        for ($i = 0; $i < 5; $i++) {
            WhisperJob::factory()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'status' => 'pending',
                'transcription' => null,
                'confidence_score' => null,
                'ai_feedback' => null,
                'processing_time_ms' => null,
                'error_message' => null,
            ]);
        }

        // Create processing jobs (3)
        $this->command->info('Creating processing whisper jobs...');
        for ($i = 0; $i < 3; $i++) {
            WhisperJob::factory()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'status' => 'processing',
                'transcription' => null,
                'confidence_score' => null,
                'ai_feedback' => null,
                'processing_time_ms' => null,
                'error_message' => null,
            ]);
        }

        // Create completed jobs with high quality (8)
        $this->command->info('Creating completed high-quality whisper jobs...');
        for ($i = 0; $i < 8; $i++) {
            WhisperJob::factory()->completed()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'confidence_score' => fake()->randomFloat(2, 0.90, 0.98),
                'ai_feedback' => [
                    'pronunciation_score' => fake()->numberBetween(85, 95),
                    'tajweed_score' => fake()->numberBetween(80, 95),
                    'fluency_score' => fake()->numberBetween(85, 95),
                    'suggestions' => [
                        'Excellent recitation with clear pronunciation',
                        'Beautiful application of Tajweed rules',
                        'Consistent pace and rhythm',
                    ],
                ],
                'processing_time_ms' => fake()->numberBetween(15000, 45000),
            ]);
        }

        // Create completed jobs with medium quality (6)
        $this->command->info('Creating completed medium-quality whisper jobs...');
        for ($i = 0; $i < 6; $i++) {
            WhisperJob::factory()->completed()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'confidence_score' => fake()->randomFloat(2, 0.75, 0.89),
                'ai_feedback' => [
                    'pronunciation_score' => fake()->numberBetween(70, 84),
                    'tajweed_score' => fake()->numberBetween(65, 79),
                    'fluency_score' => fake()->numberBetween(70, 84),
                    'suggestions' => [
                        'Good recitation with room for improvement',
                        'Focus on heavy letter pronunciation',
                        'Practice consistent Madd application',
                    ],
                ],
                'processing_time_ms' => fake()->numberBetween(20000, 60000),
            ]);
        }

        // Create completed jobs with lower quality (4)
        $this->command->info('Creating completed lower-quality whisper jobs...');
        for ($i = 0; $i < 4; $i++) {
            WhisperJob::factory()->completed()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'confidence_score' => fake()->randomFloat(2, 0.60, 0.74),
                'ai_feedback' => [
                    'pronunciation_score' => fake()->numberBetween(50, 69),
                    'tajweed_score' => fake()->numberBetween(45, 64),
                    'fluency_score' => fake()->numberBetween(55, 69),
                    'suggestions' => [
                        'Needs improvement in pronunciation clarity',
                        'Practice Tajweed rules more consistently',
                        'Work on speech pace and rhythm',
                        'Consider recording in quieter environment',
                    ],
                ],
                'processing_time_ms' => fake()->numberBetween(25000, 70000),
            ]);
        }

        // Create failed jobs (4)
        $this->command->info('Creating failed whisper jobs...');
        $failureReasons = [
            'Audio quality too low for processing',
            'Background noise level too high',
            'Audio file format not supported',
            'Processing timeout due to server load',
        ];

        for ($i = 0; $i < 4; $i++) {
            WhisperJob::factory()->failed()->create([
                'user_id' => $students->random()->id,
                'submission_id' => $submissions->isNotEmpty() ? $submissions->random()->id : null,
                'error_message' => $failureReasons[$i],
                'processing_time_ms' => fake()->numberBetween(5000, 15000),
            ]);
        }

        // Create some standalone practice jobs (not linked to submissions)
        $this->command->info('Creating standalone practice whisper jobs...');
        for ($i = 0; $i < 6; $i++) {
            $status = fake()->randomElement(['completed', 'completed', 'completed', 'pending', 'processing', 'failed']);
            
            if ($status === 'completed') {
                WhisperJob::factory()->completed()->create([
                    'user_id' => $students->random()->id,
                    'submission_id' => null, // Standalone practice
                ]);
            } elseif ($status === 'failed') {
                WhisperJob::factory()->failed()->create([
                    'user_id' => $students->random()->id,
                    'submission_id' => null, // Standalone practice
                ]);
            } else {
                WhisperJob::factory()->create([
                    'user_id' => $students->random()->id,
                    'submission_id' => null, // Standalone practice
                    'status' => $status,
                    'transcription' => null,
                    'confidence_score' => null,
                    'ai_feedback' => null,
                    'processing_time_ms' => null,
                    'error_message' => null,
                ]);
            }
        }

        $this->command->info('Demo whisper jobs seeding completed!');
        $this->command->info('Summary:');
        $this->command->info('- Pending jobs: 5');
        $this->command->info('- Processing jobs: 3');
        $this->command->info('- Completed high-quality jobs: 8');
        $this->command->info('- Completed medium-quality jobs: 6');
        $this->command->info('- Completed lower-quality jobs: 4');
        $this->command->info('- Failed jobs: 4');
        $this->command->info('- Standalone practice jobs: 6');
        $this->command->info('- Total whisper jobs created: 36');
    }
}