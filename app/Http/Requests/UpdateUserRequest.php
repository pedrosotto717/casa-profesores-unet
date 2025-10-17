<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validRoles = array_column(UserRole::cases(), 'value');
        $validStatuses = array_column(UserStatus::cases(), 'value');
        $userId = $this->route('user')->id ?? null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'role' => ['sometimes', 'string', Rule::in($validRoles)],
            'status' => ['sometimes', 'string', Rule::in($validStatuses)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'responsible_email' => ['sometimes', 'nullable', 'email', 'max:180'],
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
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'role.in' => 'El rol seleccionado no es válido.',
            'status.in' => 'El estado seleccionado no es válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'responsible_email.email' => 'El formato del correo electrónico responsable no es válido.',
            'responsible_email.max' => 'El correo electrónico responsable no puede exceder los 180 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'role' => 'rol',
            'status' => 'estado',
            'password' => 'contraseña',
            'responsible_email' => 'correo electrónico responsable',
        ];
    }
}
