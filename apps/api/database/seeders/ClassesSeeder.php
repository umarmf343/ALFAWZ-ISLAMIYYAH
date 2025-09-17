<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\ClassMember;
use Spatie\Permission\Models\Role;

/**
 * Seeder for creating demo classes with teacher assignments and student enrollments.
 * Creates classes for levels 1-3 with realistic teacher-student distributions.
 */
class ClassesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo classes and assigns teachers and students to them.
     *
     * @return void
     */
    public function run(): void
    {
        // Get all teachers and students
        $teachers = User::role('teacher')->get();
        $students = User::role('student')->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Please run UsersSeeder first.');
            return;
        }

        if ($students->isEmpty()) {
            $this->command->warn('No students found. Please run UsersSeeder first.');
            return;
        }

        // Level 1 Classes (Beginners)
        $level1Classes = [
            [
                'title' => 'Quran Basics - Morning Class',
                'description' => 'Introduction to Arabic letters, basic Tajweed rules, and short Surahs. Perfect for beginners starting their Quranic journey.',
                'level' => 1,
            ],
            [
                'title' => 'Quran Basics - Evening Class',
                'description' => 'Foundational Quran reading skills with emphasis on proper pronunciation and basic memorization techniques.',
                'level' => 1,
            ],
            [
                'title' => 'Al-Fatiha & Short Surahs',
                'description' => 'Focused study of Al-Fatiha and the last 10 Surahs of the Quran with detailed Tajweed instruction.',
                'level' => 1,
            ],
        ];

        // Level 2 Classes (Intermediate)
        $level2Classes = [
            [
                'title' => 'Intermediate Tajweed & Memorization',
                'description' => 'Advanced Tajweed rules, Juz Amma memorization, and introduction to longer Surahs.',
                'level' => 2,
            ],
            [
                'title' => 'Surah Al-Baqarah Study Circle',
                'description' => 'Detailed study and memorization of Surah Al-Baqarah with focus on meaning and application.',
                'level' => 2,
            ],
        ];

        // Level 3 Classes (Advanced)
        $level3Classes = [
            [
                'title' => 'Advanced Quranic Studies',
                'description' => 'Complete Quran recitation mastery, advanced Tajweed, and preparation for Ijazah certification.',
                'level' => 3,
            ],
            [
                'title' => 'Quranic Tafseer & Reflection',
                'description' => 'Deep study of Quranic meanings, classical Tafseer, and contemporary applications.',
                'level' => 3,
            ],
        ];

        $allClasses = array_merge($level1Classes, $level2Classes, $level3Classes);
        $createdClasses = [];

        // Create classes and assign teachers
        foreach ($allClasses as $classData) {
            // Assign a random teacher to each class
            $teacher = $teachers->random();
            
            $class = SchoolClass::create([
                'teacher_id' => $teacher->id,
                'title' => $classData['title'],
                'description' => $classData['description'],
                'level' => $classData['level'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $createdClasses[] = $class;
        }

        // Assign students to classes randomly but with level preference
        $studentsCollection = $students->shuffle();
        $studentsPerClass = max(3, intval($studentsCollection->count() / count($createdClasses)));

        foreach ($createdClasses as $class) {
            // Get a subset of students for this class
            $classStudents = $studentsCollection->take($studentsPerClass);
            
            foreach ($classStudents as $student) {
                ClassMember::firstOrCreate([
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                    'role_in_class' => 'student',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Remove assigned students from the pool to avoid overlap
            $studentsCollection = $studentsCollection->diff($classStudents);
            
            // If we run out of students, reset the pool
            if ($studentsCollection->isEmpty()) {
                $studentsCollection = $students->shuffle();
            }
        }

        // Create some additional random classes using factories
        $additionalClasses = SchoolClass::factory()
            ->count(3)
            ->create([
                'teacher_id' => fn() => $teachers->random()->id,
            ]);

        // Assign random students to additional classes
        foreach ($additionalClasses as $class) {
            $randomStudents = $students->random(rand(4, 8));
            
            foreach ($randomStudents as $student) {
                ClassMember::firstOrCreate([
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                    'role_in_class' => 'student',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $totalClasses = SchoolClass::count();
        $totalEnrollments = ClassMember::count();
        
        $this->command->info('Classes created successfully!');
        $this->command->info('- Total Classes: ' . $totalClasses);
        $this->command->info('- Level 1 Classes: ' . SchoolClass::where('level', 1)->count());
        $this->command->info('- Level 2 Classes: ' . SchoolClass::where('level', 2)->count());
        $this->command->info('- Level 3 Classes: ' . SchoolClass::where('level', 3)->count());
        $this->command->info('- Total Student Enrollments: ' . $totalEnrollments);
        $this->command->info('- Average Students per Class: ' . round($totalEnrollments / $totalClasses, 1));
    }
}