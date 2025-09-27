<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'type' => $this->file_type,
            'url' => $this->url,
            // Pivot data (when attached via EntityFile)
            'caption' => $this->when(isset($this->pivot), $this->pivot?->caption),
            'is_cover' => $this->when(isset($this->pivot), $this->pivot?->is_cover),
            'sort_order' => $this->when(isset($this->pivot), $this->pivot?->sort_order),
        ];
    }
}
