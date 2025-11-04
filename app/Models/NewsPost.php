<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsPostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'content',
        'author_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'status' => NewsPostStatus::class,
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NewsPost $post) {
            if (!$post->uuid) {
                $post->uuid = (string) Str::uuid();
            }
            if (!$post->slug && $post->title) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /**
     * Get the author of the news post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get all entity files (images) for this news post.
     */
    public function entityFiles(): MorphMany
    {
        return $this->morphMany(EntityFile::class, 'entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the featured image (cover image) for this news post.
     */
    public function featuredImage(): MorphMany
    {
        return $this->entityFiles()->where('is_cover', true);
    }

    /**
     * Get the featured image URL accessor.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        // Use the loaded collection instead of making a new query
        if ($this->relationLoaded('entityFiles')) {
            $featuredImage = $this->entityFiles->where('is_cover', true)->first();
        } else {
            $featuredImage = $this->entityFiles()->where('is_cover', true)->first();
        }
        
        if ($featuredImage && $featuredImage->file) {
            return $featuredImage->file->url;
        }

        return null;
    }

    /**
     * Scope to filter only published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', NewsPostStatus::Published);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, NewsPostStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search posts by title or content.
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
              ->orWhere('content', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === NewsPostStatus::Published;
    }

    /**
     * Check if the post is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === NewsPostStatus::Draft;
    }
}

