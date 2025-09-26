<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

final class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'file_type',
        'storage_disk',
        'metadata',
        'visibility',
        'uploaded_by',
        'description',
    ];

    protected $casts = [
        'visibility' => DocumentVisibility::class,
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the public URL for the file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->storage_disk)->url($this->file_path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the file exists in storage.
     */
    public function exists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->file_path);
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->storage_disk)->delete($this->file_path);
        }
        return true;
    }

    /**
     * Scope for filtering by file type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope for filtering by storage disk.
     */
    public function scopeOnDisk($query, string $disk)
    {
        return $query->where('storage_disk', $disk);
    }

    /**
     * Scope for filtering by user uploads.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Scope for filtering by visibility.
     */
    public function scopeWithVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file from storage when model is deleted
        static::deleting(function ($file) {
            $file->deleteFile();
        });
    }
}
