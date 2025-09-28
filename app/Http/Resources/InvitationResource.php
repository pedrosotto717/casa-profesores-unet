<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvitationResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->getStatusLabel(),
            'token' => $this->token,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships (only when loaded)
            'inviter' => $this->whenLoaded('inviterUser', function () {
                return [
                    'id' => $this->inviterUser->id,
                    'name' => $this->inviterUser->name,
                    'email' => $this->inviterUser->email,
                    'role' => $this->inviterUser->role?->value,
                ];
            }),
            
            'reviewed_by' => $this->whenLoaded('reviewedBy', function () {
                return [
                    'id' => $this->reviewedBy->id,
                    'name' => $this->reviewedBy->name,
                    'email' => $this->reviewedBy->email,
                ];
            }),
            
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            
            // Additional computed fields
            'is_expired' => $this->expires_at ? $this->expires_at < now() : false,
            'days_until_expiry' => $this->expires_at ? now()->diffInDays($this->expires_at, false) : null,
        ];
    }

    /**
     * Get a human-readable label for the invitation status.
     */
    private function getStatusLabel(): string
    {
        return match ($this->status?->value) {
            'pendiente' => 'Pendiente',
            'aceptada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'expirada' => 'Expirada',
            'revocada' => 'Revocada',
            default => 'Desconocido',
        };
    }
}
