<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

// database/seeders/RolesAndAdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * RolesAndAdminSeeder creates base roles and a default admin account.
 */
class RolesAndAdminSeeder extends Seeder
{
    /**
     * run ensures 'admin', 'teacher', and 'student' roles exist, and seeds an admin user.
     */
    public function run(): void
    {
        foreach (['admin','teacher','student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@alfawz.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}