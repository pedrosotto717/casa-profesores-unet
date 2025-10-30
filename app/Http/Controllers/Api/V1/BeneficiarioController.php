<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BeneficiarioEstatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBeneficiarioRequest;
use App\Http\Requests\UpdateBeneficiarioRequest;
use App\Http\Resources\BeneficiarioResource;
use App\Models\Beneficiario;
use App\Services\BeneficiarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

final class BeneficiarioController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $beneficiarioService
    ) {}

    /**
     * (ADMIN) Display a listing of all beneficiarios with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Beneficiario::class);

        try {
            $perPage = $request->input('per_page', 15);
            $agremiadoId = $request->input('agremiado_id');
            $estatus = $request->input('estatus');

            $query = Beneficiario::with('agremiado:id,name');

            // Filter by agremiado_id if provided
            if ($agremiadoId) {
                $query->where('agremiado_id', $agremiadoId);
            }

            // Filter by estatus if provided
            if ($estatus && in_array($estatus, ['pendiente', 'aprobado', 'inactivo'])) {
                $query->where('estatus', $estatus);
            }

            $beneficiarios = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => BeneficiarioResource::collection($beneficiarios),
                'meta' => [
                    'pagination' => [
                        'current_page' => $beneficiarios->currentPage(),
                        'last_page' => $beneficiarios->lastPage(),
                        'per_page' => $beneficiarios->perPage(),
                        'total' => $beneficiarios->total(),
                    ]
                ],
                'message' => 'Beneficiarios retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving beneficiarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (PROFESOR) Store a newly created beneficiario.
     */
    public function store(StoreBeneficiarioRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $beneficiario = $this->beneficiarioService->create(Auth::id(), $data);

            return response()->json([
                'success' => true,
                'data' => new BeneficiarioResource($beneficiario),
                'message' => 'Beneficiario created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (ADMIN/PROFESOR) Display the specified beneficiario.
     */
    public function show(Beneficiario $beneficiario): JsonResponse
    {
        $this->authorize('view', $beneficiario);

        try {
            return response()->json([
                'success' => true,
                'data' => new BeneficiarioResource($beneficiario->load('agremiado:id,name')),
                'message' => 'Beneficiario retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (ADMIN/PROFESOR) Update the specified beneficiario.
     */
    public function update(UpdateBeneficiarioRequest $request, Beneficiario $beneficiario): JsonResponse
    {
        try {
            $data = $request->validated();
            $beneficiario = $this->beneficiarioService->update($beneficiario, $data, Auth::id());

            return response()->json([
                'success' => true,
                'data' => new BeneficiarioResource($beneficiario),
                'message' => 'Beneficiario updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (ADMIN/PROFESOR) Remove the specified beneficiario.
     */
    public function destroy(Beneficiario $beneficiario): JsonResponse
    {
        $this->authorize('delete', $beneficiario);

        try {
            $this->beneficiarioService->delete($beneficiario);

            return response()->json([
                'success' => true,
                'message' => 'Beneficiario deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (PROFESOR) Get beneficiarios for the authenticated professor.
     */
    public function myBeneficiarios(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== UserRole::Profesor) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only professors can view their beneficiarios.'
            ], 403);
        }

        try {
            $beneficiarios = Beneficiario::where('agremiado_id', $user->id)
                ->orderBy('estatus')
                ->orderBy('nombre_completo')
                ->get();

            return response()->json([
                'success' => true,
                'data' => BeneficiarioResource::collection($beneficiarios),
                'message' => 'My beneficiarios retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving my beneficiarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (ADMIN) Approve a pending beneficiario.
     */
    public function approve(Beneficiario $beneficiario): JsonResponse
    {
        $this->authorize('approve', $beneficiario);

        if ($beneficiario->estatus !== BeneficiarioEstatus::Pendiente) {
            return response()->json([
                'success' => false,
                'message' => 'The beneficiario is not pending approval'
            ], 422);
        }

        try {
            $beneficiario = $this->beneficiarioService->approve($beneficiario, Auth::user());

            return response()->json([
                'success' => true,
                'data' => new BeneficiarioResource($beneficiario),
                'message' => 'Beneficiario approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (ADMIN) Reject a pending beneficiario.
     */
    public function reject(Beneficiario $beneficiario): JsonResponse
    {
        $this->authorize('reject', $beneficiario);

        try {
            $beneficiario = $this->beneficiarioService->reject($beneficiario, Auth::user());

            return response()->json([
                'success' => true,
                'data' => new BeneficiarioResource($beneficiario),
                'message' => 'Beneficiario rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting beneficiario: ' . $e->getMessage()
            ], 500);
        }
    }
}
