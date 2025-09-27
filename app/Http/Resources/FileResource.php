<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'file_path' => $this->file_path,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'formatted_size' => $this->formatted_size,
            'file_type' => $this->file_type,
            'storage_disk' => $this->storage_disk,
            'visibility' => $this->visibility->value,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'uploaded_by' => [
                'id' => $this->uploadedBy->id,
                'name' => $this->uploadedBy->name,
                'email' => $this->uploadedBy->email,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Pivot data (when attached via EntityFile)
            'caption' => $this->when(isset($this->pivot), $this->pivot?->caption),
            'is_cover' => $this->when(isset($this->pivot), $this->pivot?->is_cover),
            'sort_order' => $this->when(isset($this->pivot), $this->pivot?->sort_order),
        ];
    }
}
