<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paystack_reference',
        'amount_kobo',
        'currency',
        'status',
        'payment_method',
        'metadata',
        'paid_at'
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the user who made this payment.
     *
     * @return BelongsTo User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if payment is completed.
     *
     * @return bool True if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending.
     *
     * @return bool True if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed.
     *
     * @return bool True if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get payment amount in main currency unit (Naira/Dollars).
     *
     * @return float Amount in main currency
     */
    public function getAmount(): float
    {
        return $this->amount_kobo / 100;
    }

    /**
     * Get formatted amount with currency symbol.
     *
     * @return string Formatted amount
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->getAmount();
        $symbol = $this->getCurrencySymbol();
        
        return $symbol . number_format($amount, 2);
    }

    /**
     * Get currency symbol.
     *
     * @return string Currency symbol
     */
    public function getCurrencySymbol(): string
    {
        switch ($this->currency) {
            case 'NGN':
                return '₦';
            case 'USD':
                return '$';
            case 'EUR':
                return '€';
            case 'GBP':
                return '£';
            default:
                return $this->currency . ' ';
        }
    }

    /**
     * Get payment plan from metadata.
     *
     * @return string|null Payment plan
     */
    public function getPlan(): ?string
    {
        return $this->metadata['plan'] ?? null;
    }

    /**
     * Get failure reason from metadata.
     *
     * @return string|null Failure reason
     */
    public function getFailureReason(): ?string
    {
        return $this->metadata['failure_reason'] ?? null;
    }

    /**
     * Get Paystack transaction data from metadata.
     *
     * @return array|null Paystack data
     */
    public function getPaystackData(): ?array
    {
        return $this->metadata['paystack_data'] ?? null;
    }

    /**
     * Check if payment is for a yearly plan.
     *
     * @return bool True if yearly plan
     */
    public function isYearlyPlan(): bool
    {
        return $this->getPlan() === 'yearly';
    }

    /**
     * Check if payment is for a monthly plan.
     *
     * @return bool True if monthly plan
     */
    public function isMonthlyPlan(): bool
    {
        return $this->getPlan() === 'monthly';
    }

    /**
     * Get payment duration in days based on plan.
     *
     * @return int Duration in days
     */
    public function getPlanDuration(): int
    {
        return $this->isYearlyPlan() ? 365 : 30;
    }

    /**
     * Get payment status with human-readable description.
     *
     * @return array Status info
     */
    public function getStatusInfo(): array
    {
        switch ($this->status) {
            case 'completed':
                return [
                    'status' => 'completed',
                    'label' => 'Completed',
                    'description' => 'Payment was successful',
                    'color' => 'green'
                ];
            case 'pending':
                return [
                    'status' => 'pending',
                    'label' => 'Pending',
                    'description' => 'Payment is being processed',
                    'color' => 'yellow'
                ];
            case 'failed':
                return [
                    'status' => 'failed',
                    'label' => 'Failed',
                    'description' => $this->getFailureReason() ?? 'Payment was unsuccessful',
                    'color' => 'red'
                ];
            default:
                return [
                    'status' => $this->status,
                    'label' => ucfirst($this->status),
                    'description' => 'Unknown payment status',
                    'color' => 'gray'
                ];
        }
    }

    /**
     * Get days since payment was made.
     *
     * @return int|null Days since payment (null if not paid)
     */
    public function getDaysSincePaid(): ?int
    {
        if (!$this->paid_at) {
            return null;
        }
        
        return (int) $this->paid_at->diffInDays(now());
    }

    /**
     * Check if payment is recent (within specified days).
     *
     * @param int $days Number of days to consider recent
     * @return bool True if recent
     */
    public function isRecent(int $days = 7): bool
    {
        $daysSince = $this->getDaysSincePaid();
        return $daysSince !== null && $daysSince <= $days;
    }

    /**
     * Scope to filter completed payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter pending payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter failed payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by currency.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency Currency code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', strtoupper($currency));
    }

    /**
     * Scope to filter by plan type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $plan Plan type (monthly, yearly)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPlan($query, string $plan)
    {
        return $query->whereJsonContains('metadata->plan', $plan);
    }

    /**
     * Scope to filter recent payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days to consider recent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('paid_at', '>=', now()->subDays($days));
    }
}