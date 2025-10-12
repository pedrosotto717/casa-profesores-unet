<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreAcademyStudentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'status' => ['nullable', 'string', 'in:solvente,insolvente'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The student name is required.',
            'name.max' => 'The student name may not exceed 200 characters.',
            'age.required' => 'The student age is required.',
            'age.integer' => 'The age must be a valid number.',
            'age.min' => 'The age must be at least 1.',
            'age.max' => 'The age may not exceed 120.',
            'status.in' => 'The status must be either solvente or insolvente.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'solvente',
            ]);
        }
    }
}

