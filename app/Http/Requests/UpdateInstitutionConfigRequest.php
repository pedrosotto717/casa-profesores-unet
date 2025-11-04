<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInstitutionConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission' => ['required', 'string'],
            'vision' => ['required', 'string'],
            'members' => ['nullable', 'array'],
            'members.*.id' => ['required', 'string'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.role' => ['required', 'string', 'max:255'],
            'members.*.file_id' => ['nullable', 'integer', 'exists:files,id'],
            'members.*.image_url' => ['nullable', 'string'],
            'member_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            
            // Social media and contact information
            'social_media' => ['nullable', 'array'],
            'social_media.facebook' => ['nullable', 'string', 'url', 'max:500'],
            'social_media.instagram' => ['nullable', 'string', 'url', 'max:500'],
            'social_media.twitter' => ['nullable', 'string', 'url', 'max:500'],
            'social_media.email' => ['nullable', 'string', 'email', 'max:255'],
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
            'mission.required' => 'La misión es requerida.',
            'mission.string' => 'La misión debe ser un texto.',
            'vision.required' => 'La visión es requerida.',
            'vision.string' => 'La visión debe ser un texto.',
            'members.array' => 'Los miembros deben ser un arreglo.',
            'members.*.id.required' => 'El ID del miembro es requerido.',
            'members.*.id.string' => 'El ID del miembro debe ser un texto.',
            'members.*.name.required' => 'El nombre del miembro es requerido.',
            'members.*.name.string' => 'El nombre del miembro debe ser un texto.',
            'members.*.name.max' => 'El nombre del miembro no puede exceder los 255 caracteres.',
            'members.*.role.required' => 'El cargo del miembro es requerido.',
            'members.*.role.string' => 'El cargo del miembro debe ser un texto.',
            'members.*.role.max' => 'El cargo del miembro no puede exceder los 255 caracteres.',
            'members.*.image_url.string' => 'La URL de la imagen debe ser un texto.',
            'member_images.*.image' => 'El archivo debe ser una imagen válida.',
            'member_images.*.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg o webp.',
            'member_images.*.max' => 'La imagen no puede exceder los 2MB.',
        ];
    }
}
