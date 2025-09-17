<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssetsLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AdminAssignmentController manages assignments and shared assets library.
 * Provides assignment oversight, asset management, and signed upload URLs.
 */
class AdminAssignmentController extends Controller
{
    /**
     * Get paginated list of assignments with class and submission metrics.
     * Includes completion rates and recent activity.
     *
     * @param Request $request HTTP request with optional filters
     * @return \Illuminate\Http\JsonResponse paginated assignment list with metrics
     */
    public function index(Request $request)
    {
        $query = Assignment::query()
            ->with(['class:id,name', 'teacher:id,name'])
            ->withCount('submissions')
            ->select(['id', 'title', 'description', 'class_id', 'teacher_id', 'status', 'created_at', 'updated_at']);
        
        // Search by title or description
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filter by class
        if ($classId = $request->get('class_id')) {
            $query->where('class_id', $classId);
        }
        
        // Filter by teacher
        if ($teacherId = $request->get('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $assignments = $query->paginate($request->get('per_page', 20));
        
        // Transform data to include additional metrics
        $assignments->getCollection()->transform(function ($assignment) {
            // Get class student count for completion rate calculation
            $classStudentCount = DB::table('class_user')
                ->where('class_id', $assignment->class_id)
                ->count();
            
            $completionRate = $classStudentCount > 0 
                ? round(($assignment->submissions_count / $classStudentCount) * 100, 1)
                : 0;
            
            // Get average score
            $avgScore = DB::table('submissions')
                ->where('assignment_id', $assignment->id)
                ->whereNotNull('overall_score')
                ->avg('overall_score');
            
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'status' => $assignment->status,
                'class' => [
                    'id' => $assignment->class->id,
                    'name' => $assignment->class->name,
                ],
                'teacher' => [
                    'id' => $assignment->teacher->id,
                    'name' => $assignment->teacher->name,
                ],
                'metrics' => [
                    'total_submissions' => $assignment->submissions_count,
                    'class_size' => $classStudentCount,
                    'completion_rate' => $completionRate,
                    'avg_score' => $avgScore ? round($avgScore, 1) : null,
                ],
                'created_at' => $assignment->created_at->toISOString(),
                'updated_at' => $assignment->updated_at->toISOString(),
            ];
        });
        
        return response()->json($assignments);
    }
    
    /**
     * Get assets library with search and filtering.
     * Provides shared content for teachers and assignments.
     *
     * @param Request $request HTTP request with optional filters
     * @return \Illuminate\Http\JsonResponse paginated assets list
     */
    public function assets(Request $request)
    {
        $query = AssetsLibrary::query()
            ->with(['owner:id,name'])
            ->select(['id', 'owner_id', 'kind', 'title', 's3_url', 'size_bytes', 'meta_json', 'created_at']);
        
        // Search by title
        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        
        // Filter by asset kind
        if ($kind = $request->get('kind')) {
            $query->where('kind', $kind);
        }
        
        // Filter by owner
        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $assets = $query->paginate($request->get('per_page', 20));
        
        // Transform data to include readable file sizes and metadata
        $assets->getCollection()->transform(function ($asset) {
            $meta = json_decode($asset->meta_json, true) ?? [];
            
            return [
                'id' => $asset->id,
                'title' => $asset->title,
                'kind' => $asset->kind,
                'owner' => [
                    'id' => $asset->owner->id,
                    'name' => $asset->owner->name,
                ],
                's3_url' => $asset->s3_url,
                'size_bytes' => $asset->size_bytes,
                'size_human' => $this->formatBytes($asset->size_bytes),
                'meta' => $meta,
                'created_at' => $asset->created_at->toISOString(),
            ];
        });
        
        return response()->json($assets);
    }
    
    /**
     * Generate signed upload URL for asset upload.
     * Creates temporary S3 upload URL with proper permissions.
     *
     * @param Request $request HTTP request with file info
     * @return \Illuminate\Http\JsonResponse signed upload URL and metadata
     */
    public function generateUploadUrl(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|max:100',
            'size_bytes' => 'required|integer|min:1|max:104857600', // 100MB max
            'kind' => 'required|string|in:audio,image,document,video',
        ]);
        
        $filename = $request->get('filename');
        $contentType = $request->get('content_type');
        $sizeBytes = $request->get('size_bytes');
        $kind = $request->get('kind');
        
        // Generate unique S3 key
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uniqueId = Str::uuid();
        $s3Key = "assets/{$kind}/{$uniqueId}.{$extension}";
        
        try {
            // Generate signed upload URL (valid for 1 hour)
            $disk = Storage::disk('s3');
            $signedUrl = $disk->temporaryUploadUrl(
                $s3Key,
                now()->addHour(),
                [
                    'ContentType' => $contentType,
                    'ContentLength' => $sizeBytes,
                ]
            );
            
            // Prepare asset record data for frontend to save after upload
            $assetData = [
                'owner_id' => auth()->id(),
                'kind' => $kind,
                'title' => pathinfo($filename, PATHINFO_FILENAME),
                's3_url' => $disk->url($s3Key),
                'size_bytes' => $sizeBytes,
                'meta_json' => json_encode([
                    'original_filename' => $filename,
                    'content_type' => $contentType,
                    'uploaded_by' => auth()->user()->name,
                ]),
            ];
            
            return response()->json([
                'upload_url' => $signedUrl,
                's3_key' => $s3Key,
                'asset_data' => $assetData,
                'expires_at' => now()->addHour()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate upload URL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Save asset record after successful upload.
     * Creates database record for uploaded asset.
     *
     * @param Request $request HTTP request with asset data
     * @return \Illuminate\Http\JsonResponse created asset record
     */
    public function saveAsset(Request $request)
    {
        $request->validate([
            'kind' => 'required|string|in:audio,image,document,video',
            'title' => 'required|string|max:255',
            's3_url' => 'required|url',
            'size_bytes' => 'required|integer|min:1',
            'meta_json' => 'nullable|json',
        ]);
        
        $asset = AssetsLibrary::create([
            'owner_id' => auth()->id(),
            'kind' => $request->get('kind'),
            'title' => $request->get('title'),
            's3_url' => $request->get('s3_url'),
            'size_bytes' => $request->get('size_bytes'),
            'meta_json' => $request->get('meta_json', '{}'),
        ]);
        
        return response()->json([
            'success' => true,
            'asset' => [
                'id' => $asset->id,
                'title' => $asset->title,
                'kind' => $asset->kind,
                's3_url' => $asset->s3_url,
                'size_human' => $this->formatBytes($asset->size_bytes),
                'created_at' => $asset->created_at->toISOString(),
            ],
        ], 201);
    }
    
    /**
     * Format bytes into human-readable format.
     * @param int $bytes File size in bytes
     * @return string formatted size (e.g., "1.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
}