<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BeneficiarioResource extends JsonResource
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
            'agremiado_id' => $this->agremiado_id,
            'nombre_completo' => $this->nombre_completo,
            'parentesco' => $this->parentesco->value,
            'estatus' => $this->estatus->value,
            'agremiado' => new UserResource($this->whenLoaded('agremiado')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
