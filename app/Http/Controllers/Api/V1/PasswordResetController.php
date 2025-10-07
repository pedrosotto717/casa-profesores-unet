<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\SendPulseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PasswordResetController extends Controller
{
    public function __construct(
        private SendPulseService $sendPulseService
    ) {}

    /**
     * Send password reset code to user's email
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];
        
        try {
            // Find user by email
            $user = User::where('email', $email)->first();
            
            // Always return success message for security (don't reveal if email exists)
            $response = [
                'success' => true,
                'message' => 'Si el email existe en nuestro sistema, hemos enviado un código de recuperación.',
                'meta' => [
                    'version' => 'v1',
                ],
            ];
            
            // If user doesn't exist, return early with success message
            if (!$user) {
                return response()->json($response);
            }
            
            // Check if user is rejected (cannot reset password)
            if ($user->status === \App\Enums\UserStatus::Rechazado) {
                return response()->json($response);
            }
            
            // Generate 6-digit code
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store hashed code and expiration time
            $expiresAt = now()->addMinutes(15);
            
            $user->update([
                'auth_code' => hash('sha256', $code),
                'auth_code_expires_at' => $expiresAt,
                'auth_code_attempts' => 0,
                'last_code_sent_at' => now(),
            ]);
            
            // Send email with the code
            $emailResult = $this->sendPulseService->sendPasswordResetCodeEmail(
                $user->email,
                $user->name,
                $code
            );
            
            // Log email sending result (but don't expose to user)
            if (!$emailResult['ok']) {
                Log::error('Password reset email failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $emailResult['error'] ?? 'Unknown error',
                ]);
            } else {
                Log::info('Password reset code sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
            
            // Log audit event
            $this->logPasswordResetRequest($user->id, $request->ip(), $request->userAgent());
            
        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return response()->json($response);
    }

    /**
     * Reset password using the verification code
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];
        $newPassword = $validated['password'];
        
        try {
            DB::beginTransaction();
            
            // Find user by email
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una cuenta con este email.',
                ], 400);
            }
            
            // Verify code (already validated in Form Request, but double-check)
            if (!$user->auth_code || !hash_equals($user->auth_code, hash('sha256', $code))) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación es inválido.',
                ], 400);
            }
            
            // Check if code has expired
            if (now()->isAfter($user->auth_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación ha expirado.',
                ], 400);
            }
            
            // Update password and clear reset code
            $user->update([
                'password' => Hash::make($newPassword),
                'auth_code' => null,
                'auth_code_expires_at' => null,
                'auth_code_attempts' => 0,
            ]);
            
            // Revoke all existing tokens (force logout)
            $user->tokens()->delete();
            
            // Log audit event
            $this->logPasswordResetCompleted($user->id, $request->ip(), $request->userAgent());
            
            DB::commit();
            
            Log::info('Password reset completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente. Inicia sesión con tu nueva contraseña.',
                'meta' => [
                    'version' => 'v1',
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Password reset failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al restablecer la contraseña. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Log password reset request in audit logs
     */
    private function logPasswordResetRequest(int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => $userId,
                'target_user_id' => $userId,
                'action' => 'password_reset_requested',
                'old_data' => null,
                'new_data' => [
                    'timestamp' => now()->toISOString(),
                    'ip_address' => $ipAddress,
                ],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log password reset request audit', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log password reset completion in audit logs
     */
    private function logPasswordResetCompleted(int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => $userId,
                'target_user_id' => $userId,
                'action' => 'password_reset_completed',
                'old_data' => null,
                'new_data' => [
                    'timestamp' => now()->toISOString(),
                    'ip_address' => $ipAddress,
                ],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log password reset completion audit', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}