<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Única fuente de reglas de login. Se mantiene al pasar de mock a BD.
 * Si falla, Laravel responde 422 con { message, errors }.
 */
class LoginRequest extends FormRequest
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
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'digits:8'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'role' => ['required', 'in:student,parent,staff'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'El DNI es obligatorio.',
            'identifier.digits' => 'El DNI debe tener 8 dígitos.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.max' => 'La contraseña no puede superar :max caracteres.',
            'role.required' => 'Debe seleccionar un perfil de acceso.',
            'role.in' => 'El perfil de acceso no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'identifier' => 'DNI',
            'password' => 'contraseña',
            'role' => 'perfil',
        ];
    }
}
