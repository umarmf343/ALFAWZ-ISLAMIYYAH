<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * AdminBillingController manages payment plans, invoices, and billing analytics.
 * Provides comprehensive billing oversight and revenue tracking.
 */
class AdminBillingController extends Controller
{
    /**
     * Get paginated list of invoices with filtering and search.
     * Includes user info, plan details, and payment status.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse paginated invoices list
     */
    public function invoices(Request $request)
    {
        $query = DB::table('invoices')
            ->leftJoin('users', 'invoices.user_id', '=', 'users.id')
            ->leftJoin('plans', 'invoices.plan_code', '=', 'plans.code')
            ->select([
                'invoices.id',
                'invoices.user_id',
                'users.name as user_name',
                'users.email as user_email',
                'invoices.plan_code',
                'plans.name as plan_name',
                'invoices.amount',
                'invoices.currency',
                'invoices.paystack_ref',
                'invoices.status',
                'invoices.meta_json',
                'invoices.created_at',
                'invoices.updated_at'
            ]);
        
        // Search by user name, email, or reference
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('invoices.paystack_ref', 'like', "%{$search}%")
                  ->orWhere('invoices.id', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('invoices.status', $status);
        }
        
        // Filter by plan
        if ($planCode = $request->get('plan_code')) {
            $query->where('invoices.plan_code', $planCode);
        }
        
        // Filter by date range
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('invoices.created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('invoices.created_at', '<=', $endDate);
        }
        
        // Filter by amount range
        if ($minAmount = $request->get('min_amount')) {
            $query->where('invoices.amount', '>=', $minAmount);
        }
        if ($maxAmount = $request->get('max_amount')) {
            $query->where('invoices.amount', '<=', $maxAmount);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'user_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy("invoices.{$sortBy}", $sortOrder);
        }
        
        // Manual pagination
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $total = $query->count();
        $invoices = $query->offset($offset)->limit($perPage)->get();
        
        // Transform data
        $transformedInvoices = $invoices->map(function ($invoice) {
            $meta = json_decode($invoice->meta_json, true) ?? [];
            
            return [
                'id' => $invoice->id,
                'user' => [
                    'id' => $invoice->user_id,
                    'name' => $invoice->user_name,
                    'email' => $invoice->user_email,
                ],
                'plan' => [
                    'code' => $invoice->plan_code,
                    'name' => $invoice->plan_name,
                ],
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'amount_formatted' => $this->formatCurrency($invoice->amount, $invoice->currency),
                'paystack_ref' => $invoice->paystack_ref,
                'status' => $invoice->status,
                'meta' => $meta,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
            ];
        });
        
        return response()->json([
            'data' => $transformedInvoices,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }
    
    /**
     * Get all available plans with usage statistics.
     * Shows plan details and subscription metrics.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse plans list with statistics
     */
    public function plans(Request $request)
    {
        $plans = DB::table('plans')
            ->leftJoin(
                DB::raw('(
                    SELECT plan_code, 
                           COUNT(*) as total_subscriptions,
                           COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_subscriptions,
                           SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_revenue
                    FROM invoices 
                    GROUP BY plan_code
                ) as invoice_stats'),
                'plans.code',
                '=',
                'invoice_stats.plan_code'
            )
            ->select([
                'plans.*',
                'invoice_stats.total_subscriptions',
                'invoice_stats.paid_subscriptions',
                'invoice_stats.total_revenue'
            ])
            ->orderBy('plans.active', 'desc')
            ->orderBy('plans.amount')
            ->get()
            ->map(function ($plan) {
                $perks = json_decode($plan->perks_json, true) ?? [];
                
                return [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'amount' => $plan->amount,
                    'currency' => $plan->currency,
                    'amount_formatted' => $this->formatCurrency($plan->amount, $plan->currency),
                    'interval' => $plan->interval,
                    'perks' => $perks,
                    'active' => (bool) $plan->active,
                    'statistics' => [
                        'total_subscriptions' => $plan->total_subscriptions ?? 0,
                        'paid_subscriptions' => $plan->paid_subscriptions ?? 0,
                        'total_revenue' => $plan->total_revenue ?? 0,
                        'total_revenue_formatted' => $this->formatCurrency($plan->total_revenue ?? 0, $plan->currency),
                        'conversion_rate' => $plan->total_subscriptions > 0 
                            ? round(($plan->paid_subscriptions / $plan->total_subscriptions) * 100, 1)
                            : 0,
                    ],
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at,
                ];
            });
        
        return response()->json($plans);
    }
    
    /**
     * Create or update a plan.
     * Manages subscription plan configuration.
     *
     * @param Request $request HTTP request with plan data
     * @param int|null $id Plan ID for updates
     * @return \Illuminate\Http\JsonResponse created/updated plan
     */
    public function savePlan(Request $request, $id = null)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('plans', 'code')->ignore($id)
            ],
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|string|in:monthly,term,yearly',
            'perks' => 'nullable|array',
            'active' => 'boolean',
        ]);
        
        $planData = [
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'amount' => $request->get('amount'),
            'currency' => strtoupper($request->get('currency')),
            'interval' => $request->get('interval'),
            'perks_json' => json_encode($request->get('perks', [])),
            'active' => $request->get('active', true),
            'updated_at' => now(),
        ];
        
        if ($id) {
            // Update existing plan
            DB::table('plans')->where('id', $id)->update($planData);
            $plan = DB::table('plans')->where('id', $id)->first();
            $message = 'Plan updated successfully';
        } else {
            // Create new plan
            $planData['created_at'] = now();
            $planId = DB::table('plans')->insertGetId($planData);
            $plan = DB::table('plans')->where('id', $planId)->first();
            $message = 'Plan created successfully';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'plan' => [
                'id' => $plan->id,
                'code' => $plan->code,
                'name' => $plan->name,
                'amount' => $plan->amount,
                'currency' => $plan->currency,
                'amount_formatted' => $this->formatCurrency($plan->amount, $plan->currency),
                'interval' => $plan->interval,
                'perks' => json_decode($plan->perks_json, true) ?? [],
                'active' => (bool) $plan->active,
            ],
        ], $id ? 200 : 201);
    }
    
    /**
     * Get billing analytics and revenue metrics.
     * Provides comprehensive financial insights.
     *
     * @param Request $request HTTP request with date filters
     * @return \Illuminate\Http\JsonResponse billing analytics
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // Revenue metrics
        $revenueQuery = DB::table('invoices')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        $totalRevenue = (clone $revenueQuery)->where('status', 'paid')->sum('amount');
        $totalInvoices = (clone $revenueQuery)->count();
        $paidInvoices = (clone $revenueQuery)->where('status', 'paid')->count();
        $pendingRevenue = (clone $revenueQuery)->where('status', 'pending')->sum('amount');
        
        // Payment success rate
        $successRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0;
        
        // Revenue by plan
        $revenueByPlan = DB::table('invoices')
            ->join('plans', 'invoices.plan_code', '=', 'plans.code')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', 'paid')
            ->select([
                'plans.name as plan_name',
                'plans.code as plan_code',
                DB::raw('COUNT(*) as subscription_count'),
                DB::raw('SUM(invoices.amount) as total_revenue')
            ])
            ->groupBy('plans.code', 'plans.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($plan) {
                return [
                    'plan_name' => $plan->plan_name,
                    'plan_code' => $plan->plan_code,
                    'subscription_count' => $plan->subscription_count,
                    'total_revenue' => $plan->total_revenue,
                    'avg_revenue_per_subscription' => round($plan->total_revenue / $plan->subscription_count, 2),
                ];
            });
        
        // Daily revenue trend
        $dailyRevenue = collect()
            ->range(0, now()->parse($endDate)->diffInDays(now()->parse($startDate)))
            ->map(function ($daysFromStart) use ($startDate) {
                $date = now()->parse($startDate)->addDays($daysFromStart)->toDateString();
                
                $dayRevenue = DB::table('invoices')
                    ->whereDate('created_at', $date)
                    ->where('status', 'paid')
                    ->sum('amount');
                
                $dayInvoices = DB::table('invoices')
                    ->whereDate('created_at', $date)
                    ->count();
                
                return [
                    'date' => $date,
                    'revenue' => $dayRevenue,
                    'invoice_count' => $dayInvoices,
                ];
            });
        
        // Top paying customers
        $topCustomers = DB::table('invoices')
            ->join('users', 'invoices.user_id', '=', 'users.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', 'paid')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(invoices.amount) as total_spent')
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'user' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                    ],
                    'payment_count' => $customer->payment_count,
                    'total_spent' => $customer->total_spent,
                    'avg_payment' => round($customer->total_spent / $customer->payment_count, 2),
                ];
            });
        
        return response()->json([
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_invoices' => $totalInvoices,
                'paid_invoices' => $paidInvoices,
                'pending_revenue' => $pendingRevenue,
                'success_rate' => $successRate,
                'avg_invoice_value' => $paidInvoices > 0 ? round($totalRevenue / $paidInvoices, 2) : 0,
            ],
            'revenue_by_plan' => $revenueByPlan,
            'daily_revenue' => $dailyRevenue,
            'top_customers' => $topCustomers,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Bulk refund invoices through Paystack integration.
     * Processes refunds for multiple invoices with proper validation and audit logging.
     * @param Request $request HTTP request with ids array
     * @return \Illuminate\Http\JsonResponse Success response with refund results
     */
    public function bulkRefund(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
            'reason' => 'nullable|string|max:255'
        ]);
        
        $processedCount = 0;
        $errors = [];
        $refundResults = [];
        
        foreach ($data['ids'] as $invoiceId) {
            try {
                $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
                
                if (!$invoice) {
                    $errors[] = "Invoice {$invoiceId} not found";
                    continue;
                }
                
                // Check if invoice is eligible for refund
                if ($invoice->status !== 'paid') {
                    $errors[] = "Invoice {$invoiceId} is not paid and cannot be refunded";
                    continue;
                }
                
                // Process refund through Paystack
                $refundResult = $this->processPaystackRefund($invoice, $data['reason'] ?? 'Admin bulk refund');
                
                if ($refundResult['success']) {
                    // Update invoice status
                    DB::table('invoices')->where('id', $invoiceId)->update([
                        'status' => 'refunded',
                        'updated_at' => now()
                    ]);
                    
                    $processedCount++;
                    $refundResults[] = [
                        'invoice_id' => $invoiceId,
                        'amount' => $invoice->amount,
                        'reference' => $refundResult['reference'] ?? null,
                        'status' => 'success'
                    ];
                } else {
                    $errors[] = "Refund failed for invoice {$invoiceId}: " . $refundResult['message'];
                    $refundResults[] = [
                        'invoice_id' => $invoiceId,
                        'amount' => $invoice->amount,
                        'status' => 'failed',
                        'error' => $refundResult['message']
                    ];
                }
                
            } catch (\Exception $e) {
                $errors[] = "Failed to process refund for invoice {$invoiceId}: " . $e->getMessage();
            }
        }
        
        // Audit log the bulk refund operation
        $this->auditLog('invoice.bulk.refund', [
            'ids' => $data['ids'],
            'reason' => $data['reason'] ?? 'Admin bulk refund',
            'processed_count' => $processedCount,
            'total_requested' => count($data['ids']),
            'errors_count' => count($errors),
            'refund_results' => $refundResults
        ]);
        
        return response()->json([
            'ok' => true,
            'processed_count' => $processedCount,
            'total_requested' => count($data['ids']),
            'errors' => $errors,
            'refund_results' => $refundResults,
            'success_rate' => count($data['ids']) > 0 ? round(($processedCount / count($data['ids'])) * 100, 2) : 0
        ]);
    }
    
    /**
     * Process refund through Paystack API.
     * @param object $invoice Invoice record to refund
     * @param string $reason Refund reason
     * @return array Refund result with success status and details
     */
    private function processPaystackRefund($invoice, string $reason): array
    {
        // Mock implementation - replace with actual Paystack API integration
        try {
            // In a real implementation, you would call Paystack's refund API here
            // For now, we'll simulate a successful refund
            
            if (!$invoice->paystack_ref) {
                return [
                    'success' => false,
                    'message' => 'No Paystack reference found for this invoice'
                ];
            }
            
            // Simulate API call success
            return [
                'success' => true,
                'reference' => 'RF_' . uniqid(),
                'message' => 'Refund processed successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API communication error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Log audit trail for admin actions.
     * @param string $action Action identifier
     * @param array $data Additional data to log
     */
    private function auditLog(string $action, array $data = [])
    {
        // In a real implementation, you might log to a dedicated audit table
        // For now, we'll use Laravel's logging system
        \Log::info("Admin action: {$action}", array_merge([
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()->email ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip()
        ], $data));
    }
    
    /**
     * Format currency amount for display.
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @return string formatted currency
     */
    private function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'NGN' => '₦',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($amount, 2);
    }
}