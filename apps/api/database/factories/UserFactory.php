<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating User model instances with realistic demo data.
 * Includes proper password hashing and email verification for testing.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state for demo users.
     * Creates users with hashed passwords and verified email addresses.
     *
     * @return array<string, mixed> User attributes array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // Demo password for all users
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     * Useful for testing email verification flows.
     *
     * @return static Factory instance for method chaining
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with admin-specific attributes.
     * Sets up admin user with appropriate naming convention.
     *
     * @return static Factory instance for method chaining
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin ' . fake()->lastName(),
            'email' => 'admin.' . Str::lower(fake()->lastName()) . '@alfawz.test',
        ]);
    }

    /**
     * Create a user with teacher-specific attributes.
     * Sets up teacher user with appropriate naming and email pattern.
     *
     * @return static Factory instance for method chaining
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Teacher ' . fake()->firstName() . ' ' . fake()->lastName(),
            'email' => 'teacher.' . Str::lower(fake()->lastName()) . '@alfawz.test',
        ]);
    }

    /**
     * Create a user with student-specific attributes.
     * Sets up student user with appropriate naming and email pattern.
     *
     * @return static Factory instance for method chaining
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->firstName() . ' ' . fake()->lastName(),
            'email' => 'student.' . Str::lower(fake()->lastName()) . '@alfawz.test',
        ]);
    }
}
