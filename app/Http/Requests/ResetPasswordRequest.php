<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'code.required' => 'El código de verificación es obligatorio.',
            'code.size' => 'El código debe tener exactamente 6 dígitos.',
            'code.regex' => 'El código debe contener solo números.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password_confirmation.required' => 'La confirmación de contraseña es obligatoria.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = $this->input('email');
            $code = $this->input('code');
            
            // Find user by email
            $user = \App\Models\User::where('email', $email)->first();
            
            if (!$user) {
                $validator->errors()->add('email', 'No se encontró una cuenta con este email.');
                return;
            }
            
            // Check if user has a valid auth code
            if (!$user->auth_code || !$user->auth_code_expires_at) {
                $validator->errors()->add('code', 'No hay un código de recuperación válido para este email.');
                return;
            }
            
            // Check if code has expired
            if (now()->isAfter($user->auth_code_expires_at)) {
                $validator->errors()->add('code', 'El código de recuperación ha expirado. Solicita uno nuevo.');
                return;
            }
            
            // Check if code matches (using hash comparison for security)
            if (!hash_equals($user->auth_code, hash('sha256', $code))) {
                // Increment attempts counter
                $user->increment('auth_code_attempts');
                
                // Check if max attempts exceeded
                if ($user->auth_code_attempts >= 5) {
                    // Invalidate the code
                    $user->update([
                        'auth_code' => null,
                        'auth_code_expires_at' => null,
                        'auth_code_attempts' => 0,
                    ]);
                    
                    $validator->errors()->add('code', 'Has excedido el número máximo de intentos. Solicita un nuevo código.');
                } else {
                    $remainingAttempts = 5 - $user->auth_code_attempts;
                    $validator->errors()->add('code', "Código incorrecto. Te quedan {$remainingAttempts} intentos.");
                }
                return;
            }
            
            // Check if user is rejected (cannot reset password)
            if ($user->status === \App\Enums\UserStatus::Rechazado) {
                $validator->errors()->add('email', 'Esta cuenta ha sido rechazada y no puede restablecer su contraseña.');
                return;
            }
        });
    }
}