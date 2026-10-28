<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RecoveryConfirmRequest extends FormRequest
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
        $code = $this->input('code');
        if (is_string($code) || is_numeric($code)) {
            $this->merge([
                'code' => preg_replace('/\D/', '', (string) $code),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'bail', 'digits:8'],
            'role' => ['required', 'string', 'bail', 'in:student,parent,staff'],
            'code' => ['required', 'string', 'bail', 'digits:6'],
            'password' => ['required', 'string', 'bail', 'min:8', 'max:128'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.digits' => 'El DNI debe tener 8 dígitos.',
            'code.digits' => 'El código debe tener 6 dígitos.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'role.in' => 'El perfil de acceso no es válido.',
        ];
    }
}
