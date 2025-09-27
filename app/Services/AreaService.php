<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\EntityFile;
use App\Models\File;
use App\Support\R2Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AreaService
{
    /**
     * Get all areas with optional filtering and pagination.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Area::query();

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->with(['entityFiles.file'])
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Get a specific area by ID.
     */
    public function getById(int $id): ?Area
    {
        return Area::with(['entityFiles.file', 'services'])
                   ->find($id);
    }

    /**
     * Create a new area with optional images.
     */
    public function create(array $data, array $images = [], int $userId = null): Area
    {
        return DB::transaction(function () use ($data, $images, $userId) {
            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $area = Area::create($data);

            // Handle image uploads
            if (!empty($images) && $userId) {
                $this->attachImages($area, $images, $userId);
            }

            // Log area creation
            if ($userId) {
                $this->logAreaCreation($userId, $area, $data);
            }

            // Refresh the area to load the newly created entity files
            $area->refresh();
            return $area->load('entityFiles.file');
        });
    }

    /**
     * Update an area with optional image changes.
     */
    public function update(Area $area, array $data, array $images = [], array $removeFileIds = [], int $userId = null): Area
    {
        return DB::transaction(function () use ($area, $data, $images, $removeFileIds, $userId) {
            // Update area data
            $area->update($data);

            // Remove specified files
            if (!empty($removeFileIds)) {
                $this->detachImages($area, $removeFileIds);
            }

            // Add new images
            if (!empty($images) && $userId) {
                $this->attachImages($area, $images, $userId);
            }

            // Refresh the area to load the newly created entity files
            $area->refresh();
            return $area->load('entityFiles.file');
        });
    }

    /**
     * Delete an area and its associated files.
     */
    public function delete(Area $area, int $userId = null): bool
    {
        return DB::transaction(function () use ($area, $userId) {
            // Log area deletion before deleting
            if ($userId) {
                $this->logAreaDeletion($userId, $area);
            }

            // Delete associated files
            $this->deleteAllImages($area);

            // Force delete the area (hard delete)
            return $area->forceDelete();
        });
    }

    /**
     * Attach images to an area.
     */
    public function attachImages(Area $area, array $images, int $userId): void
    {
        $sortOrder = $area->entityFiles()->max('sort_order') ?? 0;

        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $fileRecord = R2Storage::putPublicWithRecord(
                    $image,
                    $userId,
                    'image',
                    $image->getClientOriginalName(),
                    "Image for area: {$area->name}"
                );

                EntityFile::create([
                    'entity_type' => 'Area',
                    'entity_id' => $area->getKey(),
                    'file_id' => $fileRecord->getKey(),
                    'sort_order' => $sortOrder + $index + 1,
                    'is_cover' => $index === 0 && $area->entityFiles()->count() === 0, // First image is cover if no existing images
                ]);
            }
        }
    }

    /**
     * Detach images from an area.
     */
    public function detachImages(Area $area, array $fileIds): void
    {
        $entityFiles = EntityFile::forEntity('Area', $area->getKey())
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
     * Delete all images associated with an area.
     */
    public function deleteAllImages(Area $area): void
    {
        $entityFiles = EntityFile::forEntity('Area', $area->getKey())->get();

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
     * Log area creation action to audit trail.
     * 
     * @param int $userId The ID of the user creating the area
     * @param Area $area The created area
     * @param array $data The original data used to create the area
     * @return void
     */
    private function logAreaCreation(int $userId, Area $area, array $data): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Area',
            'entity_id' => $area->id,
            'action' => 'area_created',
            'before' => null,
            'after' => [
                'area_id' => $area->id,
                'name' => $area->name,
                'slug' => $area->slug,
                'description' => $area->description,
                'is_active' => $area->is_active,
                'created_at' => $area->created_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Log area deletion action to audit trail.
     * 
     * @param int $userId The ID of the user deleting the area
     * @param Area $area The area being deleted
     * @return void
     */
    private function logAreaDeletion(int $userId, Area $area): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Area',
            'entity_id' => $area->id,
            'action' => 'area_deleted',
            'before' => [
                'area_id' => $area->id,
                'name' => $area->name,
                'slug' => $area->slug,
                'description' => $area->description,
                'is_active' => $area->is_active,
                'created_at' => $area->created_at?->toISOString(),
            ],
            'after' => null,
        ]);
    }
}
