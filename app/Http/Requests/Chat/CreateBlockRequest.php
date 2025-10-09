<?php declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CreateBlockRequest extends FormRequest
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
            'blocked_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->id) {
                        $fail('You cannot block yourself.');
                    }
                },
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
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
            'blocked_user_id.required' => 'The blocked user ID is required.',
            'blocked_user_id.integer' => 'The blocked user ID must be an integer.',
            'blocked_user_id.exists' => 'The specified user does not exist.',
            'reason.string' => 'The reason must be a string.',
            'reason.max' => 'The reason may not be greater than 255 characters.',
            'expires_at.date' => 'The expiration date must be a valid date.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }
}
