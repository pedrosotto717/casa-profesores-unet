<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReservationCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Returns minimal data for calendar display (non-admin users only).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->getStatusLabel($this->status->value),
            'area' => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'capacity' => $this->area->capacity,
                'is_reservable' => $this->area->is_reservable,
            ],
        ];
    }
}
