<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'blocker_id' => 'integer',
            'blocked_id' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who created the block.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * Get the user who is blocked.
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    /**
     * Scope to get active blocks (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get blocks where a user is the blocker.
     */
    public function scopeWhereBlocker($query, int $userId)
    {
        return $query->where('blocker_id', $userId);
    }

    /**
     * Scope to get blocks where a user is blocked.
     */
    public function scopeWhereBlocked($query, int $userId)
    {
        return $query->where('blocked_id', $userId);
    }

    /**
     * Check if this block is currently active (not expired).
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
