<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAcademyRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:150', 'unique:academies,name'],
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
            'schedules' => ['nullable', 'array'],
            'schedules.*.area_id' => ['required_with:schedules', 'integer', 'exists:areas,id'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'min:1', 'max:7'], // 1=lunes, 7=domingo
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'schedules.*.area_id.required_with' => 'Area ID is required when schedules are provided.',
            'schedules.*.area_id.exists' => 'The selected area does not exist.',
            'schedules.*.day_of_week.required_with' => 'Day of week is required when schedules are provided.',
            'schedules.*.day_of_week.integer' => 'Day of week must be an integer (1-7).',
            'schedules.*.day_of_week.min' => 'Day of week must be between 1 (Monday) and 7 (Sunday).',
            'schedules.*.day_of_week.max' => 'Day of week must be between 1 (Monday) and 7 (Sunday).',
            'schedules.*.start_time.required_with' => 'Start time is required when schedules are provided.',
            'schedules.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'schedules.*.end_time.required_with' => 'End time is required when schedules are provided.',
            'schedules.*.end_time.date_format' => 'End time must be in HH:MM format.',
            'schedules.*.end_time.after' => 'End time must be after start time.',
            'schedules.*.capacity.integer' => 'Capacity must be an integer.',
            'schedules.*.capacity.min' => 'Capacity must be at least 1.',
            'schedules.*.capacity.max' => 'Capacity cannot exceed 100.',
        ];
    }
}

