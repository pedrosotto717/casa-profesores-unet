<?php declare(strict_types=1);

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBlockResource extends JsonResource
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
            'blocker_id' => $this->blocker_id,
            'blocked_id' => $this->blocked_id,
            'blocked_user' => [
                'id' => $this->blocked->id,
                'name' => $this->blocked->name,
                'email' => $this->blocked->email,
                'role' => $this->blocked->role->value,
            ],
            'reason' => $this->reason,
            'expires_at' => $this->expires_at,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at,
        ];
    }
}
