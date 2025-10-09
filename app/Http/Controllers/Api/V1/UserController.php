<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->has('role'), function ($query) use ($request) {
                $query->where('role', $request->get('role'));
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'version' => 'v1',
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuario creado exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser(
            $user,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new UserResource($updatedUser),
            'message' => 'Usuario actualizado exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Update the authenticated user's own profile.
     * Only allows updating basic information (name, email, password).
     * Role and status cannot be modified through this endpoint.
     */
    public function updateMe(UpdateMeRequest $request): JsonResponse
    {
        $updatedUser = $this->userService->updateMe(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => new UserResource($updatedUser),
            'message' => 'Perfil actualizado exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Get pending user registrations for admin approval.
     */
    public function pendingRegistrations(Request $request): JsonResponse
    {
        $users = User::where('status', UserStatus::AprobacionPendiente)
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('responsible_email', 'like', "%{$search}%");
                });
            })
            ->when($request->has('aspired_role'), function ($query) use ($request) {
                $query->where('aspired_role', $request->get('aspired_role'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'version' => 'v1',
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ]);
    }

    /**
     * Send invitation to user (stub implementation).
     */
    public function invite(Request $request, User $user): JsonResponse
    {
        $result = $this->userService->inviteUser($user, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user_id' => $result['user_id'],
                'email' => $result['email'],
            ],
            'meta' => [
                'version' => 'v1',
            ],
        ], 202);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->userService->deleteUser($user, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}
