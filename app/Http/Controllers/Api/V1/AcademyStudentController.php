<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademyStudentRequest;
use App\Http\Requests\UpdateAcademyStudentRequest;
use App\Http\Resources\AcademyStudentResource;
use App\Models\Academy;
use App\Models\AcademyStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AcademyStudentController extends Controller
{
    /**
     * Display a listing of students for a specific academy.
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        // Authorization check
        if (Gate::denies('viewAny', [AcademyStudent::class, $academy])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to view students for this academy.'
            ], 403);
        }

        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');

            $query = AcademyStudent::where('academy_id', $academy->id);

            // Filter by status if provided
            if ($status && in_array($status, ['solvente', 'insolvente'])) {
                $query->where('status', $status);
            }

            $students = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AcademyStudentResource::collection($students),
                'meta' => [
                    'pagination' => [
                        'current_page' => $students->currentPage(),
                        'last_page' => $students->lastPage(),
                        'per_page' => $students->perPage(),
                        'total' => $students->total(),
                    ]
                ],
                'message' => 'Students retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(StoreAcademyStudentRequest $request, Academy $academy): JsonResponse
    {
        try {
            $student = AcademyStudent::create([
                'academy_id' => $academy->id,
                'name' => $request->input('name'),
                'age' => $request->input('age'),
                'status' => $request->input('status', 'solvente'),
            ]);

            return response()->json([
                'success' => true,
                'data' => new AcademyStudentResource($student),
                'message' => 'Student created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified student in storage.
     */
    public function update(UpdateAcademyStudentRequest $request, Academy $academy, AcademyStudent $student): JsonResponse
    {
        try {
            // Verify the student belongs to the academy
            if ($student->academy_id !== $academy->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student does not belong to this academy'
                ], 404);
            }

            $student->update($request->only(['name', 'age', 'status']));

            return response()->json([
                'success' => true,
                'data' => new AcademyStudentResource($student->fresh()),
                'message' => 'Student updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Academy $academy, AcademyStudent $student): JsonResponse
    {
        // Authorization check
        if (Gate::denies('delete', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to delete this student.'
            ], 403);
        }

        try {
            // Verify the student belongs to the academy
            if ($student->academy_id !== $academy->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student does not belong to this academy'
                ], 404);
            }

            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting student: ' . $e->getMessage()
            ], 500);
        }
    }
}

