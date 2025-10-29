<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Aporte extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'moneda',
        'aporte_date',
        'factura_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'aporte_date' => 'date',
    ];

    /**
     * Get the user that owns the aporte.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the factura associated with this aporte.
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
}
