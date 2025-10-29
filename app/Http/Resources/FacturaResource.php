<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturaResource extends JsonResource
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
            'user_id' => $this->user_id,
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->getTipoLabel($this->tipo->value),
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'fecha_emision' => $this->fecha_emision?->toISOString(),
            'fecha_pago' => $this->fecha_pago?->toISOString(),
            'estatus_pago' => $this->estatus_pago->value,
            'estatus_pago_label' => $this->getEstatusPagoLabel($this->estatus_pago->value),
            'descripcion' => $this->descripcion,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Include user relationship when loaded
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role->value,
                ];
            }),
        ];
    }

    /**
     * Get human-readable tipo label.
     */
    private function getTipoLabel(string $tipo): string
    {
        return match ($tipo) {
            'Aporte Solvencia' => 'Aporte de Solvencia',
            'Pago Reserva' => 'Pago de Reserva',
            default => $tipo,
        };
    }

    /**
     * Get human-readable estatus pago label.
     */
    private function getEstatusPagoLabel(string $estatus): string
    {
        return match ($estatus) {
            'Pagado' => 'Pagado',
            'Pendiente' => 'Pendiente',
            default => $estatus,
        };
    }
}
