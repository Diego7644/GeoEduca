<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class ConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school' => ['required', 'string', 'min:3', 'max:80'],
            'campus' => ['required', 'string', 'min:3', 'max:80'],
            'address' => ['required', 'string', 'min:5', 'max:120'],
            'year' => ['required', 'digits:4'],
            'entry_limit' => ['required', 'date_format:H:i'],
            'exit_time' => ['required', 'date_format:H:i'],
            'geofence_m' => ['required', 'integer', 'min:20', 'max:500'],
            'promotion_min' => ['required', 'integer', 'min:50', 'max:100'],
            'tolerance_min' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'school.required' => 'El nombre del colegio es obligatorio.',
            'entry_limit.date_format' => 'El límite de ingreso no es una hora válida.',
            'exit_time.date_format' => 'La hora de salida no es una hora válida.',
            'geofence_m.min' => 'El perímetro mínimo es de :min metros.',
            'promotion_min.min' => 'La meta de promoción no puede ser menor a :min%.',
        ];
    }
}
