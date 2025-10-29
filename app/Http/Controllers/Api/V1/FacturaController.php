<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacturaResource;
use App\Services\FacturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FacturaController extends Controller
{
    public function __construct(
        private readonly FacturaService $facturaService
    ) {
    }

    /**
     * Display a listing of all facturas (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'tipo',
            'estatus_pago',
            'fecha_desde',
            'fecha_hasta',
            'monto_min',
            'monto_max',
            'per_page'
        ]);

        $facturas = $this->facturaService->getAllFacturas($filters);

        return response()->json([
            'success' => true,
            'data' => FacturaResource::collection($facturas->items()),
            'meta' => [
                'current_page' => $facturas->currentPage(),
                'last_page' => $facturas->lastPage(),
                'per_page' => $facturas->perPage(),
                'total' => $facturas->total(),
                'from' => $facturas->firstItem(),
                'to' => $facturas->lastItem(),
            ],
        ]);
    }

    /**
     * Display facturas for a specific user (admin only).
     */
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $filters = $request->only([
            'tipo',
            'estatus_pago',
            'fecha_desde',
            'fecha_hasta',
            'monto_min',
            'monto_max',
            'per_page'
        ]);

        $facturas = $this->facturaService->getFacturasByUser($userId, $filters);

        return response()->json([
            'success' => true,
            'data' => FacturaResource::collection($facturas->items()),
            'meta' => [
                'current_page' => $facturas->currentPage(),
                'last_page' => $facturas->lastPage(),
                'per_page' => $facturas->perPage(),
                'total' => $facturas->total(),
                'from' => $facturas->firstItem(),
                'to' => $facturas->lastItem(),
            ],
        ]);
    }

    /**
     * Display facturas for the authenticated user.
     */
    public function myFacturas(Request $request): JsonResponse
    {
        $filters = $request->only([
            'tipo',
            'estatus_pago',
            'fecha_desde',
            'fecha_hasta',
            'monto_min',
            'monto_max',
            'per_page'
        ]);

        $facturas = $this->facturaService->getFacturasByUser($request->user()->id, $filters);

        return response()->json([
            'success' => true,
            'data' => FacturaResource::collection($facturas->items()),
            'meta' => [
                'current_page' => $facturas->currentPage(),
                'last_page' => $facturas->lastPage(),
                'per_page' => $facturas->perPage(),
                'total' => $facturas->total(),
                'from' => $facturas->firstItem(),
                'to' => $facturas->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified factura.
     */
    public function show(int $id): JsonResponse
    {
        $factura = $this->facturaService->getFacturaById($id);

        return response()->json([
            'success' => true,
            'data' => new FacturaResource($factura),
        ]);
    }
}
