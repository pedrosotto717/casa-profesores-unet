<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class SetPasswordController extends Controller
{
    /**
     * Set password for invited user using auth code.
     */
    public function setPassword(Request $request): JsonResponse
    {
        $request->validate([
            'auth_code' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'auth_code.required' => 'El código de autenticación es obligatorio.',
            'auth_code.size' => 'El código de autenticación debe tener exactamente 64 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password_confirmation.required' => 'La confirmación de contraseña es obligatoria.',
        ]);

        // Find user by auth code
        $user = User::where('auth_code', $request->auth_code)
                    ->where('auth_code_expires_at', '>', now())
                    ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'auth_code' => 'El código de autenticación es inválido o ha expirado.'
            ]);
        }

        // Update user password and clear auth code
        $user->update([
            'password' => Hash::make($request->password),
            'auth_code' => null,
            'auth_code_expires_at' => null,
        ]);

        // Create token for immediate login
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'status' => $user->status->value,
                ],
                'token' => $token,
            ],
            'message' => 'Contraseña establecida exitosamente. Has iniciado sesión automáticamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}

