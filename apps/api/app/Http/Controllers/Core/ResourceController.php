<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Resource;
use Illuminate\Support\Facades\Auth;
use Exception;

class ResourceController extends Controller
{
    /**
     * Get all resources with optional filtering by type and search.
     *
     * @param Request $request HTTP request with optional query params
     * @return JsonResponse List of resources
     */
    public function index(Request $request): JsonResponse
    {
        $query = Resource::with('user:id,name,email');
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filter by user role access
        $user = Auth::user();
        if ($user->hasRole('student')) {
            $query->where('is_public', true);
        }
        
        $resources = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));
        
        return response()->json($resources);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:pdf,video,audio,image,document,worksheet',
            'is_public' => 'boolean',
            'tags' => 'nullable|string', // Accept as string, will parse JSON
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename with user ID and timestamp
            $userId = auth()->id();
            $timestamp = now()->format('Y/m/d');
            $filename = $userId . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Determine S3 disk based on file type
            $disk = 's3';
            $folder = 'resources';
            
            if (in_array($request->type, ['audio'])) {
                $disk = 's3-audio';
                $folder = 'audio';
            } elseif (in_array($request->type, ['image'])) {
                $disk = 's3-images';
                $folder = 'images';
            }
            
            // Store file in S3 with organized folder structure
            $path = $folder . '/' . $timestamp . '/' . $filename;
            $storedPath = $file->storeAs($folder . '/' . $timestamp, $filename, $disk);
            $s3Url = Storage::disk($disk)->url($storedPath);

            // Parse tags if provided
            $tags = null;
            if ($request->tags) {
                $tags = json_decode($request->tags, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $tags = null;
                }
            }

            // Create resource record
            $resource = Resource::create([
                'user_id' => $userId,
                'title' => $request->title,
                'type' => $request->type,
                'file_path' => $storedPath,
                's3_url' => $s3Url,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'original_name' => $originalName,
                'is_public' => $request->boolean('is_public', false),
                'tags' => $tags,
            ]);

            // Load the user relationship and add computed attributes
            $resource->load('user');
            $resource->append(['file_size_formatted', 'is_image', 'is_video', 'is_audio', 'is_document']);

            return response()->json([
                'success' => true,
                'message' => 'Resource uploaded successfully',
                'resource' => $resource,
            ], 201);

        } catch (Exception $e) {
            Log::error('Resource upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload resource. Please try again.',
            ], 500);
        }
    }

    /**
     * Get a specific resource by ID.
     *
     * @param Resource $resource The resource model
     * @return JsonResponse Resource data
     */
    public function show(Resource $resource): JsonResponse
    {
        $user = Auth::user();
        
        // Check access permissions
        if (!$resource->is_public && $resource->user_id !== $user->id && !$user->hasRole(['admin', 'teacher'])) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        return response()->json($resource->load('user:id,name,email'));
    }

    /**
     * Update resource metadata (not the file itself).
     *
     * @param Request $request HTTP request with updated data
     * @param Resource $resource The resource to update
     * @return JsonResponse Updated resource data
     */
    public function update(Request $request, Resource $resource): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership or admin access
        if ($resource->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $resource->update($request->only(['title', 'description', 'is_public', 'tags']));
        
        return response()->json([
            'message' => 'Resource updated successfully',
            'resource' => $resource->load('user:id,name,email')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        // Check if user owns the resource or is admin
        if ($resource->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized to delete this resource',
            ], 403);
        }

        try {
            // Determine which S3 disk was used based on file type
            $disk = 's3';
            if (in_array($resource->type, ['audio'])) {
                $disk = 's3-audio';
            } elseif (in_array($resource->type, ['image'])) {
                $disk = 's3-images';
            }
            
            // Delete file from S3
            if (Storage::disk($disk)->exists($resource->file_path)) {
                Storage::disk($disk)->delete($resource->file_path);
            }
            
            // Delete resource record
            $resource->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resource deleted successfully',
            ]);

        } catch (Exception $e) {
            Log::error('Resource deletion failed', [
                'resource_id' => $resource->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete resource. Please try again.',
            ], 500);
        }
    }

    /**
     * Download a resource file.
     */
    public function download(Resource $resource)
    {
        // Check access permissions
        if (!$resource->is_public && $resource->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized to download this resource',
            ], 403);
        }

        try {
            // Determine which S3 disk was used
            $disk = 's3';
            if (in_array($resource->type, ['audio'])) {
                $disk = 's3-audio';
            } elseif (in_array($resource->type, ['image'])) {
                $disk = 's3-images';
            }
            
            // Check if file exists
            if (!Storage::disk($disk)->exists($resource->file_path)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found',
                ], 404);
            }
            
            // Get file from S3
            $fileContents = Storage::disk($disk)->get($resource->file_path);
            
            return response($fileContents)
                ->header('Content-Type', $resource->mime_type)
                ->header('Content-Disposition', 'attachment; filename="' . $resource->original_name . '"')
                ->header('Content-Length', strlen($fileContents));

        } catch (Exception $e) {
            Log::error('Resource download failed', [
                'resource_id' => $resource->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to download resource. Please try again.',
            ], 500);
        }
    }
}