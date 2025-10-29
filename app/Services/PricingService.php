<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Reservation;
use App\Models\Area;
use App\Models\User;
use App\Enums\UserRole;

final class PricingService
{
    /**
     * Calculate the cost for a reservation.
     *
     * @return array{costo_base: float, descuento: float, costo_final: float, moneda: string, horas: float}
     */
    public function calculateReservationCost(Reservation $reservation): array
    {
        $area = $reservation->area;
        $user = $reservation->requester;
        
        // Calculate hours
        $hours = $reservation->starts_at->diffInHours($reservation->ends_at, true);
        
        // Get base cost
        $costoBase = $area->monto_hora_externo * $hours;
        $descuento = 0.0;
        $costoFinal = $costoBase;
        
        // Check if area is free for agremiados (professors)
        if ($area->es_gratis_agremiados && $user->role === UserRole::Profesor) {
            return [
                'costo_base' => $costoBase,
                'descuento' => $costoBase,
                'costo_final' => 0.0,
                'moneda' => $area->moneda,
                'horas' => $hours,
            ];
        }
        
        // Apply discount for agremiados (professors)
        if ($user->role === UserRole::Profesor && $area->porcentaje_descuento_agremiado > 0) {
            $descuento = $costoBase * ($area->porcentaje_descuento_agremiado / 100);
            $costoFinal = $costoBase - $descuento;
        }
        
        return [
            'costo_base' => round($costoBase, 2),
            'descuento' => round($descuento, 2),
            'costo_final' => round($costoFinal, 2),
            'moneda' => $area->moneda,
            'horas' => $hours,
        ];
    }
}

