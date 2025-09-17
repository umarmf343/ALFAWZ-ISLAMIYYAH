<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

// database/seeders/TeachersStudentsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * TeachersStudentsSeeder seeds demo teacher and student accounts.
 */
class TeachersStudentsSeeder extends Seeder
{
    /**
     * run creates 2 teachers and 3 students with a shared password.
     */
    public function run(): void
    {
        $users = [
            ['email'=>'teacher1@alfawz.com','name'=>'Teacher One','role'=>'teacher'],
            ['email'=>'teacher2@alfawz.com','name'=>'Teacher Two','role'=>'teacher'],
            ['email'=>'student1@alfawz.com','name'=>'Student One','role'=>'student'],
            ['email'=>'student2@alfawz.com','name'=>'Student Two','role'=>'student'],
            ['email'=>'student3@alfawz.com','name'=>'Student Three','role'=>'student'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email'=>$u['email']],
                [
                    'name'=>$u['name'],
                    'password'=>Hash::make('password123'),
                    'email_verified_at'=>now(),
                ]
            );
            if (!$user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }
        }
    }
}