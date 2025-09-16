<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AcademyEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'user_id',
        'academy_schedule_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => EnrollmentStatus::class,
    ];

    /**
     * Get the academy that owns the enrollment.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the user that owns the enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the academy schedule that owns the enrollment.
     */
    public function academySchedule(): BelongsTo
    {
        return $this->belongsTo(AcademySchedule::class);
    }
}
