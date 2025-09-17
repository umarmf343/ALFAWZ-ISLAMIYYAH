<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminToolsController extends Controller
{
    /**
     * Simulate Paystack webhook for testing purposes.
     * Sends a test webhook payload to the application's webhook endpoint.
     *
     * @param Request $request HTTP request with webhook_type and reference
     * @return \Illuminate\Http\JsonResponse Test webhook result
     */
    public function testPaystackWebhook(Request $request)
    {
        $request->validate([
            'webhook_type' => 'required|string|in:charge.success,subscription.create,subscription.disable',
            'reference' => 'required|string',
            'amount' => 'nullable|numeric|min:0'
        ]);

        $webhookType = $request->input('webhook_type');
        $reference = $request->input('reference');
        $amount = $request->input('amount', 10000); // Default 100 NGN in kobo

        // Create test webhook payload
        $payload = [
            'event' => $webhookType,
            'data' => [
                'id' => rand(1000000, 9999999),
                'domain' => 'test',
                'status' => 'success',
                'reference' => $reference,
                'amount' => $amount,
                'message' => 'Test webhook from admin tools',
                'gateway_response' => 'Successful',
                'paid_at' => now()->toISOString(),
                'created_at' => now()->toISOString(),
                'channel' => 'card',
                'currency' => 'NGN',
                'customer' => [
                    'id' => rand(100000, 999999),
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com'
                ]
            ]
        ];

        try {
            // Send webhook to our own endpoint
            $webhookUrl = config('app.url') . '/api/payments/webhook';
            $response = Http::timeout(30)->post($webhookUrl, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Test webhook sent successfully',
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Test webhook failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test webhook failed: ' . $e->getMessage(),
                'payload' => $payload
            ], 500);
        }
    }

    /**
     * Verify Paystack transaction reference.
     * Checks the status of a Paystack transaction using their API.
     *
     * @param Request $request HTTP request with reference parameter
     * @return \Illuminate\Http\JsonResponse Verification result
     */
    public function verifyPaystackRef(Request $request)
    {
        $request->validate([
            'reference' => 'required|string'
        ]);

        $reference = $request->input('reference');
        $secretKey = config('services.paystack.secret_key');

        if (!$secretKey) {
            return response()->json([
                'success' => false,
                'message' => 'Paystack secret key not configured'
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json'
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $data = $response->json();

            return response()->json([
                'success' => true,
                'reference' => $reference,
                'paystack_response' => $data,
                'verified_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Paystack verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
                'reference' => $reference
            ], 500);
        }
    }

    /**
     * Check S3 bucket health and functionality.
     * Tests bucket access, write permissions, and temporary URL generation.
     *
     * @return \Illuminate\Http\JsonResponse S3 health check results
     */
    public function s3Health()
    {
        $results = [
            'bucket_access' => false,
            'write_test' => false,
            'temp_url_test' => false,
            'errors' => []
        ];

        try {
            // Test 1: Check if we can access the bucket
            $disk = Storage::disk('s3');
            $testFile = 'health-check/' . Str::random(10) . '.txt';
            $testContent = 'Health check test at ' . now()->toISOString();

            // Test 2: Write test
            if ($disk->put($testFile, $testContent)) {
                $results['bucket_access'] = true;
                $results['write_test'] = true;

                // Test 3: Temporary URL generation
                try {
                    $tempUrl = $disk->temporaryUrl($testFile, now()->addMinutes(5));
                    $results['temp_url_test'] = true;
                    $results['temp_url'] = $tempUrl;
                } catch (\Exception $e) {
                    $results['errors'][] = 'Temporary URL generation failed: ' . $e->getMessage();
                }

                // Clean up test file
                $disk->delete($testFile);
            } else {
                $results['errors'][] = 'Failed to write test file to S3';
            }
        } catch (\Exception $e) {
            $results['errors'][] = 'S3 access failed: ' . $e->getMessage();
        }

        $results['success'] = $results['bucket_access'] && $results['write_test'];
        $results['checked_at'] = now()->toISOString();
        $results['bucket'] = config('filesystems.disks.s3.bucket');
        $results['region'] = config('filesystems.disks.s3.region');

        return response()->json($results);
    }

    /**
     * Generate pre-signed S3 upload URL.
     * Creates a temporary URL for direct file uploads to S3.
     *
     * @param Request $request HTTP request with filename and content_type
     * @return \Illuminate\Http\JsonResponse Pre-signed URL details
     */
    public function signS3Upload(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|max:100'
        ]);

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $folder = $request->input('folder', 'uploads');

        // Sanitize filename
        $sanitizedFilename = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $s3Key = $folder . '/' . date('Y/m/d') . '/' . Str::random(8) . '_' . $sanitizedFilename;

        try {
            $disk = Storage::disk('s3');
            
            // Generate pre-signed PUT URL (valid for 15 minutes)
            $adapter = $disk->getAdapter();
            $client = $adapter->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            $command = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $s3Key,
                'ContentType' => $contentType,
                'ACL' => 'private'
            ]);

            $presignedRequest = $client->createPresignedRequest($command, '+15 minutes');
            $presignedUrl = (string) $presignedRequest->getUri();

            return response()->json([
                'success' => true,
                'upload_url' => $presignedUrl,
                's3_key' => $s3Key,
                'content_type' => $contentType,
                'expires_at' => now()->addMinutes(15)->toISOString(),
                'instructions' => [
                    'method' => 'PUT',
                    'headers' => [
                        'Content-Type' => $contentType
                    ],
                    'note' => 'Upload file directly to the upload_url using PUT method'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('S3 pre-signed URL generation failed', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate pre-signed URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check queue system heartbeat and health.
     * Verifies that the queue system is running and processing jobs.
     *
     * @return \Illuminate\Http\JsonResponse Queue health status
     */
    public function queuePing()
    {
        $results = [
            'queue_connection' => config('queue.default'),
            'heartbeat_found' => false,
            'last_heartbeat' => null,
            'heartbeat_age_seconds' => null,
            'queue_healthy' => false
        ];

        try {
            // Check for heartbeat in cache (set by scheduled command)
            $heartbeat = Cache::get('queue_heartbeat');
            
            if ($heartbeat) {
                $results['heartbeat_found'] = true;
                $results['last_heartbeat'] = $heartbeat;
                
                $heartbeatTime = Carbon::parse($heartbeat);
                $ageSeconds = now()->diffInSeconds($heartbeatTime);
                $results['heartbeat_age_seconds'] = $ageSeconds;
                
                // Consider queue healthy if heartbeat is less than 5 minutes old
                $results['queue_healthy'] = $ageSeconds < 300;
            }

            // Additional queue stats if available
            if (config('queue.default') === 'database') {
                try {
                    $pendingJobs = \DB::table('jobs')->count();
                    $failedJobs = \DB::table('failed_jobs')->count();
                    
                    $results['pending_jobs'] = $pendingJobs;
                    $results['failed_jobs'] = $failedJobs;
                } catch (\Exception $e) {
                    $results['db_error'] = $e->getMessage();
                }
            }

            $results['checked_at'] = now()->toISOString();
            $results['success'] = true;

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
        }

        return response()->json($results);
    }
}