<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Models\AuditLog;
use App\Models\EntityFile;
use App\Models\File;
use App\Support\R2Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ServiceService
{
    /**
     * Get all services with optional filtering and pagination.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Service::with(['area', 'entityFiles.file']);

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get a specific service by ID.
     */
    public function getById(int $id): ?Service
    {
        return Service::with(['area', 'entityFiles.file'])->find($id);
    }

    /**
     * Create a new service with optional images.
     */
    public function create(array $data, array $images = [], int $userId = null): Service
    {
        return DB::transaction(function () use ($data, $images, $userId) {
            $service = Service::create($data);

            // Handle image uploads
            if (!empty($images) && $userId) {
                $this->attachImages($service, $images, $userId);
            }

            // Log service creation
            if ($userId) {
                $this->logServiceCreation($userId, $service, $data);
            }

            // Refresh the service to load the newly created entity files
            $service->refresh();
            return $service->load(['area', 'entityFiles.file']);
        });
    }

    /**
     * Update a service with optional image changes.
     */
    public function update(Service $service, array $data, array $images = [], array $removeFileIds = [], int $userId = null): Service
    {
        return DB::transaction(function () use ($service, $data, $images, $removeFileIds, $userId) {
            // Update service data
            $service->update($data);

            // Remove specified files
            if (!empty($removeFileIds)) {
                $this->detachImages($service, $removeFileIds);
            }

            // Add new images
            if (!empty($images) && $userId) {
                $this->attachImages($service, $images, $userId);
            }

            // Refresh the service to load the newly created entity files
            $service->refresh();
            return $service->load(['area', 'entityFiles.file']);
        });
    }

    /**
     * Delete a service and its associated files.
     */
    public function delete(Service $service, int $userId = null): bool
    {
        return DB::transaction(function () use ($service, $userId) {
            // Log service deletion before deleting
            if ($userId) {
                $this->logServiceDeletion($userId, $service);
            }

            // Delete associated files
            $this->deleteAllImages($service);

            // Force delete the service (hard delete)
            return $service->forceDelete();
        });
    }

    /**
     * Attach images to a service.
     */
    public function attachImages(Service $service, array $images, int $userId): void
    {
        $sortOrder = $service->entityFiles()->max('sort_order') ?? 0;

        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $fileRecord = R2Storage::putPublicWithRecord(
                    $image,
                    $userId,
                    'image',
                    $image->getClientOriginalName(),
                    "Image for service: {$service->name}"
                );

                EntityFile::create([
                    'entity_type' => 'Service',
                    'entity_id' => $service->getKey(),
                    'file_id' => $fileRecord->getKey(),
                    'sort_order' => $sortOrder + $index + 1,
                    'is_cover' => $index === 0 && $service->entityFiles()->count() === 0, // First image is cover if no existing images
                ]);
            }
        }
    }

    /**
     * Detach images from a service.
     */
    public function detachImages(Service $service, array $fileIds): void
    {
        $entityFiles = EntityFile::forEntity('Service', $service->getKey())
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
     * Delete all images associated with a service.
     */
    public function deleteAllImages(Service $service): void
    {
        $entityFiles = EntityFile::forEntity('Service', $service->getKey())->get();

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
     * Log service creation action to audit trail.
     * 
     * @param int $userId The ID of the user creating the service
     * @param Service $service The created service
     * @param array $data The original data used to create the service
     * @return void
     */
    private function logServiceCreation(int $userId, Service $service, array $data): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Service',
            'entity_id' => $service->id,
            'action' => 'service_created',
            'before' => null,
            'after' => [
                'service_id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'area_id' => $service->area_id,
                'is_active' => $service->is_active,
                'created_at' => $service->created_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Log service deletion action to audit trail.
     * 
     * @param int $userId The ID of the user deleting the service
     * @param Service $service The service being deleted
     * @return void
     */
    private function logServiceDeletion(int $userId, Service $service): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Service',
            'entity_id' => $service->id,
            'action' => 'service_deleted',
            'before' => [
                'service_id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'area_id' => $service->area_id,
                'is_active' => $service->is_active,
                'created_at' => $service->created_at?->toISOString(),
            ],
            'after' => null,
        ]);
    }
}
