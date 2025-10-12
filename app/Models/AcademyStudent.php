<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AcademyStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'age',
        'status',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    /**
     * Get the academy that owns the student.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}

