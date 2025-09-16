<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requester_id',
        'area_id',
        'starts_at',
        'ends_at',
        'status',
        'approved_by',
        'reviewed_at',
        'decision_reason',
        'title',
        'notes',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that requested the reservation.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the area that owns the reservation.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the user that approved the reservation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
