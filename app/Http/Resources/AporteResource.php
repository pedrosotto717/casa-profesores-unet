<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AporteResource extends JsonResource
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
            'amount' => $this->amount,
            'moneda' => $this->moneda,
            'aporte_date' => $this->aporte_date?->toDateString(),
            'factura_id' => $this->factura_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Include user relationship when loaded
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'status' => $this->user->status->value,
                    'solvent_until' => $this->user->solvent_until?->toDateString(),
                ];
            }),
            
            // Include factura relationship when loaded
            'factura' => $this->whenLoaded('factura', function () {
                return [
                    'id' => $this->factura->id,
                    'tipo' => $this->factura->tipo->value,
                    'monto' => $this->factura->monto,
                    'moneda' => $this->factura->moneda,
                    'fecha_emision' => $this->factura->fecha_emision?->toISOString(),
                    'fecha_pago' => $this->factura->fecha_pago?->toISOString(),
                    'estatus_pago' => $this->factura->estatus_pago->value,
                    'descripcion' => $this->factura->descripcion,
                ];
            }),
        ];
    }
}
