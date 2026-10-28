<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DetectAccesoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:nfc,qr'],
            'role' => ['required', 'in:student,parent,staff'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'El método de ingreso no es válido.',
            'role.in' => 'El perfil de acceso no es válido.',
        ];
    }
}
