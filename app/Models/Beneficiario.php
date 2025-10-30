<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\BeneficiarioEstatus;
use App\Enums\BeneficiarioParentesco;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Beneficiario extends Model
{
    use HasFactory;

    protected $fillable = [
        'agremiado_id',
        'nombre_completo',
        'parentesco',
        'estatus',
    ];

    protected $casts = [
        'parentesco' => BeneficiarioParentesco::class,
        'estatus' => BeneficiarioEstatus::class,
    ];

    /**
     * Get the agremiado (professor) that owns the beneficiario.
     */
    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agremiado_id');
    }
}
