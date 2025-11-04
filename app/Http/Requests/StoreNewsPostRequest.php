<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreNewsPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role->value === 'administrador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede exceder 255 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser "draft" o "published".',
            'featured_image.image' => 'El archivo debe ser una imagen.',
            'featured_image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp.',
            'featured_image.max' => 'La imagen no puede superar 2MB.',
        ];
    }
}

