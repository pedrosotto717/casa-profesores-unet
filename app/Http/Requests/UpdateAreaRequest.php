<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAreaRequest extends FormRequest
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
        $areaId = $this->route('area')->id;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('areas', 'name')->ignore($areaId)],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('areas', 'slug')->ignore($areaId)],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'The area name is required.',
            'name.unique' => 'An area with this name already exists.',
            'slug.unique' => 'An area with this slug already exists.',
            'capacity.min' => 'Capacity must be at least 1.',
            'hourly_rate.min' => 'Hourly rate cannot be negative.',
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, JPG, GIF, or WebP format.',
            'images.*.max' => 'Each image must be smaller than 10MB.',
            'remove_file_ids.*.exists' => 'One or more file IDs do not exist.',
        ];
    }
}

