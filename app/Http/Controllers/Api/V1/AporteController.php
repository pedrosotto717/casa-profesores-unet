<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAporteRequest;
use App\Http\Requests\UpdateAporteRequest;
use App\Http\Resources\AporteResource;
use App\Services\AporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AporteController extends Controller
{
    private AporteService $aporteService;

    public function __construct(AporteService $aporteService)
    {
        $this->aporteService = $aporteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id',
            'date_from',
            'date_to',
            'amount_min',
            'amount_max',
            'per_page'
        ]);

        $aportes = $this->aporteService->getAportesPaginated($filters);

        return response()->json([
            'success' => true,
            'data' => AporteResource::collection($aportes->items()),
            'meta' => [
                'current_page' => $aportes->currentPage(),
                'last_page' => $aportes->lastPage(),
                'per_page' => $aportes->perPage(),
                'total' => $aportes->total(),
                'from' => $aportes->firstItem(),
                'to' => $aportes->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAporteRequest $request): JsonResponse
    {
        $aporte = $this->aporteService->createAporte(
            $request->validated('user_id'),
            $request->validated('amount'),
            $request->validated('moneda'),
            $request->validated('aporte_date')
        );

        return response()->json([
            'success' => true,
            'data' => new AporteResource($aporte),
            'message' => 'Aporte creado exitosamente.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $aporte = \App\Models\Aporte::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AporteResource($aporte),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAporteRequest $request, string $id): JsonResponse
    {
        $aporte = $this->aporteService->updateAporte((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new AporteResource($aporte),
            'message' => 'Aporte actualizado exitosamente.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->aporteService->deleteAporte((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Aporte eliminado exitosamente.',
        ]);
    }
}
