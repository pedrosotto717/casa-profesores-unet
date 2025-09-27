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
            'hourly_rate' => $this->hourly_rate,
            'is_active' => $this->is_active,
            'images' => ImageResource::collection($this->whenLoaded('entityFiles', function () {
                return $this->entityFiles->map(function ($entityFile) {
                    $file = $entityFile->file;
                    $file->pivot = $entityFile; // Attach pivot data to file
                    return $file;
                });
            })),
            'services_count' => $this->whenCounted('services'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

