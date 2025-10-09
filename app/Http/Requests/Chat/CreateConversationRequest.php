<?php declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateConversationRequest extends FormRequest
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
            'peer_email' => [
                'required_without:peer_id',
                'email',
                'exists:users,email',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->email) {
                        $fail('You cannot start a conversation with yourself.');
                    }
                },
            ],
            'peer_id' => [
                'required_without:peer_email',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->id) {
                        $fail('You cannot start a conversation with yourself.');
                    }
                },
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
            'peer_email.required_without' => 'Either peer email or peer ID is required.',
            'peer_email.email' => 'The peer email must be a valid email address.',
            'peer_email.exists' => 'The specified user does not exist.',
            'peer_id.required_without' => 'Either peer email or peer ID is required.',
            'peer_id.integer' => 'The peer ID must be an integer.',
            'peer_id.exists' => 'The specified user does not exist.',
        ];
    }
}
