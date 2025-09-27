<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademyRequest;
use App\Http\Requests\UpdateAcademyRequest;
use App\Http\Resources\AcademyResource;
use App\Models\Academy;
use App\Services\AcademyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AcademyController extends Controller
{
    private AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Display a listing of academies (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'lead_instructor_id']);
        $academies = $this->academyService->getAll($filters);
        
        // Debug logging for index
        Log::info('Academy index - Service returned:', [
            'academies_count' => $academies->count(),
            'first_academy_entity_files' => $academies->first() ? $academies->first()->entityFiles->count() : 0,
        ]);

        return response()->json([
            'data' => AcademyResource::collection($academies),
            'meta' => [
                'total' => $academies->count(),
            ],
        ]);
    }

    /**
     * Store a newly created academy (admin only).
     */
    public function store(StoreAcademyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);

        $academy = $this->academyService->create($data, $images, $request->user()->id);

        return response()->json([
            'message' => 'Academy created successfully.',
            'data' => new AcademyResource($academy),
        ], 201);
    }

    /**
     * Display the specified academy (public endpoint).
     */
    public function show(Academy $academy): JsonResponse
    {
        // Load the academy with its relationships
        $academy->load(['leadInstructor', 'entityFiles.file']);
        
        // Debug logging
        Log::info('Academy loaded:', [
            'academy_id' => $academy->id,
            'academy_name' => $academy->name,
            'entity_files_count' => $academy->entityFiles->count(),
            'entity_files_loaded' => $academy->relationLoaded('entityFiles'),
        ]);
        
        if ($academy->entityFiles->count() > 0) {
            Log::info('Entity files details:', [
                'entity_files' => $academy->entityFiles->map(function ($ef) {
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
            'data' => new AcademyResource($academy),
        ]);
    }

    /**
     * Update the specified academy (admin only).
     */
    public function update(UpdateAcademyRequest $request, Academy $academy): JsonResponse
    {
        $data = $request->validated();
        $images = $request->file('images', []);
        $removeFileIds = $request->input('remove_file_ids', []);

        $academy = $this->academyService->update($academy, $data, $images, $removeFileIds, $request->user()->id);

        return response()->json([
            'message' => 'Academy updated successfully.',
            'data' => new AcademyResource($academy),
        ]);
    }

    /**
     * Remove the specified academy (admin only).
     */
    public function destroy(Request $request, Academy $academy): JsonResponse
    {
        $this->academyService->delete($academy, $request->user()->id);

        return response()->json([
            'message' => 'Academy deleted successfully.',
        ]);
    }
}
