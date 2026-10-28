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
        if ($this->has('identifier')) {
            $this->merge([
                'identifier' => preg_replace('/\D/', '', (string) $this->input('identifier')),
            ]);
        }
        if ($this->has('code')) {
            $this->merge([
                'code' => preg_replace('/\D/', '', (string) $this->input('code')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'digits:8'],
            'role' => ['required', 'in:student,parent,staff'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
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
