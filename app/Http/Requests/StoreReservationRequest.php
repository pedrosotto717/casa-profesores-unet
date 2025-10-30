<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\FutureDateTime;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
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
            'starts_at' => ['required', 'date', new FutureDateTime()],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'starts_at.required' => 'La fecha y hora de inicio es requerida.',
            'starts_at.date' => 'La fecha de inicio debe ser una fecha válida.',
            'starts_at.after' => 'La fecha de inicio debe ser posterior a la fecha actual.',
            'ends_at.required' => 'La fecha y hora de fin es requerida.',
            'ends_at.date' => 'La fecha de fin debe ser una fecha válida.',
            'ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'title.string' => 'El título debe ser un texto.',
            'title.max' => 'El título no puede exceder los 180 caracteres.',
            'notes.string' => 'Las notas deben ser un texto.',
            'notes.max' => 'Las notas no pueden exceder los 500 caracteres.',
        ];
    }
}
