<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    private ServiceService $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    /**
     * Display a listing of services (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'area_id', 'is_active']);
        $services = $this->serviceService->getAll($filters);

        return response()->json([
            'data' => ServiceResource::collection($services),
            'meta' => [
                'total' => $services->count(),
            ],
        ]);
    }

    /**
     * Store a newly created service (admin only).
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);

        $service = $this->serviceService->create($data, $images, $request->user()->id);

        return response()->json([
            'message' => 'Service created successfully.',
            'data' => new ServiceResource($service),
        ], 201);
    }

    /**
     * Display the specified service (public endpoint).
     */
    public function show(Service $service): JsonResponse
    {
        // Load the service with its relationships
        $service->load(['area', 'entityFiles.file']);

        return response()->json([
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Update the specified service (admin only).
     */
    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);
        $removeFileIds = $request->input('remove_file_ids', []);

        $service = $this->serviceService->update($service, $data, $images, $removeFileIds, $request->user()->id);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Remove the specified service (admin only).
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->serviceService->delete($service, $request->user()->id);

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }
}
