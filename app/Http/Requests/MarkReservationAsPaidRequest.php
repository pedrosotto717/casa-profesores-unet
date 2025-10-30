<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkReservationAsPaidRequest extends FormRequest
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
            'fecha_pago' => ['required', 'date'],
            'moneda' => ['nullable', 'string', 'in:USD,VES,COP,EUR'],
            'monto' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fecha_pago.required' => 'La fecha de pago es requerida.',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida.',
            'moneda.in' => 'La moneda debe ser USD, VES, COP o EUR.',
            'monto.numeric' => 'El monto debe ser un número válido.',
            'monto.min' => 'El monto debe ser mayor o igual a 0.',
        ];
    }
}
