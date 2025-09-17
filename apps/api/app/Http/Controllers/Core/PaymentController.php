<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Initialize a new payment transaction with Paystack.
     * Creates payment record and returns Paystack authorization URL.
     *
     * @param Request $request HTTP request with payment data
     * @return \Illuminate\Http\JsonResponse Payment initialization response
     */
    public function init(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100', // Minimum 1 Naira (100 kobo)
            'currency' => 'sometimes|in:NGN,USD',
            'plan' => 'required|in:monthly,yearly',
            'callback_url' => 'sometimes|url'
        ]);

        $currency = $validated['currency'] ?? 'NGN';
        $amountKobo = (int) ($validated['amount'] * 100); // Convert to kobo
        $reference = 'alfawz_' . Str::random(16) . '_' . time();
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'paystack_reference' => $reference,
            'amount_kobo' => $amountKobo,
            'currency' => $currency,
            'status' => 'pending',
            'metadata' => [
                'plan' => $validated['plan'],
                'user_email' => $user->email,
                'user_name' => $user->name
            ]
        ]);

        // Initialize transaction with Paystack
        $paystackResponse = $this->initializePaystackTransaction([
            'email' => $user->email,
            'amount' => $amountKobo,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $validated['callback_url'] ?? config('app.url') . '/payment/callback',
            'metadata' => [
                'user_id' => $user->id,
                'plan' => $validated['plan'],
                'payment_id' => $payment->id
            ]
        ]);

        if (!$paystackResponse['success']) {
            $payment->update(['status' => 'failed']);
            
            return response()->json([
                'error' => 'Payment initialization failed',
                'message' => $paystackResponse['message'] ?? 'Unable to initialize payment'
            ], 422);
        }

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'reference' => $reference,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'status' => 'pending'
            ],
            'authorization_url' => $paystackResponse['data']['authorization_url'],
            'access_code' => $paystackResponse['data']['access_code'],
            'message' => 'Payment initialized successfully'
        ]);
    }

    /**
     * Handle Paystack webhook notifications.
     * Processes payment status updates and activates premium features.
     *
     * @param Request $request Webhook request from Paystack
     * @return \Illuminate\Http\JsonResponse Webhook response
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $body, config('services.paystack.secret_key'));
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid Paystack webhook signature', [
                'signature' => $signature,
                'expected' => $expectedSignature
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();
        
        Log::info('Paystack webhook received', [
            'event' => $event['event'] ?? 'unknown',
            'reference' => $event['data']['reference'] ?? 'unknown'
        ]);

        // Handle different event types
        switch ($event['event']) {
            case 'charge.success':
                $this->handleSuccessfulPayment($event['data']);
                break;
                
            case 'charge.failed':
                $this->handleFailedPayment($event['data']);
                break;
                
            default:
                Log::info('Unhandled Paystack event', ['event' => $event['event']]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Initialize transaction with Paystack API.
     *
     * @param array $data Transaction data
     * @return array API response
     */
    private function initializePaystackTransaction(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json'
            ])->post('https://api.paystack.co/transaction/initialize', $data);

            $responseData = $response->json();
            
            Log::info('Paystack initialization response', [
                'status' => $response->status(),
                'success' => $responseData['status'] ?? false
            ]);

            return [
                'success' => $responseData['status'] ?? false,
                'data' => $responseData['data'] ?? [],
                'message' => $responseData['message'] ?? 'Unknown error'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack API error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment service unavailable'
            ];
        }
    }

    /**
     * Handle successful payment webhook.
     *
     * @param array $data Payment data from webhook
     * @return void
     */
    private function handleSuccessfulPayment(array $data): void
    {
        $reference = $data['reference'];
        $payment = Payment::where('paystack_reference', $reference)->first();
        
        if (!$payment) {
            Log::warning('Payment not found for successful transaction', ['reference' => $reference]);
            return;
        }

        if ($payment->status === 'completed') {
            Log::info('Payment already processed', ['reference' => $reference]);
            return;
        }

        // Update payment record
        $payment->update([
            'status' => 'completed',
            'payment_method' => $data['channel'] ?? 'unknown',
            'paid_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'paystack_data' => $data
            ])
        ]);

        // Activate premium features for user
        $this->activatePremiumFeatures($payment);
        
        Log::info('Payment processed successfully', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount_kobo
        ]);
    }

    /**
     * Handle failed payment webhook.
     *
     * @param array $data Payment data from webhook
     * @return void
     */
    private function handleFailedPayment(array $data): void
    {
        $reference = $data['reference'];
        $payment = Payment::where('paystack_reference', $reference)->first();
        
        if (!$payment) {
            Log::warning('Payment not found for failed transaction', ['reference' => $reference]);
            return;
        }

        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'failure_reason' => $data['gateway_response'] ?? 'Unknown failure',
                'paystack_data' => $data
            ])
        ]);
        
        Log::info('Payment marked as failed', [
            'payment_id' => $payment->id,
            'reason' => $data['gateway_response'] ?? 'Unknown'
        ]);
    }

    /**
     * Activate premium features for user based on payment plan.
     *
     * @param Payment $payment The completed payment
     * @return void
     */
    private function activatePremiumFeatures(Payment $payment): void
    {
        $user = $payment->user;
        $plan = $payment->metadata['plan'] ?? 'monthly';
        
        // Calculate subscription end date
        $endDate = $plan === 'yearly' ? now()->addYear() : now()->addMonth();
        
        // Assign premium role if not already assigned
        if (!$user->hasRole('premium')) {
            $user->assignRole('premium');
        }
        
        // Update user's premium subscription details
        $user->update([
            'premium_until' => $endDate,
            'premium_plan' => $plan
        ]);
        
        Log::info('Premium features activated', [
            'user_id' => $user->id,
            'plan' => $plan,
            'expires_at' => $endDate->toISOString()
        ]);
    }

    /**
     * Get payment history for the authenticated user.
     *
     * @param Request $request HTTP request (authenticated)
     * @return \Illuminate\Http\JsonResponse Payment history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $payments = Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'reference' => $payment->paystack_reference,
                    'amount' => $payment->amount_kobo / 100,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'plan' => $payment->metadata['plan'] ?? 'unknown',
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at
                ];
            });

        return response()->json([
            'payments' => $payments
        ]);
    }

    /**
     * Get current subscription details for the authenticated user.
     *
     * @param Request $request HTTP request (authenticated)
     * @return \Illuminate\Http\JsonResponse Subscription details
     */
    public function subscription(Request $request)
    {
        $user = $request->user();
        
        $currentSubscription = null;
        if ($user->hasRole('premium') && $user->premium_until) {
            $currentSubscription = [
                'plan' => $user->premium_plan,
                'status' => now()->lt($user->premium_until) ? 'active' : 'expired',
                'expires_at' => $user->premium_until,
                'days_remaining' => now()->diffInDays($user->premium_until, false)
            ];
        }

        return response()->json([
            'subscription' => $currentSubscription,
            'is_premium' => $user->hasRole('premium'),
            'premium_until' => $user->premium_until
        ]);
    }

    /**
     * Get available subscription plans.
     *
     * @return \Illuminate\Http\JsonResponse Available plans
     */
    public function plans()
    {
        $plans = [
            [
                'id' => 'monthly',
                'name' => 'Monthly Premium',
                'description' => 'Access to all premium features for one month',
                'price' => 999, // 9.99 USD in kobo
                'currency' => 'USD',
                'duration' => 30,
                'duration_unit' => 'days',
                'features' => [
                    'Unlimited class creation',
                    'Advanced analytics',
                    'Priority support',
                    'Custom branding',
                    'Export capabilities'
                ],
                'popular' => false
            ],
            [
                'id' => 'yearly',
                'name' => 'Yearly Premium',
                'description' => 'Access to all premium features for one year (2 months free)',
                'price' => 9999, // 99.99 USD in kobo (equivalent to 10 months)
                'currency' => 'USD',
                'duration' => 365,
                'duration_unit' => 'days',
                'features' => [
                    'Unlimited class creation',
                    'Advanced analytics',
                    'Priority support',
                    'Custom branding',
                    'Export capabilities',
                    '2 months free',
                    'Best value'
                ],
                'popular' => true
            ]
        ];

        return response()->json([
            'plans' => $plans
        ]);
    }

    /**
     * Cancel user's subscription.
     *
     * @param Request $request HTTP request (authenticated)
     * @return \Illuminate\Http\JsonResponse Cancellation result
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('premium')) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        // Remove premium role
        $user->removeRole('premium');
        
        // Clear premium subscription details
        $user->update([
            'premium_until' => null,
            'premium_plan' => null
        ]);

        Log::info('Subscription cancelled', [
            'user_id' => $user->id,
            'user_email' => $user->email
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'cancelled_at' => now()->toISOString()
        ]);
    }
}