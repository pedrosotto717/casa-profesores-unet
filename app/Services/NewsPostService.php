<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\NewsPostStatus;
use App\Models\AuditLog;
use App\Models\EntityFile;
use App\Models\NewsPost;
use App\Support\R2Storage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NewsPostService
{
    /**
     * Get published news posts for public viewing (paginated).
     */
    public function publicGetPublished(int $perPage = 12): LengthAwarePaginator
    {
        return NewsPost::published()
            ->with(['author', 'entityFiles.file'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a published news post by UUID or slug for public viewing.
     */
    public function publicFindByUuidOrSlug(string $identifier): ?NewsPost
    {
        return NewsPost::published()
            ->where(function ($query) use ($identifier) {
                $query->where('uuid', $identifier)
                      ->orWhere('slug', $identifier);
            })
            ->with(['author', 'entityFiles.file'])
            ->first();
    }

    /**
     * Get all news posts for admin panel (paginated).
     */
    public function adminGetAll(int $perPage = 15): LengthAwarePaginator
    {
        return NewsPost::query()
            ->with(['author', 'entityFiles.file'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a news post by ID for admin editing.
     */
    public function adminFindById(int $id): ?NewsPost
    {
        return NewsPost::with(['author', 'entityFiles.file'])
            ->find($id);
    }

    /**
     * Create a new news post.
     */
    public function create(array $data, ?UploadedFile $featuredImage, int $userId): NewsPost
    {
        return DB::transaction(function () use ($data, $featuredImage, $userId) {
            // Generate slug from title
            $data['slug'] = Str::slug($data['title']);
            
            // Generate UUID
            $data['uuid'] = (string) Str::uuid();
            
            // Assign author
            $data['author_id'] = $userId;
            
            // Set published_at if status is published
            if ($data['status'] === 'published' || $data['status'] === NewsPostStatus::Published->value) {
                $data['published_at'] = now();
            }

            // Create the news post
            $post = NewsPost::create($data);

            // Handle featured image upload
            if ($featuredImage) {
                $this->attachFeaturedImage($post, $featuredImage, $userId);
            }

            // Log audit trail
            $this->logNewsPostCreation($userId, $post);

            // Return post with relationships loaded
            return $post->fresh(['author', 'entityFiles.file']);
        });
    }

    /**
     * Update an existing news post.
     */
    public function update(
        NewsPost $post,
        array $data,
        ?UploadedFile $featuredImage,
        bool $clearFeaturedImage,
        int $userId
    ): NewsPost {
        return DB::transaction(function () use ($post, $data, $featuredImage, $clearFeaturedImage, $userId) {
            // Store original data for audit log
            $originalData = $post->toArray();
            $originalStatus = $post->status;

            // Handle featured image clearing
            if ($clearFeaturedImage) {
                $this->deleteFeaturedImage($post);
            }

            // Handle new featured image upload
            if ($featuredImage) {
                // Delete old image first
                $this->deleteFeaturedImage($post);
                // Upload new image
                $this->attachFeaturedImage($post, $featuredImage, $userId);
            }

            // Update slug if title changed
            if (isset($data['title']) && $data['title'] !== $post->title) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle published_at when changing from draft to published
            if (isset($data['status'])) {
                $newStatus = is_string($data['status']) 
                    ? NewsPostStatus::from($data['status']) 
                    : $data['status'];

                if ($originalStatus === NewsPostStatus::Draft && 
                    $newStatus === NewsPostStatus::Published && 
                    !$post->published_at) {
                    $data['published_at'] = now();
                }
            }

            // Update the post
            $post->update($data);

            // Log audit trail
            $this->logNewsPostUpdate($userId, $post, $originalData);

            // Return updated post with relationships
            return $post->fresh(['author', 'entityFiles.file']);
        });
    }

    /**
     * Delete a news post.
     */
    public function delete(NewsPost $post, int $userId): bool
    {
        return DB::transaction(function () use ($post, $userId) {
            $title = $post->title;

            // Delete all associated images
            $this->deleteAllImages($post);

            // Log audit trail before deletion
            $this->logNewsPostDeletion($userId, $post);

            // Delete the post
            return $post->forceDelete();
        });
    }

    /**
     * Attach a featured image to the news post.
     */
    private function attachFeaturedImage(NewsPost $post, UploadedFile $image, int $userId): void
    {
        // Upload image to R2
        $fileRecord = R2Storage::putPublicWithRecord(
            $image,
            $userId,
            'image',
            "Featured image for: {$post->title}",
            "Featured image for news post: {$post->title}"
        );

        // Create entity file relationship with is_cover = true
        EntityFile::create([
            'entity_type' => 'NewsPost',
            'entity_id' => $post->id,
            'file_id' => $fileRecord->id,
            'sort_order' => 1,
            'is_cover' => true,
        ]);
    }

    /**
     * Delete the featured image of the news post.
     */
    private function deleteFeaturedImage(NewsPost $post): void
    {
        $entityFile = EntityFile::forEntity('NewsPost', $post->id)
            ->where('is_cover', true)
            ->first();

        if ($entityFile) {
            $file = $entityFile->file;
            
            // Delete the entity file relationship
            $entityFile->delete();

            // Check if file is still referenced by other entities
            $remainingReferences = EntityFile::where('file_id', $file->id)->count();
            
            if ($remainingReferences === 0) {
                // No other references, delete the file completely
                R2Storage::deleteFile($file);
            }
        }
    }

    /**
     * Delete all images associated with the news post.
     */
    private function deleteAllImages(NewsPost $post): void
    {
        $entityFiles = EntityFile::forEntity('NewsPost', $post->id)->get();

        foreach ($entityFiles as $entityFile) {
            $file = $entityFile->file;
            
            // Delete the entity file relationship
            $entityFile->delete();

            // Check if file is still referenced by other entities
            $remainingReferences = EntityFile::where('file_id', $file->id)->count();
            
            if ($remainingReferences === 0) {
                // No other references, delete the file completely
                R2Storage::deleteFile($file);
            }
        }
    }

    /**
     * Log news post creation to audit trail.
     */
    private function logNewsPostCreation(int $userId, NewsPost $post): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'NewsPost',
            'entity_id' => $post->id,
            'action' => 'news_post_created',
            'before' => null,
            'after' => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toISOString(),
                'created_at' => $post->created_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Log news post update to audit trail.
     */
    private function logNewsPostUpdate(int $userId, NewsPost $post, array $originalData): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'NewsPost',
            'entity_id' => $post->id,
            'action' => 'news_post_updated',
            'before' => [
                'id' => $post->id,
                'title' => $originalData['title'] ?? null,
                'slug' => $originalData['slug'] ?? null,
                'status' => $originalData['status'] ?? null,
                'published_at' => $originalData['published_at'] ?? null,
            ],
            'after' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toISOString(),
                'updated_at' => $post->updated_at->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Log news post deletion to audit trail.
     */
    private function logNewsPostDeletion(int $userId, NewsPost $post): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'NewsPost',
            'entity_id' => $post->id,
            'action' => 'news_post_deleted',
            'before' => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toISOString(),
                'created_at' => $post->created_at->toISOString(),
            ],
            'after' => null,
        ]);
    }
}

