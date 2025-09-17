<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Seeder for creating user roles and permissions.
 * Ensures admin, teacher, and student roles exist with appropriate permissions.
 */
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates roles and permissions for the Al Fawz Qur'an Institute system.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Class management
            'view classes',
            'create classes',
            'edit classes',
            'delete classes',
            'manage class members',
            
            // Assignment management
            'view assignments',
            'create assignments',
            'edit assignments',
            'delete assignments',
            'publish assignments',
            'grade assignments',
            
            // Submission management
            'view submissions',
            'create submissions',
            'edit submissions',
            'delete submissions',
            'provide feedback',
            
            // Quran progress
            'view progress',
            'edit progress',
            'view leaderboard',
            
            // Payment management
            'view payments',
            'manage payments',
            'view invoices',
            
            // System administration
            'manage settings',
            'manage feature flags',
            'view analytics',
            'manage whisper jobs',
            'access admin tools',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Teacher role with teaching-related permissions
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherRole->givePermissionTo([
            'view users',
            'view classes',
            'create classes',
            'edit classes',
            'manage class members',
            'view assignments',
            'create assignments',
            'edit assignments',
            'delete assignments',
            'publish assignments',
            'grade assignments',
            'view submissions',
            'edit submissions',
            'provide feedback',
            'view progress',
            'edit progress',
            'view leaderboard',
            'view payments',
            'view invoices',
        ]);

        // Create Student role with limited permissions
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentRole->givePermissionTo([
            'view classes',
            'view assignments',
            'create submissions',
            'edit submissions',
            'view submissions',
            'view progress',
            'view leaderboard',
            'view payments',
            'view invoices',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('- Admin: ' . $adminRole->permissions->count() . ' permissions');
        $this->command->info('- Teacher: ' . $teacherRole->permissions->count() . ' permissions');
        $this->command->info('- Student: ' . $studentRole->permissions->count() . ' permissions');
    }
}