<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_user_id',
        'name',
        'email',
        'token',
        'status',
        'expires_at',
        'message',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that created the invitation.
     */
    public function inviterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /**
     * Get the user that reviewed the invitation.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
