<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\GamificationEvent;
use App\Models\WhisperJob;
use Spatie\Permission\Models\Role;

class MassDemoSeeder extends Seeder
{
    /**
     * Seed mass demo data for testing and development.
     * Creates additional teachers, students, classes, assignments, and related data.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Creating mass demo data...');

        // Get existing roles
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        // Create additional teachers (5 more)
        $this->command->info('Creating additional teachers...');
        $teachers = User::factory(5)->create()->each(function ($teacher) use ($teacherRole) {
            $teacher->assignRole($teacherRole);
        });

        // Create additional students (20 more)
        $this->command->info('Creating additional students...');
        $students = User::factory(20)->create()->each(function ($student) use ($studentRole) {
            $student->assignRole($studentRole);
        });

        // Get all teachers (existing + new)
        $allTeachers = User::role('teacher')->get();
        
        // Create additional classes (10 more)
        $this->command->info('Creating additional classes...');
        $classes = SchoolClass::factory(10)->create([
            'teacher_id' => fn() => $allTeachers->random()->id,
        ]);

        // Enroll students in classes (random distribution)
        $this->command->info('Enrolling students in classes...');
        $allStudents = User::role('student')->get();
        $allClasses = SchoolClass::all();
        
        foreach ($allClasses as $class) {
            // Each class gets 3-8 random students
            $studentsToEnroll = $allStudents->random(rand(3, 8));
            foreach ($studentsToEnroll as $student) {
                $class->members()->syncWithoutDetaching([
                    $student->id => ['role_in_class' => 'student']
                ]);
            }
        }

        // Create additional assignments (25 more)
        $this->command->info('Creating additional assignments...');
        $assignments = Assignment::factory(25)->create([
            'class_id' => fn() => $allClasses->random()->id,
            'teacher_id' => fn() => $allTeachers->random()->id,
        ]);

        // Create submissions for assignments
        $this->command->info('Creating submissions...');
        $allAssignments = Assignment::where('status', 'published')->get();
        
        foreach ($allAssignments as $assignment) {
            // Get students from the assignment's class
            $classStudents = $assignment->class ? $assignment->class->members()->wherePivot('role_in_class', 'student')->get() : collect();
            
            if ($classStudents->isNotEmpty()) {
                // 60-80% of students submit
                $submittingStudents = $classStudents->random(rand(
                    (int)($classStudents->count() * 0.6),
                    (int)($classStudents->count() * 0.8)
                ));
                
                foreach ($submittingStudents as $student) {
                    Submission::factory()->create([
                        'assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                    ]);
                }
            }
        }

        // Create gamification events
        $this->command->info('Creating gamification events...');
        foreach ($allStudents as $student) {
            // Each student gets 5-15 random events
            GamificationEvent::factory(rand(5, 15))->create([
                'user_id' => $student->id,
            ]);
        }

        // Create whisper jobs for submissions with audio
        $this->command->info('Creating whisper jobs...');
        $submissionsWithAudio = Submission::whereNotNull('audio_s3_url')->get();
        
        foreach ($submissionsWithAudio as $submission) {
            // 80% chance of having a whisper job
            if (rand(1, 100) <= 80) {
                WhisperJob::factory()->create([
                    'user_id' => $submission->student_id,
                    'submission_id' => $submission->id,
                ]);
            }
        }

        // Create some standalone whisper jobs (for practice recordings)
        $this->command->info('Creating standalone whisper jobs...');
        foreach ($allStudents->random(10) as $student) {
            WhisperJob::factory(rand(1, 3))->create([
                'user_id' => $student->id,
                'submission_id' => null, // Standalone practice recording
            ]);
        }

        $this->command->info('Mass demo data seeding completed!');
        $this->command->info('Summary:');
        $this->command->info('- Additional Teachers: 5');
        $this->command->info('- Additional Students: 20');
        $this->command->info('- Additional Classes: 10');
        $this->command->info('- Additional Assignments: 25');
        $this->command->info('- Submissions: ~' . Submission::count());
        $this->command->info('- Gamification Events: ~' . GamificationEvent::count());
        $this->command->info('- Whisper Jobs: ~' . WhisperJob::count());
    }
}