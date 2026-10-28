<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identifier = $this->input('identifier');
        if (is_string($identifier) || is_numeric($identifier)) {
            $this->merge([
                'identifier' => preg_replace('/\D/', '', (string) $identifier),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'bail', 'digits:8'],
            'role' => ['required', 'string', 'bail', 'in:student,parent,staff'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'El DNI es obligatorio.',
            'identifier.digits' => 'El DNI debe tener 8 dígitos.',
            'role.in' => 'El perfil de acceso no es válido.',
        ];
    }
}
