<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class AprobarIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'min:4', 'max:40'],
            'reason' => ['required', 'string', 'min:4', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'El identificador del estudiante es obligatorio.',
            'student_id.min' => 'El identificador del estudiante debe tener al menos :min caracteres.',
            'reason.required' => 'El motivo de contingencia es obligatorio.',
            'reason.min' => 'El motivo debe tener al menos :min caracteres.',
        ];
    }
}
