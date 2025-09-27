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
            'schedules_count' => $this->whenCounted('academySchedules'),
            'enrollments_count' => $this->whenCounted('academyEnrollments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

