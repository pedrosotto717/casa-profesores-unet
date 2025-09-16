<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'area_id',
        'name',
        'description',
        'requires_reservation',
        'hourly_rate',
        'is_active',
    ];

    protected $casts = [
        'requires_reservation' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the area that owns the service.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
