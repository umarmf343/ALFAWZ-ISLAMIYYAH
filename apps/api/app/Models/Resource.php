<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Resource extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'filename',
        'file_path',
        'file_url',
        's3_url',
        'file_size',
        'mime_type',
        'original_name',
        'is_public',
        'tags',
        'download_count'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'tags' => 'array',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'file_size_formatted',
        'is_image',
        'is_video', 
        'is_audio',
        'is_document'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'file_path' // Hide internal file path for security
    ];

    /**
     * Get the user who uploaded this resource.
     *
     * @return BelongsTo User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted file size in human readable format.
     *
     * @return string Formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get formatted file size (e.g., "2.5 MB").
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the resource is an image type.
     *
     * @return bool True if image type
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->type, ['image']) || 
               str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if the resource is a video type.
     *
     * @return bool True if video type
     */
    public function getIsVideoAttribute(): bool
    {
        return in_array($this->type, ['video']) || 
               str_starts_with($this->mime_type ?? '', 'video/');
    }

    /**
     * Check if the resource is an audio type.
     *
     * @return bool True if audio type
     */
    public function getIsAudioAttribute(): bool
    {
        return in_array($this->type, ['audio']) || 
               str_starts_with($this->mime_type ?? '', 'audio/');
    }

    /**
     * Check if the resource is a document type.
     *
     * @return bool True if document type
     */
    public function getIsDocumentAttribute(): bool
    {
        return in_array($this->type, ['pdf', 'document', 'worksheet']) || 
               in_array($this->mime_type, [
                   'application/pdf',
                   'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/vnd.ms-excel',
                   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'text/plain'
               ]);
    }

    /**
     * Get the appropriate S3 disk for this resource type.
     */
    public function getS3Disk(): string
    {
        if ($this->is_audio) {
            return 's3-audio';
        } elseif ($this->is_image) {
            return 's3-images';
        }
        return 's3';
    }

    /**
     * Check if the file exists in S3 storage.
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->getS3Disk())->exists($this->file_path);
    }

    /**
     * Get a temporary signed URL for the resource (1 hour expiry).
     */
    public function getTemporaryUrl(int $minutes = 60): string
    {
        return Storage::disk($this->getS3Disk())->temporaryUrl(
            $this->file_path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Scope to filter public resources.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by resource type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to search resources by title or description.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by tags.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $tags
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope to filter public resources.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}