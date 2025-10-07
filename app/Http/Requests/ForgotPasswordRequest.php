<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ForgotPasswordRequest extends FormRequest
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
            // Check rate limiting for forgot password requests
            $key = 'forgot-password:' . $this->ip() . ':' . $this->input('email');
            
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                
                throw ValidationException::withMessages([
                    'email' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
                ]);
            }
            
            // Check if last code was sent too recently (1 minute cooldown)
            $user = \App\Models\User::where('email', $this->input('email'))->first();
            if ($user && $user->last_code_sent_at) {
                $lastSent = $user->last_code_sent_at;
                $cooldownEnd = $lastSent->addMinutes(1);
                
                if (now()->isBefore($cooldownEnd)) {
                    $remainingSeconds = now()->diffInSeconds($cooldownEnd);
                    
                    throw ValidationException::withMessages([
                        'email' => "Debes esperar {$remainingSeconds} segundos antes de solicitar otro código.",
                    ]);
                }
            }
        });
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Increment rate limiter
        $key = 'forgot-password:' . $this->ip() . ':' . $this->input('email');
        RateLimiter::hit($key, 3600); // 1 hour decay
    }
}