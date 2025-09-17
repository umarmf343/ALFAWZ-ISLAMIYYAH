<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Invoice model instances with demo data.
 * Creates realistic payment invoices with Paystack references and various statuses.
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     * Generates invoice with random amount, Paystack reference, and status.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomElement([2500, 5000, 7500, 10000, 15000]); // NGN amounts
        $status = $this->faker->randomElement(['pending', 'paid', 'failed', 'cancelled']);
        
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'amount' => $amount,
            'currency' => 'NGN',
            'status' => $status,
            'paystack_reference' => 'ref_' . $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'paystack_access_code' => $status !== 'pending' ? 'ac_' . $this->faker->regexify('[a-z0-9]{16}') : null,
            'paystack_authorization_code' => $status === 'paid' ? 'auth_' . $this->faker->regexify('[a-z0-9]{12}') : null,
            'payment_method' => $status === 'paid' ? $this->faker->randomElement(['card', 'bank_transfer', 'ussd']) : null,
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'expires_at' => $status === 'pending' ? $this->faker->dateTimeBetween('now', '+24 hours') : null,
            'metadata' => [
                'student_name' => $this->faker->name(),
                'class_level' => $this->faker->randomElement([1, 2, 3]),
                'payment_source' => 'web_app',
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent()
            ]
        ];
    }

    /**
     * Create a paid invoice state.
     * Sets status to paid with payment details and authorization code.
     *
     * @return static
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paystack_access_code' => 'ac_' . $this->faker->regexify('[a-z0-9]{16}'),
            'paystack_authorization_code' => 'auth_' . $this->faker->regexify('[a-z0-9]{12}'),
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'ussd']),
            'paid_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'expires_at' => null
        ]);
    }

    /**
     * Create a pending invoice state.
     * Sets status to pending with expiration time and no payment details.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paystack_access_code' => null,
            'paystack_authorization_code' => null,
            'payment_method' => null,
            'paid_at' => null,
            'expires_at' => $this->faker->dateTimeBetween('now', '+24 hours')
        ]);
    }

    /**
     * Create a failed invoice state.
     * Sets status to failed with access code but no authorization.
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paystack_access_code' => 'ac_' . $this->faker->regexify('[a-z0-9]{16}'),
            'paystack_authorization_code' => null,
            'payment_method' => null,
            'paid_at' => null,
            'expires_at' => null
        ]);
    }

    /**
     * Create a cancelled invoice state.
     * Sets status to cancelled with no payment processing details.
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'paystack_access_code' => null,
            'paystack_authorization_code' => null,
            'payment_method' => null,
            'paid_at' => null,
            'expires_at' => null
        ]);
    }

    /**
     * Create an invoice for a specific plan amount.
     * Sets amount based on common subscription tiers.
     *
     * @param int $amount Amount in kobo (NGN minor unit)
     * @return static
     */
    public function forAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount
        ]);
    }

    /**
     * Create an invoice for a specific user.
     * Associates invoice with given user ID.
     *
     * @param int $userId User ID to associate with invoice
     * @return static
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId
        ]);
    }

    /**
     * Create an invoice for a specific plan.
     * Associates invoice with given plan ID.
     *
     * @param int $planId Plan ID to associate with invoice
     * @return static
     */
    public function forPlan(int $planId): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $planId
        ]);
    }
}