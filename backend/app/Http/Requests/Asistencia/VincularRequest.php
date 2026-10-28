<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class VincularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('dni')) {
            $this->merge([
                'dni' => preg_replace('/\D/', '', (string) $this->input('dni')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'digits:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI del estudiante es obligatorio.',
            'dni.digits' => 'El DNI debe tener 8 dígitos.',
        ];
    }
}
