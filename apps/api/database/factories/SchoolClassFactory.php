<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * SchoolClassFactory creates demo classes with a teacher owner.
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    /** definition returns fake attributes for SchoolClass. */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->words(2, true) . ' ' . $this->faker->randomElement(['A','B','C']),
            'level' => $this->faker->numberBetween(1,3),
            'teacher_id' => User::role('teacher')->inRandomOrder()->first()?->id,
            'description' => $this->faker->sentence(8),
        ];
    }
}