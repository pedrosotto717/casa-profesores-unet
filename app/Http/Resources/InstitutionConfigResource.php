<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstitutionConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'mission' => $this->resource['mission'] ?? '',
            'vision' => $this->resource['vision'] ?? '',
            'members' => $this->resource['members'] ?? [],
            'social_media' => $this->resource['social_media'] ?? [
                'facebook' => null,
                'instagram' => null,
                'twitter' => null,
                'email' => null,
            ],
        ];
    }
}

