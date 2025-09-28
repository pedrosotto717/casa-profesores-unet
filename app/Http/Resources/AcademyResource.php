<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

final class AcademyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'lead_instructor' => new UserResource($this->whenLoaded('leadInstructor')),
            'images' => ImageResource::collection($this->whenLoaded('entityFiles', function () {
                return $this->entityFiles->map(function ($entityFile) {
                    $file = $entityFile->file;
                    $file->pivot = $entityFile; // Attach pivot data to file
                    return $file;
                });
            })),
            'schedules' => $this->whenLoaded('academySchedules', function () {
                return $this->academySchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'area_id' => $schedule->area_id,
                        'area_name' => $schedule->area ? $schedule->area->name : null,
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'capacity' => $schedule->capacity,
                    ];
                });
            }),
            'schedules_count' => $this->whenCounted('academySchedules'),
            'enrollments_count' => $this->whenCounted('academyEnrollments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

