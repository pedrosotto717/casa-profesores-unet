<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BeneficiarioParentesco;
use App\Models\Beneficiario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBeneficiarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Beneficiario::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'parentesco' => [
                'required',
                Rule::enum(BeneficiarioParentesco::class),
            ],
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
            'nombre_completo.required' => 'The beneficiary name is required.',
            'nombre_completo.max' => 'The beneficiary name may not exceed 255 characters.',
            'parentesco.required' => 'The relationship is required.',
            'parentesco.enum' => 'The relationship must be a valid option.',
        ];
    }
}
