<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Services\AreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AreaController extends Controller
{
    private AreaService $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    /**
     * Display a listing of areas (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active']);
        $areas = $this->areaService->getAll($filters);

        return response()->json([
            'data' => AreaResource::collection($areas),
            'meta' => [
                'total' => $areas->count(),
            ],
        ]);
    }

    /**
     * Store a newly created area (admin only).
     */
    public function store(StoreAreaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);

        $area = $this->areaService->create($data, $images, $request->user()->id);

        return response()->json([
            'message' => 'Area created successfully.',
            'data' => new AreaResource($area),
        ], 201);
    }

    /**
     * Display the specified area (public endpoint).
     */
    public function show(Area $area): JsonResponse
    {
        // Load the area with its relationships
        $area->load(['entityFiles.file']);

        return response()->json([
            'data' => new AreaResource($area),
        ]);
    }

    /**
     * Update the specified area (admin only).
     */
    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);
        $removeFileIds = $request->input('remove_file_ids', []);

        $area = $this->areaService->update($area, $data, $images, $removeFileIds, $request->user()->id);

        return response()->json([
            'message' => 'Area updated successfully.',
            'data' => new AreaResource($area),
        ]);
    }

    /**
     * Remove the specified area (admin only).
     */
    public function destroy(Request $request, Area $area): JsonResponse
    {
        $this->areaService->delete($area, $request->user()->id);

        return response()->json([
            'message' => 'Area deleted successfully.',
        ]);
    }
}
