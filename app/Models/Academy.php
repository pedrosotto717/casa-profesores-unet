<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\AcademyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Academy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'lead_instructor_id',
        'status',
    ];

    protected $casts = [
        'status' => AcademyStatus::class,
    ];

    /**
     * Get the lead instructor that owns the academy.
     */
    public function leadInstructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_instructor_id');
    }

    /**
     * Get the academy schedules for the academy.
     */
    public function academySchedules(): HasMany
    {
        return $this->hasMany(AcademySchedule::class);
    }

    /**
     * Get the academy enrollments for the academy.
     */
    public function academyEnrollments(): HasMany
    {
        return $this->hasMany(AcademyEnrollment::class);
    }

    /**
     * Get the entity files associated with the academy.
     */
    public function entityFiles(): MorphMany
    {
        return $this->morphMany(EntityFile::class, 'entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the files associated with the academy through entity files.
     */
    public function files()
    {
        return $this->hasManyThrough(
            File::class,
            EntityFile::class,
            'entity_id', // Foreign key on entity_files table
            'id', // Foreign key on files table
            'id', // Local key on academies table
            'file_id' // Local key on entity_files table
        )->where('entity_files.entity_type', 'Academy');
    }
}
