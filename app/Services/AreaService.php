<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Area;
use App\Models\AreaSchedule;
use App\Models\AuditLog;
use App\Models\EntityFile;
use App\Models\File;
use App\Support\R2Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        return $query->with(['entityFiles.file', 'areaSchedules'])
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Get a specific area by ID.
     */
    public function getById(int $id): ?Area
    {
        return Area::with(['entityFiles.file', 'areaSchedules'])
                   ->find($id);
    }

    /**
     * Create a new area with optional images and schedules.
     */
    public function create(array $data, array $images = [], array $schedules = [], int $userId = null): Area
    {
        return DB::transaction(function () use ($data, $images, $schedules, $userId) {
            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $area = Area::create($data);

            // Handle image uploads
            if (!empty($images) && $userId) {
                $this->attachImages($area, $images, $userId);
            }

            // Handle schedules
            if (!empty($schedules)) {
                $this->createSchedules($area, $schedules);
            }

            // Log area creation
            if ($userId) {
                $this->logAreaCreation($userId, $area, $data);
            }

            // Refresh the area to load the newly created entity files and schedules
            $area->refresh();
            return $area->load(['entityFiles.file', 'areaSchedules']);
        });
    }

    /**
     * Update an area with optional image changes and schedules.
     */
    public function update(Area $area, array $data, array $images = [], array $removeFileIds = [], array $schedules = [], int $userId = null): Area
    {
        return DB::transaction(function () use ($area, $data, $images, $removeFileIds, $schedules, $userId) {
            // Store original data for audit log
            $originalData = $area->toArray();
            
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

            // Handle schedules update
            if (!empty($schedules)) {
                $this->updateSchedules($area, $schedules);
            }

            // Log area update
            if ($userId) {
                $this->logAreaUpdate($userId, $area, $originalData, $data);
            }

            // Refresh the area to load the newly created entity files and schedules
            $area->refresh();
            return $area->load(['entityFiles.file', 'areaSchedules']);
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

                $entityFile = EntityFile::create([
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

    /**
     * Create schedules for an area.
     */
    private function createSchedules(Area $area, array $schedules): void
    {
        foreach ($schedules as $schedule) {
            AreaSchedule::create([
                'area_id' => $area->id,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_open' => $schedule['is_open'],
            ]);
        }
    }

    /**
     * Update schedules for an area (replace all existing schedules).
     */
    private function updateSchedules(Area $area, array $schedules): void
    {
        // Delete existing schedules
        AreaSchedule::where('area_id', $area->id)->delete();

        // Create new schedules
        $this->createSchedules($area, $schedules);
    }

    /**
     * Log area update action to audit trail.
     * 
     * @param int $userId The ID of the user updating the area
     * @param Area $area The updated area
     * @param array $originalData The original data before update
     * @param array $newData The new data that was applied
     * @return void
     */
    private function logAreaUpdate(int $userId, Area $area, array $originalData, array $newData): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Area',
            'entity_id' => $area->id,
            'action' => 'area_updated',
            'before' => [
                'area_id' => $area->id,
                'name' => $originalData['name'],
                'slug' => $originalData['slug'],
                'description' => $originalData['description'],
                'capacity' => $originalData['capacity'],
                'is_reservable' => $originalData['is_reservable'],
                'is_active' => $originalData['is_active'],
                'updated_at' => $originalData['updated_at'],
            ],
            'after' => [
                'area_id' => $area->id,
                'name' => $area->name,
                'slug' => $area->slug,
                'description' => $area->description,
                'capacity' => $area->capacity,
                'is_reservable' => $area->is_reservable,
                'is_active' => $area->is_active,
                'updated_at' => $area->updated_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }
}
