<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FileUploadController extends Controller
{
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * Upload audio file to S3.
     * Handles validation and returns S3 URL for frontend use.
     *
     * @param Request $request HTTP request with audio file
     * @return \Illuminate\Http\JsonResponse Upload result with S3 URL
     */
    public function uploadAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file',
            'folder' => 'sometimes|string|max:50'
        ]);

        $folder = $request->input('folder', 'general');
        $audioFile = $request->file('audio');

        try {
            $audioUrl = $this->fileUploadService->uploadAudio($audioFile, $folder);

            return response()->json([
                'success' => true,
                'audio_url' => $audioUrl,
                'message' => 'Audio uploaded successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => 'Failed to upload audio file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image file to S3.
     * Handles validation and returns S3 URL for frontend use.
     *
     * @param Request $request HTTP request with image file
     * @return \Illuminate\Http\JsonResponse Upload result with S3 URL
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file',
            'folder' => 'sometimes|string|max:50'
        ]);

        $folder = $request->input('folder', 'general');
        $imageFile = $request->file('image');

        try {
            $imageUrl = $this->fileUploadService->uploadImage($imageFile, $folder);

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'message' => 'Image uploaded successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => 'Failed to upload image file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file from S3.
     * Removes file based on provided S3 URL and file type.
     *
     * @param Request $request HTTP request with file URL and type
     * @return \Illuminate\Http\JsonResponse Deletion result
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'file_url' => 'required|url',
            'file_type' => 'required|in:audio,image'
        ]);

        $fileUrl = $request->input('file_url');
        $fileType = $request->input('file_type');

        try {
            $this->fileUploadService->deleteFile($fileUrl, $fileType);

            return response()->json([
                'success' => true,
                'message' => ucfirst($fileType) . ' deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Deletion failed',
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }
}