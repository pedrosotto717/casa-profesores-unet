<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

final class NewsPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if this is a detail view (not a list/index)
        $isDetailView = !$request->routeIs('*.index') && 
                        !str_contains($request->path(), '/admin/news') || 
                        str_contains($request->path(), '/admin/news/');
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content, // Always include content
            'excerpt' => Str::limit(strip_tags($this->content), 150),
            'status' => $this->status->value,
            'featured_image_url' => $this->featured_image_url,
            'published_at' => $this->published_at?->toISOString(),
            'author_name' => $this->whenLoaded('author', fn() => $this->author?->name ?? 'Autor Desconocido', 'Autor Desconocido'),
            'author' => [
                'id' => $this->whenLoaded('author', fn() => $this->author?->id),
                'name' => $this->whenLoaded('author', fn() => $this->author?->name ?? 'Autor Desconocido'),
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

