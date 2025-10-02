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
        'title',
        'notes',
        'decision_reason',
        'approved_by',
        'reviewed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status' => ReservationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the reservation.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the area that is reserved.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the user that reviewed the reservation.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by area.
     */
    public function scopeByArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('requester_id', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->where(function ($q) use ($from, $to) {
            $q->whereBetween('starts_at', [$from, $to])
              ->orWhereBetween('ends_at', [$from, $to])
              ->orWhere(function ($q2) use ($from, $to) {
                  $q2->where('starts_at', '<=', $from)
                     ->where('ends_at', '>=', $to);
              });
        });
    }

    /**
     * Scope to get approved reservations that overlap with given time range.
     */
    public function scopeOverlapping($query, string $startsAt, string $endsAt, int $areaId = null)
    {
        $query = $query->where('status', ReservationStatus::Aprobada)
                      ->where(function ($q) use ($startsAt, $endsAt) {
                          $q->where(function ($q2) use ($startsAt, $endsAt) {
                              // Reservation starts before our end and ends after our start
                              $q2->where('starts_at', '<', $endsAt)
                                 ->where('ends_at', '>', $startsAt);
                          });
                      });

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        return $query;
    }

    /**
     * Check if the reservation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pendiente;
    }

    /**
     * Check if the reservation is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ReservationStatus::Aprobada;
    }

    /**
     * Check if the reservation is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === ReservationStatus::Rechazada;
    }

    /**
     * Check if the reservation is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === ReservationStatus::Cancelada;
    }

    /**
     * Check if the reservation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ReservationStatus::Completada;
    }

    /**
     * Check if the reservation is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === ReservationStatus::Expirada;
    }

    /**
     * Get the duration of the reservation in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /**
     * Check if the reservation can be canceled by the user.
     */
    public function canBeCanceledByUser(int $hoursBeforeStart = 24): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        $hoursUntilStart = now()->diffInHours($this->starts_at, false);
        return $hoursUntilStart >= $hoursBeforeStart;
    }
}