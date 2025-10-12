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
use Illuminate\Support\Facades\Log;

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
        $schedules = $request->input('schedules', []);

        $area = $this->areaService->create($data, $images, $schedules, $request->user()->id);

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
        $area->load(['entityFiles.file', 'areaSchedules']);
        
        // Debug logging
        Log::info('Area loaded:', [
            'area_id' => $area->id,
            'area_name' => $area->name,
            'entity_files_count' => $area->entityFiles->count(),
            'entity_files_loaded' => $area->relationLoaded('entityFiles'),
        ]);
        
        if ($area->entityFiles->count() > 0) {
            Log::info('Entity files details:', [
                'entity_files' => $area->entityFiles->map(function ($ef) {
                    return [
                        'entity_file_id' => $ef->id,
                        'file_id' => $ef->file_id,
                        'file_loaded' => $ef->relationLoaded('file'),
                        'file_exists' => $ef->file ? true : false,
                        'file_title' => $ef->file?->title,
                    ];
                })->toArray()
            ]);
        }

        return response()->json([
            'data' => new AreaResource($area),
        ]);
    }

    /**
     * Update the specified area (admin only).
     */
    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        Log::info('AreaController - update method called:', [
            'area_id' => $area->id,
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_files' => $request->hasFile('images'),
            'user_id' => $request->user()?->id,
            'user_authenticated' => $request->user() !== null,
            'all_input' => $request->all(),
            'validated_data' => $request->validated(),
        ]);

        $data = $request->validated();
        $images = $request->file('images', []);
        $removeFileIds = $request->input('remove_file_ids', []);
        $schedules = $request->input('schedules', []);

        Log::info('AreaController - extracted data:', [
            'data' => $data,
            'images_count' => count($images),
            'remove_file_ids' => $removeFileIds,
            'schedules_count' => count($schedules),
        ]);

        $area = $this->areaService->update($area, $data, $images, $removeFileIds, $schedules, $request->user()->id);

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

    /**
     * Test endpoint to verify authentication and routing.
     */
    public function testUpdate(Request $request, Area $area): JsonResponse
    {
        Log::info('AreaController - testUpdate method called:', [
            'area_id' => $area->id,
            'request_method' => $request->method(),
            'user_id' => $request->user()?->id,
            'user_authenticated' => $request->user() !== null,
            'user_role' => $request->user()?->role?->value ?? 'none',
        ]);

        return response()->json([
            'message' => 'Test endpoint reached successfully.',
            'area_id' => $area->id,
            'user_id' => $request->user()?->id,
            'user_authenticated' => $request->user() !== null,
            'user_role' => $request->user()?->role?->value ?? 'none',
        ]);
    }
}
