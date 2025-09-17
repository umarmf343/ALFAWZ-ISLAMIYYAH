<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds to create roles and permissions.
     * Sets up the three main roles: admin, teacher, student with their permissions.
     *
     * @return void
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User management
            'manage-users',
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            
            // Class management
            'manage-classes',
            'view-classes',
            'create-classes',
            'update-classes',
            'delete-classes',
            'join-classes',
            
            // Assignment management
            'manage-assignments',
            'view-assignments',
            'create-assignments',
            'update-assignments',
            'delete-assignments',
            'submit-assignments',
            
            // Feedback management
            'manage-feedback',
            'view-feedback',
            'create-feedback',
            'update-feedback',
            'delete-feedback',
            
            // Submission management
            'manage-submissions',
            'view-submissions',
            'grade-submissions',
            
            // System management
            'manage-system',
            'view-analytics',
            'manage-payments',
            'view-reports',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - full access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);
        
        // Teacher role - can manage classes, assignments, and feedback
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherPermissions = [
            'view-users',
            'view-classes',
            'create-classes',
            'update-classes',
            'delete-classes',
            'view-assignments',
            'create-assignments',
            'update-assignments',
            'delete-assignments',
            'view-feedback',
            'create-feedback',
            'update-feedback',
            'delete-feedback',
            'view-submissions',
            'grade-submissions',
            'manage-payments',
        ];
        $teacherRole->syncPermissions($teacherPermissions);
        
        // Student role - can view and submit
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentPermissions = [
            'view-classes',
            'join-classes',
            'view-assignments',
            'submit-assignments',
            'view-feedback',
            'view-submissions',
        ];
        $studentRole->syncPermissions($studentPermissions);
        
        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: admin, teacher, student');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}