<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'file_url',
        'visibility',
        'uploaded_by',
        'description',
    ];

    protected $casts = [
        'visibility' => DocumentVisibility::class,
    ];

    /**
     * Get the user that uploaded the document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
