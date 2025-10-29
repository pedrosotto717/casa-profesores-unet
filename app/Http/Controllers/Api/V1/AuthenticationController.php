<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthenticationController extends Controller
{
    /**
     * Handle user login and return API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user = Auth::user();
        
        // Check if user is pending approval
        if ($user->status === UserStatus::AprobacionPendiente) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Su cuenta está pendiente de aprobación administrativa. Por favor, espere a que un administrador revise su solicitud.'],
            ]);
        }

        // Check if user's solvency has expired
        if ($user->solvent_until && $user->solvent_until->isPast()) {
            $user->update(['status' => UserStatus::Insolvente]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Handle user logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}
