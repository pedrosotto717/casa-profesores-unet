<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AcademySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'area_id',
        'day_of_week',
        'start_time',
        'end_time',
        'capacity',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'capacity' => 'integer',
    ];

    /**
     * Get the academy that owns the schedule.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the area that owns the schedule.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the academy enrollments for the schedule.
     */
    public function academyEnrollments(): HasMany
    {
        return $this->hasMany(AcademyEnrollment::class);
    }
}
