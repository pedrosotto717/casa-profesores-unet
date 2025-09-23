<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\EmailVerificationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UserService
{
    /**
     * Register a new user with local authentication.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            // SSO users won't have password; this is local registration
            'password' => Hash::make($data['password']),
            // safe initial role (NOT admin). Using enum value
            'role'     => UserRole::Usuario,
        ]);

        // Generate email verification token
        $verificationToken = $user->generateEmailVerificationToken();
        
        // Send verification email
        $this->sendEmailVerification($user, $verificationToken);

        // SPA token with Sanctum
        $token = $user->createToken('spa')->plainTextToken;

        return [
            'user' => $user, 
            'token' => $token,
            'message' => 'Usuario registrado exitosamente. Por favor verifica tu correo electrónico.'
        ];
    }

    /**
     * Promote or demote a user to a specific role.
     */
    public function promoteToRole(string $email, string $role): User
    {
        // Get all valid role values from the enum
        $allowedRoles = array_column(UserRole::cases(), 'value');
        
        if (!in_array($role, $allowedRoles, true)) {
            throw ValidationException::withMessages(['role' => 'Invalid role.']);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->role = $role;
        $user->save();

        return $user;
    }

    /**
     * Send email verification notification to user.
     */
    public function sendEmailVerification(User $user, string $token): void
    {
        try {
            // Ensure email configuration is properly set
            if (!config('mail.from.address') || !config('mail.from.name')) {
                Log::warning('Email configuration not properly set. Using defaults.');
            }
            
            $verificationUrl = config('app.url') . '/verify-email?id=' . $user->id . '&hash=' . $token;
            
            Log::info('Sending email verification to: ' . $user->email);
            Log::info('Verification URL: ' . $verificationUrl);
            Log::info('Mail config - From: ' . config('mail.from.address') . ', Name: ' . config('mail.from.name'));

            // Queue the email to avoid blocking the request
            Mail::to($user->email)->queue(new EmailVerificationNotification($user, $verificationUrl));
            
            Log::info('Email verification queued successfully to: ' . $user->email);
            
        } catch (\Throwable $th) {
            Log::error('Error sending email verification: ' . $th->getMessage());
            Log::error('Stack trace: ' . $th->getTraceAsString());
            // Don't throw exception to avoid breaking the registration process
        }
    }

    /**
     * Verify user's email address.
     */
    public function verifyEmail(int $userId, string $token): array
    {
        $user = User::findOrFail($userId);

        if ($user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya ha sido verificado.'
            ];
        }

        if ($user->email_verification_token !== $token) {
            return [
                'success' => false,
                'message' => 'Token de verificación inválido o expirado.'
            ];
        }

        $user->markEmailAsVerified();

        return [
            'success' => true,
            'message' => 'Correo electrónico verificado exitosamente.'
        ];
    }

}
