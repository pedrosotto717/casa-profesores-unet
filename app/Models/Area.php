<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'capacity',
        'hourly_rate',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the area schedules for the area.
     */
    public function areaSchedules(): HasMany
    {
        return $this->hasMany(AreaSchedule::class);
    }

    /**
     * Get the services for the area.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the academy schedules for the area.
     */
    public function academySchedules(): HasMany
    {
        return $this->hasMany(AcademySchedule::class);
    }

    /**
     * Get the reservations for the area.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
