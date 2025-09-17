<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    /**
     * Upload audio file to S3 with validation.
     * Supports MP3, WAV, M4A formats with size limits.
     *
     * @param UploadedFile $file Audio file to upload
     * @param string $folder Folder path within audio directory
     * @return string S3 URL of uploaded file
     * @throws ValidationException If file validation fails
     */
    public function uploadAudio(UploadedFile $file, string $folder = 'recordings'): string
    {
        // Validate audio file
        $this->validateAudioFile($file);
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file, $folder);
        
        // Upload to S3 audio disk
        $path = Storage::disk('s3-audio')->putFileAs($folder, $file, $filename);
        
        if (!$path) {
            throw new \Exception('Failed to upload audio file to S3');
        }
        
        // Return full S3 URL
        return Storage::disk('s3-audio')->url($path);
    }
    
    /**
     * Upload image file to S3 with validation.
     * Supports JPEG, PNG, JPG, GIF formats with size limits.
     *
     * @param UploadedFile $file Image file to upload
     * @param string $folder Folder path within images directory
     * @return string S3 URL of uploaded file
     * @throws ValidationException If file validation fails
     */
    public function uploadImage(UploadedFile $file, string $folder = 'assignments'): string
    {
        // Validate image file
        $this->validateImageFile($file);
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file, $folder);
        
        // Upload to S3 images disk
        $path = Storage::disk('s3-images')->putFileAs($folder, $file, $filename);
        
        if (!$path) {
            throw new \Exception('Failed to upload image file to S3');
        }
        
        // Return full S3 URL
        return Storage::disk('s3-images')->url($path);
    }
    
    /**
     * Delete file from S3 by URL.
     * Extracts path from URL and removes file from appropriate disk.
     *
     * @param string $url S3 URL of file to delete
     * @param string $type File type ('audio' or 'image')
     * @return bool True if deleted successfully
     */
    public function deleteFile(string $url, string $type = 'audio'): bool
    {
        if (empty($url)) {
            return true;
        }
        
        // Extract path from URL
        $path = $this->extractPathFromUrl($url);
        
        if (!$path) {
            return false;
        }
        
        // Choose appropriate disk
        $disk = $type === 'image' ? 's3-images' : 's3-audio';
        
        return Storage::disk($disk)->delete($path);
    }
    
    /**
     * Validate audio file format and size.
     * Ensures file meets requirements for audio recordings.
     *
     * @param UploadedFile $file Audio file to validate
     * @throws ValidationException If validation fails
     */
    private function validateAudioFile(UploadedFile $file): void
    {
        $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/x-m4a'];
        $allowedExtensions = ['mp3', 'wav', 'm4a'];
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not valid.'
            ]);
        }
        
        if ($file->getSize() > $maxSize) {
            throw ValidationException::withMessages([
                'file' => 'Audio file size cannot exceed 50MB.'
            ]);
        }
        
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($mimeType, $allowedMimes) || !in_array($extension, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'Audio file must be MP3, WAV, or M4A format.'
            ]);
        }
    }
    
    /**
     * Validate image file format and size.
     * Ensures file meets requirements for assignment images.
     *
     * @param UploadedFile $file Image file to validate
     * @throws ValidationException If validation fails
     */
    private function validateImageFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not valid.'
            ]);
        }
        
        if ($file->getSize() > $maxSize) {
            throw ValidationException::withMessages([
                'file' => 'Image file size cannot exceed 10MB.'
            ]);
        }
        
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($mimeType, $allowedMimes) || !in_array($extension, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'Image file must be JPEG, PNG, JPG, or GIF format.'
            ]);
        }
    }
    
    /**
     * Generate unique filename with timestamp and UUID.
     * Prevents filename collisions and maintains file extension.
     *
     * @param UploadedFile $file File to generate name for
     * @param string $folder Folder context for naming
     * @return string Unique filename
     */
    private function generateUniqueFilename(UploadedFile $file, string $folder): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $uuid = Str::uuid()->toString();
        
        return "{$folder}_{$timestamp}_{$uuid}.{$extension}";
    }
    
    /**
     * Extract file path from S3 URL.
     * Handles both CloudFront and direct S3 URLs.
     *
     * @param string $url S3 URL to extract path from
     * @return string|null File path or null if invalid
     */
    private function extractPathFromUrl(string $url): ?string
    {
        // Handle CloudFront URLs
        if (strpos($url, 'cloudfront.net') !== false) {
            $parts = parse_url($url);
            return ltrim($parts['path'] ?? '', '/');
        }
        
        // Handle direct S3 URLs
        if (strpos($url, 's3.amazonaws.com') !== false || strpos($url, 's3-') !== false) {
            $parts = parse_url($url);
            $pathParts = explode('/', ltrim($parts['path'] ?? '', '/'));
            
            // Remove bucket name from path
            if (count($pathParts) > 1) {
                array_shift($pathParts);
                return implode('/', $pathParts);
            }
        }
        
        return null;
    }
    
    /**
     * Get file size from S3 URL.
     * Useful for displaying file information to users.
     *
     * @param string $url S3 URL to check
     * @param string $type File type ('audio' or 'image')
     * @return int|null File size in bytes or null if not found
     */
    public function getFileSize(string $url, string $type = 'audio'): ?int
    {
        $path = $this->extractPathFromUrl($url);
        
        if (!$path) {
            return null;
        }
        
        $disk = $type === 'image' ? 's3-images' : 's3-audio';
        
        try {
            return Storage::disk($disk)->size($path);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if file exists on S3.
     * Useful for validating file URLs before processing.
     *
     * @param string $url S3 URL to check
     * @param string $type File type ('audio' or 'image')
     * @return bool True if file exists
     */
    public function fileExists(string $url, string $type = 'audio'): bool
    {
        $path = $this->extractPathFromUrl($url);
        
        if (!$path) {
            return false;
        }
        
        $disk = $type === 'image' ? 's3-images' : 's3-audio';
        
        return Storage::disk($disk)->exists($path);
    }
}