<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
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
            'role' => $this->role?->value,
            'role_label' => $this->getRoleLabel(),
            'sso_uid' => $this->sso_uid,
            'is_solvent' => $this->is_solvent,
            'solvent_until' => $this->solvent_until?->toDateString(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships (only when loaded)
            'reservations_count' => $this->when(isset($this->reservations_count), $this->reservations_count),
            'contributions_count' => $this->when(isset($this->contributions_count), $this->contributions_count),
            'academies_count' => $this->when(isset($this->academies_count), $this->academies_count),
            'enrollments_count' => $this->when(isset($this->enrollments_count), $this->enrollments_count),
            
            // Full relationships (only when explicitly loaded)
            'academies' => AcademyResource::collection($this->whenLoaded('academies')),
        ];
    }

    /**
     * Get a human-readable label for the user role.
     */
    private function getRoleLabel(): string
    {
        return match ($this->role?->value) {
            'usuario' => 'Usuario',
            'profesor' => 'Profesor',
            'instructor' => 'Instructor',
            'administrador' => 'Administrador',
            'obrero' => 'Obrero',
            'estudiante' => 'Estudiante',
            'invitado' => 'Invitado',
            default => 'Usuario',
        };
    }
}
