<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->has('role'), function ($query) use ($request) {
                $query->where('role', $request->get('role'));
            })
            ->when($request->has('is_solvent'), function ($query) use ($request) {
                $query->where('is_solvent', $request->boolean('is_solvent'));
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users->items(),
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
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user,
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}
