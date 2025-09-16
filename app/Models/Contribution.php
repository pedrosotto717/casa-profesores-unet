<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\ContributionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period',
        'amount',
        'status',
        'paid_at',
        'receipt_url',
    ];

    protected $casts = [
        'period' => 'date',
        'amount' => 'decimal:2',
        'status' => ContributionStatus::class,
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user that owns the contribution.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
