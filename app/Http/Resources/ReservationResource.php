<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReservationResource extends JsonResource
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
            'user' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
                'role' => $this->requester->role->value,
                'role_label' => $this->getRoleLabel($this->requester->role->value),
            ],
            'area' => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'capacity' => $this->area->capacity,
                'is_reservable' => $this->area->is_reservable,
            ],
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->getStatusLabel($this->status->value),
            'estatus_pago' => $this->estatus_pago?->value ?? 'Pendiente',
            'estatus_pago_label' => $this->getPaymentStatusLabel($this->estatus_pago?->value ?? 'Pendiente'),
            'fecha_cancelacion' => $this->fecha_cancelacion?->toIso8601String(),
            'title' => $this->title,
            'notes' => $this->notes,
            'decision_reason' => $this->decision_reason,
            'approver' => $this->when($this->approver, [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ]),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'duration_minutes' => $this->getDurationInMinutes(),
            'can_be_canceled' => $this->canBeCanceledByUser(),
            'factura' => $this->whenLoaded('factura', function () {
                return [
                    'id' => $this->factura->id,
                    'monto' => $this->factura->monto,
                    'moneda' => $this->factura->moneda,
                    'fecha_pago' => $this->factura->fecha_pago?->toIso8601String(),
                    'estatus_pago' => $this->factura->estatus_pago?->value ?? 'Pagado',
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get human-readable role label.
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'usuario' => 'Usuario',
            'profesor' => 'Profesor',
            'estudiante' => 'Estudiante',
            'instructor' => 'Instructor',
            'obrero' => 'Obrero',
            'invitado' => 'Invitado',
            'administrador' => 'Administrador',
            default => ucfirst($role),
        };
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            'completada' => 'Completada',
            'expirada' => 'Expirada',
            default => ucfirst($status),
        };
    }

    /**
     * Get human-readable payment status label.
     */
    private function getPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'Pendiente' => 'Pendiente',
            'Pagado' => 'Pagado',
            'Gratis' => 'Gratis',
            default => ucfirst($status),
        };
    }
}
