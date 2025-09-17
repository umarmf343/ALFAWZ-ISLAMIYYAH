<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for creating demo users with different roles.
 * Creates admin, teachers, students, and named demo accounts for testing.
 */
class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo users with appropriate roles and credentials.
     *
     * @return void
     */
    public function run(): void
    {
        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@alfawz.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        // Create Demo Teachers
        $teachers = [
            [
                'name' => 'Ustadh Ahmed Hassan',
                'email' => 'ahmed@alfawz.com',
                'password' => Hash::make('teacher123'),
            ],
            [
                'name' => 'Ustadha Fatima Al-Zahra',
                'email' => 'fatima@alfawz.com',
                'password' => Hash::make('teacher123'),
            ],
            [
                'name' => 'Ustadh Omar Ibn Khattab',
                'email' => 'omar@alfawz.com',
                'password' => Hash::make('teacher123'),
            ],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = User::firstOrCreate(
                ['email' => $teacherData['email']],
                array_merge($teacherData, [
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
            $teacher->assignRole($teacherRole);
        }

        // Create Demo Students
        $students = [
            [
                'name' => 'Aisha Bint Abu Bakr',
                'email' => 'aisha@alfawz.com',
                'password' => Hash::make('student123'),
            ],
            [
                'name' => 'Abdullah Ibn Abbas',
                'email' => 'abdullah@alfawz.com',
                'password' => Hash::make('student123'),
            ],
            [
                'name' => 'Khadijah Bint Khuwaylid',
                'email' => 'khadijah@alfawz.com',
                'password' => Hash::make('student123'),
            ],
            [
                'name' => 'Ali Ibn Abi Talib',
                'email' => 'ali@alfawz.com',
                'password' => Hash::make('student123'),
            ],
            [
                'name' => 'Zainab Bint Ali',
                'email' => 'zainab@alfawz.com',
                'password' => Hash::make('student123'),
            ],
        ];

        foreach ($students as $studentData) {
            $student = User::firstOrCreate(
                ['email' => $studentData['email']],
                array_merge($studentData, [
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
            $student->assignRole($studentRole);
        }

        // Create additional random teachers (5 more)
        $additionalTeachers = User::factory()
            ->count(5)
            ->teacher()
            ->create();
        
        foreach ($additionalTeachers as $teacher) {
            $teacher->assignRole($teacherRole);
        }

        // Create additional random students (20 more)
        $additionalStudents = User::factory()
            ->count(20)
            ->student()
            ->create();
        
        foreach ($additionalStudents as $student) {
            $student->assignRole($studentRole);
        }

        $this->command->info('Users created successfully!');
        $this->command->info('- Admin: 1 user (admin@alfawz.com / admin123)');
        $this->command->info('- Teachers: ' . User::role('teacher')->count() . ' users (teacher123)');
        $this->command->info('- Students: ' . User::role('student')->count() . ' users (student123)');
        $this->command->info('');
        $this->command->info('Demo Credentials:');
        $this->command->info('Admin: admin@alfawz.com / admin123');
        $this->command->info('Teacher: ahmed@alfawz.com / teacher123');
        $this->command->info('Student: aisha@alfawz.com / student123');
    }
}