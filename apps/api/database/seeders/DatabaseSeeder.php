<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder is the root seeder that orchestrates all child seeders.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * run executes all seeders in the correct order to populate demo data.
     */
    public function run(): void
    {
        $this->call([
            RolesAndAdminSeeder::class,
            TeachersStudentsSeeder::class,
            DemoClassesAndAssignmentsSeeder::class,
        ]);
    }
}
