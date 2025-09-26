<?php

namespace App\Support;

use App\Models\File;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Static helper class for Cloudflare R2 storage operations.
 * 
 * This class provides centralized methods for uploading files to R2
 * and generating public URLs using the configured R2 disk.
 */
final class R2Storage
{
    /**
     * Upload a file to R2 with public visibility and create database record.
     * 
     * @param UploadedFile $file The uploaded file
     * @param int $userId The ID of the user uploading the file
     * @param string $fileType The type of file (document, image, receipt, other)
     * @param string $title Optional title for the file
     * @param string $description Optional description
     * @return mixed The created file model
     */
    public static function putPublicWithRecord(
        UploadedFile $file, 
        int $userId, 
        string $fileType = 'other',
        $title = null,
        $description = null
    ) {
        // Generate simple path with just hash name (no folders)
        $path = $file->hashName();
        
        // Store file with public visibility
        Storage::disk('r2')->put($path, $file->getContent(), 'public');
        
        // Calculate file hash for deduplication
        $fileHash = hash('sha256', $file->getContent());
        
        // Check for existing file with same hash
        $existingFile = File::where('file_hash', $fileHash)->first();
        if ($existingFile) {
            // Delete the newly uploaded file since we already have it
            Storage::disk('r2')->delete($path);
            
            // Return reference to existing file
            return $existingFile;
        }
        
        // Create database record
        $fileRecord = File::create([
            'title' => $title ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => $fileHash,
            'file_type' => $fileType,
            'storage_disk' => 'r2',
            'metadata' => [
                'uploaded_via' => 'api',
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
            'visibility' => 'publico',
            'uploaded_by' => $userId,
            'description' => $description,
        ]);
        
        // Log audit trail for PDF and Word files
        $mimeType = $file->getMimeType();
        if ($mimeType === 'application/pdf' || 
            $mimeType === 'application/msword' || 
            $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            self::logFileUpload($userId, $fileRecord, $file);
        }
        
        return $fileRecord;
    }

    /**
     * Upload a file to R2 with public visibility (legacy method).
     * 
     * @param UploadedFile $file The uploaded file
     * @return array Array containing the file path and public URL
     */
    public static function putPublic(UploadedFile $file)
    {
        // Generate simple path with just hash name (no folders)
        $path = $file->hashName();
        
        // Store file with public visibility
        Storage::disk('r2')->put($path, $file->getContent(), 'public');
        
        return [
            'path' => $path,
            'url' => Storage::disk('r2')->url($path)
        ];
    }

    /**
     * Delete a file from R2 storage.
     * 
     * @param string $path The file path to delete
     * @return bool True if the file was deleted successfully
     */
    public static function delete(string $path): bool
    {
        return Storage::disk('r2')->delete($path);
    }

    /**
     * Check if a file exists in R2 storage.
     * 
     * @param string $path The file path to check
     * @return bool True if the file exists
     */
    public static function exists(string $path): bool
    {
        return Storage::disk('r2')->exists($path);
    }

    /**
     * Get the public URL for a file in R2 storage.
     * 
     * @param string $path The file path
     * @return string The public URL
     */
    public static function url(string $path): string
    {
        return Storage::disk('r2')->url($path);
    }

    /**
     * Get file size from R2 storage.
     * 
     * @param string $path The file path
     * @return int File size in bytes
     */
    public static function size(string $path): int
    {
        return Storage::disk('r2')->size($path);
    }

    /**
     * Get file metadata from R2 storage.
     * 
     * @param string $path The file path
     * @return array File metadata
     */
    public static function metadata(string $path): array
    {
        return Storage::disk('r2')->getMetadata($path);
    }

    /**
     * Find file by file path.
     * 
     * @param string $path The file path
     * @return File|null The file model or null if not found
     */
    public static function findFileByPath(string $path): ?File
    {
        return File::where('file_path', $path)->first();
    }

    /**
     * Find file by file hash.
     * 
     * @param string $hash The file hash
     * @return File|null The file model or null if not found
     */
    public static function findFileByHash(string $hash): ?File
    {
        return File::where('file_hash', $hash)->first();
    }

    /**
     * Delete file and its associated record.
     * 
     * @param File $file The file to delete
     * @return bool True if deleted successfully
     */
    public static function deleteFile(File $file): bool
    {
        // Log audit trail for PDF and Word files before deletion
        $mimeType = $file->mime_type;
        if ($mimeType === 'application/pdf' || 
            $mimeType === 'application/msword' || 
            $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            self::logFileDeletion($file->uploaded_by, $file);
        }
        
        // Delete file from storage
        $fileDeleted = $file->deleteFile();
        
        // Delete database record
        $dbDeleted = $file->delete();
        
        return $fileDeleted && $dbDeleted;
    }

    /**
     * Get files by user.
     * 
     * @param int $userId The user ID
     * @param string|null $fileType Optional file type filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserFiles(int $userId, ?string $fileType = null)
    {
        $query = File::byUser($userId);
        
        if ($fileType) {
            $query->ofType($fileType);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Clean up orphaned files (files in storage but not in database).
     * 
     * @return int Number of files cleaned up
     */
    public static function cleanupOrphanedFiles()
    {
        $disk = Storage::disk('r2');
        $allFiles = $disk->allFiles(); // Get all files from root
        $dbPaths = File::pluck('file_path')->toArray();
        
        $orphanedFiles = array_diff($allFiles, $dbPaths);
        $deletedCount = 0;
        
        foreach ($orphanedFiles as $file) {
            if ($disk->delete($file)) {
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }

    /**
     * Log file upload action to audit trail.
     * 
     * @param int $userId The ID of the user uploading the file
     * @param File $fileRecord The created file record
     * @param UploadedFile $file The uploaded file
     * @return void
     */
    private static function logFileUpload(int $userId, File $fileRecord, UploadedFile $file)
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'File',
            'entity_id' => $fileRecord->id,
            'action' => 'file_uploaded',
            'before' => null,
            'after' => [
                'file_id' => $fileRecord->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $fileRecord->file_path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => $fileRecord->file_type,
                'title' => $fileRecord->title,
                'description' => $fileRecord->description,
                'visibility' => $fileRecord->visibility,
                'upload_method' => 'api',
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'upload_timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Log file deletion action to audit trail.
     * 
     * @param int $userId The ID of the user deleting the file
     * @param File $fileRecord The file record being deleted
     * @return void
     */
    private static function logFileDeletion(int $userId, File $fileRecord)
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'File',
            'entity_id' => $fileRecord->id,
            'action' => 'file_deleted',
            'before' => [
                'file_id' => $fileRecord->id,
                'original_filename' => $fileRecord->original_filename,
                'file_path' => $fileRecord->file_path,
                'mime_type' => $fileRecord->mime_type,
                'file_size' => $fileRecord->file_size,
                'file_type' => $fileRecord->file_type,
                'title' => $fileRecord->title,
                'description' => $fileRecord->description,
                'visibility' => $fileRecord->visibility,
                'created_at' => $fileRecord->created_at?->toISOString(),
            ],
            'after' => null,
        ]);
    }

}
