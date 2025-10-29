<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAporteRequest extends FormRequest
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
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'moneda' => ['sometimes', 'string', 'in:USD,VES,COP,EUR'],
            'aporte_date' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'moneda.in' => 'La moneda debe ser USD, VES, COP o EUR.',
            'aporte_date.date' => 'La fecha del aporte debe ser una fecha válida.',
            'aporte_date.before_or_equal' => 'La fecha del aporte no puede ser futura.',
        ];
    }
}
