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
            'name' => ['string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('areas', 'slug')->ignore($areaId)],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_reservable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'], // 10MB max per image
            'remove_file_ids' => ['nullable', 'array'],
            'remove_file_ids.*' => ['integer', 'exists:files,id'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'min:1', 'max:7'], // 1=lunes, 7=domingo
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_open' => ['required_with:schedules', 'boolean'],
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
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, JPG, GIF, or WebP format.',
            'images.*.max' => 'Each image must be smaller than 10MB.',
            'remove_file_ids.*.exists' => 'One or more file IDs do not exist.',
            'schedules.*.day_of_week.required_with' => 'Day of week is required when schedules are provided.',
            'schedules.*.day_of_week.integer' => 'Day of week must be an integer (1-7).',
            'schedules.*.day_of_week.min' => 'Day of week must be between 1 (Monday) and 7 (Sunday).',
            'schedules.*.day_of_week.max' => 'Day of week must be between 1 (Monday) and 7 (Sunday).',
            'schedules.*.start_time.required_with' => 'Start time is required when schedules are provided.',
            'schedules.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'schedules.*.end_time.required_with' => 'End time is required when schedules are provided.',
            'schedules.*.end_time.date_format' => 'End time must be in HH:MM format.',
            'schedules.*.end_time.after' => 'End time must be after start time.',
            'schedules.*.is_open.required_with' => 'Is open status is required when schedules are provided.',
        ];
    }
}

