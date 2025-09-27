<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class EntityFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'file_id',
        'sort_order',
        'caption',
        'is_cover',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_cover' => 'boolean',
    ];

    /**
     * Get the entity that owns the file.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the file that belongs to the entity.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Scope for filtering by entity type.
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    /**
     * Scope for ordering by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope for cover images.
     */
    public function scopeCover($query)
    {
        return $query->where('is_cover', true);
    }
}

