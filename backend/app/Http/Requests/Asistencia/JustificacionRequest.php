<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class JustificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer'],
            'type' => ['required', 'in:medical,early_exit,tardy_excuse,absence'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'child_id.required' => 'Debe indicar el estudiante.',
            'type.required' => 'El tipo de solicitud es obligatorio.',
            'type.in' => 'El tipo de solicitud no es válido.',
            'event_date.required' => 'La fecha del evento es obligatoria.',
            'event_date.date' => 'La fecha del evento no es válida.',
            'reason.required' => 'El motivo es obligatorio.',
            'reason.min' => 'El motivo debe tener al menos :min caracteres.',
        ];
    }
}
