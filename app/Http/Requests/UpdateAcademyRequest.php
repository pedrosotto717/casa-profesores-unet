<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAcademyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $academyId = $this->route('academy')->id;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('academies', 'name')->ignore($academyId)],
            'description' => ['nullable', 'string'],
            'lead_instructor_id' => [
                'required', 
                'integer', 
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', [UserRole::Instructor->value, UserRole::Profesor->value]);
                })
            ],
            'status' => ['nullable', 'string', Rule::in(['activa', 'cerrada', 'cancelada'])],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'], // 10MB max per image
            'remove_file_ids' => ['nullable', 'array'],
            'remove_file_ids.*' => ['integer', 'exists:files,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The academy name is required.',
            'name.unique' => 'An academy with this name already exists.',
            'lead_instructor_id.required' => 'The lead instructor is required.',
            'lead_instructor_id.exists' => 'The selected instructor does not exist or does not have the required role.',
            'status.in' => 'Status must be one of: activa, cerrada, cancelada.',
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, JPG, GIF, or WebP format.',
            'images.*.max' => 'Each image must be smaller than 10MB.',
            'remove_file_ids.*.exists' => 'One or more file IDs do not exist.',
        ];
    }
}

