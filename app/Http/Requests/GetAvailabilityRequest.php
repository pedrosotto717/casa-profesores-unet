<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after:from'],
            'slot_minutes' => ['nullable', 'integer', 'min:15', 'max:480'], // 15 minutes to 8 hours
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
            'area_id.required' => 'El área es requerida.',
            'area_id.integer' => 'El área debe ser un número entero.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'from.date' => 'La fecha de inicio debe ser una fecha válida.',
            'to.date' => 'La fecha de fin debe ser una fecha válida.',
            'to.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'slot_minutes.integer' => 'Los minutos del slot deben ser un número entero.',
            'slot_minutes.min' => 'Los minutos del slot deben ser al menos 15.',
            'slot_minutes.max' => 'Los minutos del slot no pueden exceder 480 (8 horas).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('from')) {
            $this->merge(['from' => now()->startOfDay()->toDateString()]);
        }

        if (!$this->has('to')) {
            $maxAdvanceDays = config('reservations.max_advance_days', 30);
            $this->merge(['to' => now()->addDays($maxAdvanceDays)->toDateString()]);
        }
    }
}

