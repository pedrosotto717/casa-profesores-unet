<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoFactura;
use App\Enums\EstatusFactura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipo',
        'monto',
        'moneda',
        'fecha_emision',
        'fecha_pago',
        'estatus_pago',
        'descripcion',
    ];

    protected $casts = [
        'tipo' => TipoFactura::class,
        'monto' => 'decimal:2',
        'fecha_emision' => 'datetime',
        'fecha_pago' => 'datetime',
        'estatus_pago' => EstatusFactura::class,
    ];

    /**
     * Get the user that owns the factura.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
