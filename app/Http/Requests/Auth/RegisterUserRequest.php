<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name'     => ['required','string','max:150'],
            'email'    => ['required','string','email','max:180','unique:users,email','ends_with:@unet.edu.ve'],
            'password' => ['required','string','min:8'],
            'aspired_role' => ['required','string','in:profesor,estudiante'],
            'responsible_email' => ['required_if:aspired_role,estudiante','nullable','email','ends_with:@unet.edu.ve'],
        ];
    }
}
