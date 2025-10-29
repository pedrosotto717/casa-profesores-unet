<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAporteRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'moneda' => ['required', 'string', 'in:USD,VES,COP,EUR'],
            'aporte_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'El ID del usuario es requerido.',
            'user_id.exists' => 'El usuario especificado no existe.',
            'amount.required' => 'El monto del aporte es requerido.',
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'moneda.required' => 'La moneda es requerida.',
            'moneda.in' => 'La moneda debe ser USD, VES, COP o EUR.',
            'aporte_date.date' => 'La fecha del aporte debe ser una fecha válida.',
            'aporte_date.before_or_equal' => 'La fecha del aporte no puede ser futura.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default aporte_date to today if not provided
        if (!$this->has('aporte_date')) {
            $this->merge([
                'aporte_date' => now()->toDateString(),
            ]);
        }
    }
}
