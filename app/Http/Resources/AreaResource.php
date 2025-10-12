<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'is_reservable' => $this->is_reservable,
            'is_active' => $this->is_active,
            'images' => ImageResource::collection($this->whenLoaded('entityFiles', function () {
                return $this->entityFiles->filter(function ($entityFile) {
                    return $entityFile->file !== null;
                })->map(function ($entityFile) {
                    $file = $entityFile->file;
                    $file->pivot = $entityFile; // Attach pivot data to file
                    return $file;
                });
            })),
            'schedules' => $this->whenLoaded('areaSchedules', function () {
                return $this->areaSchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'is_open' => $schedule->is_open,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

