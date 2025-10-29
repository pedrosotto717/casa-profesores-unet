<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;

final class ReservationCostPreviewController extends Controller
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::with(['area', 'requester'])->findOrFail($id);
        
        $costData = $this->pricingService->calculateReservationCost($reservation);
        
        return response()->json([
            'success' => true,
            'data' => [
                'reservation_id' => $reservation->id,
                'area_name' => $reservation->area->name,
                'user_name' => $reservation->requester->name,
                'user_role' => $reservation->requester->role->value,
                'costo_base' => $costData['costo_base'],
                'descuento' => $costData['descuento'],
                'costo_final' => $costData['costo_final'],
                'moneda' => $costData['moneda'],
                'horas' => $costData['horas'],
                'es_gratis' => $costData['costo_final'] == 0,
            ],
        ]);
    }
}
