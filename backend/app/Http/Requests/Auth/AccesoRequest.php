<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AccesoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('credential')) {
            $this->merge([
                'credential' => preg_replace('/\D/', '', (string) $this->input('credential')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:nfc,qr'],
            'role' => ['required', 'in:student,parent,staff'],
            'credential' => ['required', 'digits:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'credential.digits' => 'El carnet debe informar un DNI de 8 dígitos.',
            'method.in' => 'El método de ingreso no es válido.',
            'role.in' => 'El perfil de acceso no es válido.',
        ];
    }
}
