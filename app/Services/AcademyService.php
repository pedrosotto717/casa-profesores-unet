<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Academy;
use App\Models\AuditLog;
use App\Models\EntityFile;
use App\Models\File;
use App\Support\R2Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AcademyService
{
    /**
     * Get all academies with optional filtering and pagination.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Academy::with(['leadInstructor', 'entityFiles.file']);

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['lead_instructor_id'])) {
            $query->where('lead_instructor_id', $filters['lead_instructor_id']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get a specific academy by ID.
     */
    public function getById(int $id): ?Academy
    {
        return Academy::with(['leadInstructor', 'entityFiles.file', 'academySchedules', 'academyEnrollments'])
                      ->find($id);
    }

    /**
     * Create a new academy with optional images.
     */
    public function create(array $data, array $images = [], int $userId = null): Academy
    {
        return DB::transaction(function () use ($data, $images, $userId) {
            Log::info('AcademyService - Creating academy:', [
                'data' => $data,
                'images_count' => count($images),
                'user_id' => $userId,
            ]);

            $academy = Academy::create($data);

            // Handle image uploads
            if (!empty($images) && $userId) {
                Log::info('AcademyService - Attaching images:', [
                    'academy_id' => $academy->id,
                    'images_count' => count($images),
                ]);
                $this->attachImages($academy, $images, $userId);
            } else {
                Log::info('AcademyService - No images to attach:', [
                    'images_empty' => empty($images),
                    'user_id_provided' => $userId !== null,
                ]);
            }

            // Log academy creation
            if ($userId) {
                $this->logAcademyCreation($userId, $academy, $data);
            }

            // Refresh the academy to load the newly created entity files
            $academy->refresh();
            return $academy->load(['leadInstructor', 'entityFiles.file']);
        });
    }

    /**
     * Update an academy with optional image changes.
     */
    public function update(Academy $academy, array $data, array $images = [], array $removeFileIds = [], int $userId = null): Academy
    {
        return DB::transaction(function () use ($academy, $data, $images, $removeFileIds, $userId) {
            // Update academy data
            $academy->update($data);

            // Remove specified files
            if (!empty($removeFileIds)) {
                $this->detachImages($academy, $removeFileIds);
            }

            // Add new images
            if (!empty($images) && $userId) {
                $this->attachImages($academy, $images, $userId);
            }

            // Refresh the academy to load the newly created entity files
            $academy->refresh();
            return $academy->load(['leadInstructor', 'entityFiles.file']);
        });
    }

    /**
     * Delete an academy and its associated files.
     */
    public function delete(Academy $academy, int $userId = null): bool
    {
        return DB::transaction(function () use ($academy, $userId) {
            // Log academy deletion before deleting
            if ($userId) {
                $this->logAcademyDeletion($userId, $academy);
            }

            // Delete associated files
            $this->deleteAllImages($academy);

            // Force delete the academy (hard delete)
            return $academy->forceDelete();
        });
    }

    /**
     * Attach images to an academy.
     */
    public function attachImages(Academy $academy, array $images, int $userId): void
    {
        $sortOrder = $academy->entityFiles()->max('sort_order') ?? 0;

        Log::info('AcademyService - attachImages started:', [
            'academy_id' => $academy->id,
            'images_count' => count($images),
            'sort_order' => $sortOrder,
        ]);

        foreach ($images as $index => $image) {
            Log::info('AcademyService - Processing image:', [
                'index' => $index,
                'is_uploaded_file' => $image instanceof UploadedFile,
                'original_name' => $image instanceof UploadedFile ? $image->getClientOriginalName() : 'N/A',
            ]);

            if ($image instanceof UploadedFile) {
                $fileRecord = R2Storage::putPublicWithRecord(
                    $image,
                    $userId,
                    'image',
                    $image->getClientOriginalName(),
                    "Image for academy: {$academy->name}"
                );

                Log::info('AcademyService - File record created:', [
                    'file_id' => $fileRecord->getKey(),
                    'file_title' => $fileRecord->title,
                ]);

                $entityFile = EntityFile::create([
                    'entity_type' => 'Academy',
                    'entity_id' => $academy->getKey(),
                    'file_id' => $fileRecord->getKey(),
                    'sort_order' => $sortOrder + $index + 1,
                    'is_cover' => $index === 0 && $academy->entityFiles()->count() === 0, // First image is cover if no existing images
                ]);

                Log::info('AcademyService - EntityFile created:', [
                    'entity_file_id' => $entityFile->id,
                    'entity_type' => $entityFile->entity_type,
                    'entity_id' => $entityFile->entity_id,
                    'file_id' => $entityFile->file_id,
                    'sort_order' => $entityFile->sort_order,
                    'is_cover' => $entityFile->is_cover,
                ]);
            }
        }
    }

    /**
     * Detach images from an academy.
     */
    public function detachImages(Academy $academy, array $fileIds): void
    {
        $entityFiles = EntityFile::forEntity('Academy', $academy->getKey())
                                ->whereIn('file_id', $fileIds)
                                ->get();

        foreach ($entityFiles as $entityFile) {
            $file = $entityFile->file;
            
            // Delete the entity file relationship
            $entityFile->delete();

            // Check if file is still referenced by other entities
            $remainingReferences = EntityFile::where('file_id', $file->getKey())->count();
            
            if ($remainingReferences === 0) {
                // No other references, delete the file completely
                R2Storage::deleteFile($file);
            }
        }
    }

    /**
     * Delete all images associated with an academy.
     */
    public function deleteAllImages(Academy $academy): void
    {
        $entityFiles = EntityFile::forEntity('Academy', $academy->getKey())->get();

        foreach ($entityFiles as $entityFile) {
            $file = $entityFile->file;
            
            // Delete the entity file relationship
            $entityFile->delete();

            // Check if file is still referenced by other entities
            $remainingReferences = EntityFile::where('file_id', $file->getKey())->count();
            
            if ($remainingReferences === 0) {
                // No other references, delete the file completely
                R2Storage::deleteFile($file);
            }
        }
    }

    /**
     * Log academy creation action to audit trail.
     * 
     * @param int $userId The ID of the user creating the academy
     * @param Academy $academy The created academy
     * @param array $data The original data used to create the academy
     * @return void
     */
    private function logAcademyCreation(int $userId, Academy $academy, array $data): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Academy',
            'entity_id' => $academy->id,
            'action' => 'academy_created',
            'before' => null,
            'after' => [
                'academy_id' => $academy->id,
                'name' => $academy->name,
                'description' => $academy->description,
                'status' => $academy->status,
                'lead_instructor_id' => $academy->lead_instructor_id,
                'max_students' => $academy->max_students,
                'start_date' => $academy->start_date?->toISOString(),
                'end_date' => $academy->end_date?->toISOString(),
                'created_at' => $academy->created_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Log academy deletion action to audit trail.
     * 
     * @param int $userId The ID of the user deleting the academy
     * @param Academy $academy The academy being deleted
     * @return void
     */
    private function logAcademyDeletion(int $userId, Academy $academy): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Academy',
            'entity_id' => $academy->id,
            'action' => 'academy_deleted',
            'before' => [
                'academy_id' => $academy->id,
                'name' => $academy->name,
                'description' => $academy->description,
                'status' => $academy->status,
                'lead_instructor_id' => $academy->lead_instructor_id,
                'max_students' => $academy->max_students,
                'start_date' => $academy->start_date?->toISOString(),
                'end_date' => $academy->end_date?->toISOString(),
                'created_at' => $academy->created_at?->toISOString(),
            ],
            'after' => null,
        ]);
    }
}
