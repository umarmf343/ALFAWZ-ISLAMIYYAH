<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrgSetting;

class OrgSettingSeeder extends Seeder
{
    /**
     * Seed the org_settings table with default values.
     * Creates a single row with tajweed_default enabled by default.
     */
    public function run(): void
    {
        OrgSetting::firstOrCreate(
            [], // No conditions - just ensure one row exists
            ['tajweed_default' => true] // Default values
        );
    }
}