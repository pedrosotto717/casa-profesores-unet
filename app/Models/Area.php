<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'capacity',
        'is_reservable',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_reservable' => 'boolean',
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

    /**
     * Get the entity files associated with the area.
     */
    public function entityFiles(): MorphMany
    {
        return $this->morphMany(EntityFile::class, 'entity', 'entity_type', 'entity_id');
    }
}
