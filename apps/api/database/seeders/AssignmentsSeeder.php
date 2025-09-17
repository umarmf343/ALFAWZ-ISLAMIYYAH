<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assignment;
use App\Models\Hotspot;
use App\Models\SchoolClass;
use App\Models\User;

/**
 * Seeder for creating demo assignments with flipbook+hotspot and recitation types.
 * Creates realistic assignments for different class levels with appropriate content.
 */
class AssignmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo assignments and associated hotspots for interactive learning.
     *
     * @return void
     */
    public function run(): void
    {
        $classes = SchoolClass::with('teacher')->get();
        
        if ($classes->isEmpty()) {
            $this->command->warn('No classes found. Please run ClassesSeeder first.');
            return;
        }

        $createdAssignments = 0;
        $createdHotspots = 0;

        foreach ($classes as $class) {
            // Create 2-4 assignments per class
            $assignmentCount = rand(2, 4);
            
            for ($i = 0; $i < $assignmentCount; $i++) {
                $assignmentType = rand(1, 10) <= 6 ? 'flipbook' : 'recitation'; // 60% flipbook, 40% recitation
                
                if ($assignmentType === 'flipbook') {
                    $assignment = $this->createFlipbookAssignment($class);
                    $this->createHotspotsForAssignment($assignment);
                    $createdHotspots += rand(3, 6);
                } else {
                    $assignment = $this->createRecitationAssignment($class);
                }
                
                $createdAssignments++;
            }
        }

        // Create some additional assignments using factories
        $additionalAssignments = Assignment::factory()
            ->count(5)
            ->flipbook()
            ->create([
                'class_id' => fn() => $classes->random()->id,
                'teacher_id' => fn() => $classes->random()->teacher->id,
            ]);

        foreach ($additionalAssignments as $assignment) {
            $this->createHotspotsForAssignment($assignment);
            $createdHotspots += rand(2, 5);
        }

        $createdAssignments += 5;

        $this->command->info('Assignments created successfully!');
        $this->command->info('- Total Assignments: ' . $createdAssignments);
        $this->command->info('- Flipbook Assignments: ' . Assignment::whereJsonContains('content->type', 'flipbook')->count());
        $this->command->info('- Recitation Assignments: ' . Assignment::whereJsonContains('content->type', 'recitation')->count());
        $this->command->info('- Total Hotspots: ' . Hotspot::count());
        $this->command->info('- Published Assignments: ' . Assignment::where('status', 'published')->count());
    }

    /**
     * Create a flipbook assignment with interactive content.
     * 
     * @param SchoolClass $class The class to create assignment for
     * @return Assignment Created assignment instance
     */
    private function createFlipbookAssignment(SchoolClass $class): Assignment
    {
        $flipbookTitles = [
            'Interactive Surah Al-Fatiha Study',
            'Tajweed Rules: Noon Sakinah & Tanween',
            'Beautiful Names of Allah (Asma ul-Husna)',
            'Quranic Arabic Vocabulary Builder',
            'Stories of the Prophets - Interactive Journey',
            'Madinah Arabic Reader - Lesson ' . rand(1, 20),
            'Islamic Calendar & Important Dates',
            'Pillars of Islam - Visual Guide',
        ];

        $title = $flipbookTitles[array_rand($flipbookTitles)];
        $dueDate = now()->addDays(rand(3, 14));
        $status = rand(1, 10) <= 8 ? 'published' : 'draft'; // 80% published

        return Assignment::create([
            'class_id' => $class->id,
            'teacher_id' => $class->teacher_id,
            'title' => $title,
            'description' => "Interactive flipbook assignment for {$class->title}. Click on the hotspots to explore different sections and complete the learning objectives.",
            'image_s3_url' => 'https://alfawz-demo.s3.amazonaws.com/flipbooks/demo-page-' . rand(1, 5) . '.jpg',
            'due_at' => $dueDate,
            'status' => $status,
            'targets' => null, // Class-wide assignment
            'content' => [
                'type' => 'flipbook',
                'pages' => rand(5, 15),
                'interactive_elements' => rand(8, 20),
                'learning_objectives' => [
                    'Understand the main concepts presented',
                    'Interact with all hotspot elements',
                    'Complete the reflection questions',
                    'Practice pronunciation where applicable'
                ],
                'instructions' => 'Navigate through the flipbook pages and click on all interactive hotspots. Pay attention to audio pronunciations and take notes on key concepts.',
            ],
            'grading' => [
                'total_points' => 100,
                'rubric' => [
                    'completion' => ['points' => 40, 'description' => 'All hotspots visited and content reviewed'],
                    'understanding' => ['points' => 30, 'description' => 'Demonstrates comprehension of key concepts'],
                    'engagement' => ['points' => 20, 'description' => 'Active participation with interactive elements'],
                    'timeliness' => ['points' => 10, 'description' => 'Submitted before due date']
                ],
                'auto_grade' => true
            ],
            'created_at' => now()->subDays(rand(1, 7)),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a recitation assignment for Quran memorization.
     * 
     * @param SchoolClass $class The class to create assignment for
     * @return Assignment Created assignment instance
     */
    private function createRecitationAssignment(SchoolClass $class): Assignment
    {
        $surahs = [
            ['name' => 'Al-Fatiha', 'number' => 1, 'verses' => 7],
            ['name' => 'Al-Ikhlas', 'number' => 112, 'verses' => 4],
            ['name' => 'Al-Falaq', 'number' => 113, 'verses' => 5],
            ['name' => 'An-Nas', 'number' => 114, 'verses' => 6],
            ['name' => 'Al-Kafirun', 'number' => 109, 'verses' => 6],
            ['name' => 'An-Nasr', 'number' => 110, 'verses' => 3],
            ['name' => 'Al-Masad', 'number' => 111, 'verses' => 5],
        ];

        $surah = $surahs[array_rand($surahs)];
        $dueDate = now()->addDays(rand(7, 21)); // Longer time for recitation
        $status = rand(1, 10) <= 9 ? 'published' : 'draft'; // 90% published

        return Assignment::create([
            'class_id' => $class->id,
            'teacher_id' => $class->teacher_id,
            'title' => "Recite Surah {$surah['name']} with Tajweed",
            'description' => "Memorize and recite Surah {$surah['name']} (Chapter {$surah['number']}) with proper Tajweed rules. Focus on correct pronunciation, rhythm, and emotional connection.",
            'image_s3_url' => null,
            'due_at' => $dueDate,
            'status' => $status,
            'targets' => null,
            'content' => [
                'type' => 'recitation',
                'surah_number' => $surah['number'],
                'surah_name' => $surah['name'],
                'verse_count' => $surah['verses'],
                'recitation_requirements' => [
                    'memorization' => 'Complete memorization required',
                    'tajweed' => 'Apply proper Tajweed rules',
                    'fluency' => 'Smooth recitation without hesitation',
                    'pronunciation' => 'Correct Arabic pronunciation'
                ],
                'practice_resources' => [
                    'audio_reference' => 'https://alfawz-demo.s3.amazonaws.com/audio/surah-' . $surah['number'] . '-reference.mp3',
                    'tajweed_guide' => 'Review Tajweed rules before recording',
                    'practice_tips' => 'Practice in small sections, focus on difficult verses'
                ]
            ],
            'grading' => [
                'total_points' => 100,
                'rubric' => [
                    'memorization' => ['points' => 30, 'description' => 'Complete and accurate memorization'],
                    'tajweed' => ['points' => 25, 'description' => 'Proper application of Tajweed rules'],
                    'fluency' => ['points' => 25, 'description' => 'Smooth and confident recitation'],
                    'pronunciation' => ['points' => 20, 'description' => 'Clear Arabic pronunciation']
                ],
                'auto_grade' => false, // Requires teacher evaluation
                'ai_feedback' => true
            ],
            'created_at' => now()->subDays(rand(1, 10)),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create hotspots for a flipbook assignment.
     * 
     * @param Assignment $assignment The assignment to create hotspots for
     * @return void
     */
    private function createHotspotsForAssignment(Assignment $assignment): void
    {
        $hotspotCount = rand(3, 6);
        
        $hotspotTypes = [
            ['title' => 'Audio Pronunciation', 'tooltip' => 'Click to hear correct pronunciation'],
            ['title' => 'Vocabulary Definition', 'tooltip' => 'Learn the meaning of this Arabic word'],
            ['title' => 'Tajweed Rule', 'tooltip' => 'Important Tajweed rule explanation'],
            ['title' => 'Historical Context', 'tooltip' => 'Background information about this verse'],
            ['title' => 'Reflection Question', 'tooltip' => 'Think about this concept'],
            ['title' => 'Related Hadith', 'tooltip' => 'Prophetic saying related to this topic'],
        ];

        for ($i = 0; $i < $hotspotCount; $i++) {
            $hotspotType = $hotspotTypes[array_rand($hotspotTypes)];
            
            Hotspot::create([
                'assignment_id' => $assignment->id,
                'title' => $hotspotType['title'],
                'tooltip' => $hotspotType['tooltip'],
                'audio_s3_url' => rand(1, 10) <= 6 ? 'https://alfawz-demo.s3.amazonaws.com/audio/hotspot-' . rand(1, 20) . '.mp3' : null,
                'x' => rand(50, 800), // Random position on image
                'y' => rand(50, 600),
                'width' => rand(80, 150),
                'height' => rand(60, 120),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}