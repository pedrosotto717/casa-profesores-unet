<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AreaSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_open',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_open' => 'boolean',
    ];

    /**
     * Get the area that owns the schedule.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
