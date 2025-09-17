<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    /**
     * Generate a signed GET URL for secure audio playback.
     * Creates temporary URLs for hotspot audio files stored in S3.
     *
     * @param Request $request HTTP request with path parameter
     * @return \Illuminate\Http\JsonResponse Signed URL for audio access
     */
    public function signedGet(Request $request)
    {
        try {
            $validated = $request->validate([
                'path' => 'required|string|max:255'
            ]);

            $path = $validated['path'];
            
            // Security: Ensure path is within allowed directories
            $allowedPrefixes = ['hotspots/', 'submissions/', 'feedback/'];
            $isAllowed = false;
            
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'Path not allowed for signed access'
                ], 403);
            }

            $disk = Storage::disk('s3');
            
            // Check if file exists
            if (!$disk->exists($path)) {
                return response()->json([
                    'error' => 'File not found',
                    'message' => 'The requested audio file does not exist'
                ], 404);
            }

            // Generate temporary URL valid for 10 minutes
            $url = $disk->temporaryUrl($path, now()->addMinutes(10));

            return response()->json([
                'url' => $url,
                'expires_at' => now()->addMinutes(10)->toISOString()
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Failed to generate signed URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a signed PUT URL for secure audio uploads.
     * Creates temporary upload URLs for client-side file uploads to S3.
     *
     * @param Request $request HTTP request with filename and content_type
     * @return \Illuminate\Http\JsonResponse Signed upload URL and file path
     */
    public function signedPut(Request $request)
    {
        try {
            $validated = $request->validate([
                'filename' => 'required|string|max:255',
                'content_type' => 'required|string|in:audio/webm,audio/mp3,audio/wav,audio/m4a',
                'folder' => 'sometimes|string|in:hotspots,submissions,feedback'
            ]);

            $folder = $validated['folder'] ?? 'hotspots';
            $filename = $validated['filename'];
            $contentType = $validated['content_type'];
            
            // Generate unique file path
            $timestamp = now()->format('Y/m/d');
            $uniqueId = uniqid();
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $path = "{$folder}/{$timestamp}/{$uniqueId}.{$extension}";

            $disk = Storage::disk('s3');
            
            // Generate presigned PUT URL valid for 15 minutes
            $url = $disk->temporaryUploadUrl($path, now()->addMinutes(15), [
                'ContentType' => $contentType
            ]);

            return response()->json([
                'upload_url' => $url,
                'file_path' => $path,
                'expires_at' => now()->addMinutes(15)->toISOString()
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Failed to generate upload URL: ' . $e->getMessage()
            ], 500);
        }
    }
}