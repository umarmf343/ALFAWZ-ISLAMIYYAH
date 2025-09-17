<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\ClassMember;
use App\Models\User;

/**
 * Seeder for creating demo student submissions across assignments.
 * Creates realistic submissions with various statuses and scores for testing.
 */
class SubmissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo submissions for students across published assignments.
     *
     * @return void
     */
    public function run(): void
    {
        $publishedAssignments = Assignment::where('status', 'published')->with('class.members.user')->get();
        
        if ($publishedAssignments->isEmpty()) {
            $this->command->warn('No published assignments found. Please run AssignmentsSeeder first.');
            return;
        }

        $createdSubmissions = 0;
        $gradedSubmissions = 0;

        foreach ($publishedAssignments as $assignment) {
            $classMembers = $assignment->class->members()->where('role_in_class', 'student')->with('user')->get();
            
            if ($classMembers->isEmpty()) {
                continue;
            }

            // 70-90% of students submit assignments
            $submissionRate = rand(70, 90) / 100;
            $studentsToSubmit = $classMembers->random(intval($classMembers->count() * $submissionRate));

            foreach ($studentsToSubmit as $classMember) {
                $student = $classMember->user;
                
                // Check if submission already exists
                $existingSubmission = Submission::where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->first();
                
                if ($existingSubmission) {
                    continue;
                }

                $submission = $this->createSubmissionForAssignment($assignment, $student);
                $createdSubmissions++;
                
                if ($submission->status === 'graded') {
                    $gradedSubmissions++;
                }
            }
        }

        // Create some additional random submissions using factories
        $additionalSubmissions = Submission::factory()
            ->count(15)
            ->create([
                'assignment_id' => fn() => $publishedAssignments->random()->id,
                'student_id' => fn() => User::role('student')->inRandomOrder()->first()->id,
            ]);

        $createdSubmissions += 15;
        $gradedSubmissions += $additionalSubmissions->where('status', 'graded')->count();

        $this->command->info('Submissions created successfully!');
        $this->command->info('- Total Submissions: ' . $createdSubmissions);
        $this->command->info('- Graded Submissions: ' . $gradedSubmissions);
        $this->command->info('- Pending Submissions: ' . ($createdSubmissions - $gradedSubmissions));
        $this->command->info('- Average Score: ' . round(Submission::whereNotNull('score')->avg('score'), 1) . '%');
    }

    /**
     * Create a submission for a specific assignment and student.
     * 
     * @param Assignment $assignment The assignment to submit for
     * @param User $student The student making the submission
     * @return Submission Created submission instance
     */
    private function createSubmissionForAssignment(Assignment $assignment, User $student): Submission
    {
        $assignmentType = $assignment->content['type'] ?? 'flipbook';
        $isGraded = rand(1, 10) <= 7; // 70% of submissions are graded
        
        $submissionData = [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => $isGraded ? 'graded' : 'pending',
            'created_at' => $this->getRandomSubmissionDate($assignment),
            'updated_at' => now(),
        ];

        if ($assignmentType === 'recitation') {
            $submissionData = array_merge($submissionData, $this->getRecitationSubmissionData($isGraded));
        } else {
            $submissionData = array_merge($submissionData, $this->getFlipbookSubmissionData($isGraded));
        }

        return Submission::create($submissionData);
    }

    /**
     * Get submission data for recitation assignments.
     * 
     * @param bool $isGraded Whether the submission should be graded
     * @return array Submission data array
     */
    private function getRecitationSubmissionData(bool $isGraded): array
    {
        $data = [
            'audio_s3_url' => 'https://alfawz-demo.s3.amazonaws.com/submissions/recitation-' . rand(1000, 9999) . '.mp3',
        ];

        if ($isGraded) {
            $score = $this->generateRealisticScore();
            $data['score'] = $score;
            $data['rubric_json'] = $this->generateRecitationRubric($score);
        }

        return $data;
    }

    /**
     * Get submission data for flipbook assignments.
     * 
     * @param bool $isGraded Whether the submission should be graded
     * @return array Submission data array
     */
    private function getFlipbookSubmissionData(bool $isGraded): array
    {
        $data = [];

        if ($isGraded) {
            $score = $this->generateRealisticScore();
            $data['score'] = $score;
            $data['rubric_json'] = $this->generateFlipbookRubric($score);
        }

        return $data;
    }

    /**
     * Generate a realistic score with normal distribution.
     * 
     * @return int Score between 0-100
     */
    private function generateRealisticScore(): int
    {
        // Generate scores with normal distribution around 75-85
        $weights = [
            range(90, 100) => 15, // Excellent: 15%
            range(80, 89) => 35,  // Good: 35%
            range(70, 79) => 30,  // Average: 30%
            range(60, 69) => 15,  // Below Average: 15%
            range(0, 59) => 5,    // Poor: 5%
        ];

        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($weights as $range => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $range[array_rand($range)];
            }
        }

        return 75; // Fallback
    }

    /**
     * Generate rubric JSON for recitation submissions.
     * 
     * @param int $overallScore Overall submission score
     * @return array Rubric data
     */
    private function generateRecitationRubric(int $overallScore): array
    {
        $baseScores = [
            'memorization' => rand(max(0, $overallScore - 15), min(100, $overallScore + 10)),
            'tajweed' => rand(max(0, $overallScore - 10), min(100, $overallScore + 15)),
            'fluency' => rand(max(0, $overallScore - 20), min(100, $overallScore + 5)),
            'pronunciation' => rand(max(0, $overallScore - 10), min(100, $overallScore + 10)),
        ];

        $comments = [
            'memorization' => $this->getMemorizationComment($baseScores['memorization']),
            'tajweed' => $this->getTajweedComment($baseScores['tajweed']),
            'fluency' => $this->getFluencyComment($baseScores['fluency']),
            'pronunciation' => $this->getPronunciationComment($baseScores['pronunciation']),
        ];

        return [
            'scores' => $baseScores,
            'comments' => $comments,
            'overall_feedback' => $this->getOverallFeedback($overallScore),
            'graded_at' => now()->toISOString(),
            'grader_notes' => 'Auto-generated demo feedback for testing purposes.'
        ];
    }

    /**
     * Generate rubric JSON for flipbook submissions.
     * 
     * @param int $overallScore Overall submission score
     * @return array Rubric data
     */
    private function generateFlipbookRubric(int $overallScore): array
    {
        $baseScores = [
            'completion' => rand(max(0, $overallScore - 10), min(100, $overallScore + 5)),
            'understanding' => rand(max(0, $overallScore - 15), min(100, $overallScore + 10)),
            'engagement' => rand(max(0, $overallScore - 20), min(100, $overallScore + 15)),
            'timeliness' => rand(max(0, $overallScore - 5), min(100, $overallScore + 5)),
        ];

        return [
            'scores' => $baseScores,
            'comments' => [
                'completion' => 'Student completed ' . $baseScores['completion'] . '% of required interactions.',
                'understanding' => $this->getUnderstandingComment($baseScores['understanding']),
                'engagement' => $this->getEngagementComment($baseScores['engagement']),
                'timeliness' => $baseScores['timeliness'] >= 90 ? 'Submitted on time' : 'Late submission',
            ],
            'overall_feedback' => $this->getOverallFeedback($overallScore),
            'graded_at' => now()->toISOString(),
        ];
    }

    /**
     * Get random submission date relative to assignment creation.
     * 
     * @param Assignment $assignment The assignment
     * @return \Carbon\Carbon Submission date
     */
    private function getRandomSubmissionDate(Assignment $assignment): \Carbon\Carbon
    {
        $assignmentDate = $assignment->created_at;
        $dueDate = $assignment->due_at ?? now();
        
        // 80% submit before due date, 20% after
        if (rand(1, 10) <= 8) {
            return $assignmentDate->copy()->addHours(rand(1, $assignmentDate->diffInHours($dueDate)));
        } else {
            return $dueDate->copy()->addHours(rand(1, 48)); // Up to 2 days late
        }
    }

    // Helper methods for generating realistic comments
    private function getMemorizationComment(int $score): string
    {
        if ($score >= 90) return 'Excellent memorization with no mistakes.';
        if ($score >= 80) return 'Good memorization with minor hesitations.';
        if ($score >= 70) return 'Adequate memorization, some verses need more practice.';
        if ($score >= 60) return 'Memorization needs improvement, several mistakes noted.';
        return 'Significant memorization gaps, requires more practice.';
    }

    private function getTajweedComment(int $score): string
    {
        if ($score >= 90) return 'Excellent application of Tajweed rules.';
        if ($score >= 80) return 'Good Tajweed with minor rule applications to improve.';
        if ($score >= 70) return 'Basic Tajweed applied, focus on specific rules.';
        if ($score >= 60) return 'Tajweed needs attention, review fundamental rules.';
        return 'Tajweed requires significant improvement.';
    }

    private function getFluencyComment(int $score): string
    {
        if ($score >= 90) return 'Very smooth and confident recitation.';
        if ($score >= 80) return 'Good flow with occasional pauses.';
        if ($score >= 70) return 'Adequate fluency, some hesitation noted.';
        if ($score >= 60) return 'Fluency needs work, frequent pauses.';
        return 'Recitation lacks fluency, requires more practice.';
    }

    private function getPronunciationComment(int $score): string
    {
        if ($score >= 90) return 'Excellent Arabic pronunciation.';
        if ($score >= 80) return 'Good pronunciation with minor corrections needed.';
        if ($score >= 70) return 'Adequate pronunciation, focus on specific letters.';
        if ($score >= 60) return 'Pronunciation needs improvement.';
        return 'Significant pronunciation issues to address.';
    }

    private function getUnderstandingComment(int $score): string
    {
        if ($score >= 90) return 'Demonstrates excellent understanding of concepts.';
        if ($score >= 80) return 'Shows good comprehension of material.';
        if ($score >= 70) return 'Basic understanding evident.';
        return 'Understanding needs development.';
    }

    private function getEngagementComment(int $score): string
    {
        if ($score >= 90) return 'Highly engaged with all interactive elements.';
        if ($score >= 80) return 'Good engagement with most activities.';
        if ($score >= 70) return 'Moderate engagement level.';
        return 'Limited engagement with activities.';
    }

    private function getOverallFeedback(int $score): string
    {
        if ($score >= 90) return 'Outstanding work! Keep up the excellent effort.';
        if ($score >= 80) return 'Great job! Continue practicing to reach excellence.';
        if ($score >= 70) return 'Good effort. Focus on areas for improvement.';
        if ($score >= 60) return 'Satisfactory work. More practice needed.';
        return 'Needs significant improvement. Please seek additional help.';
    }
}