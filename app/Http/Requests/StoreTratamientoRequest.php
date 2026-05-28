<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTratamientoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'animal_id'   => ['required', 'exists:animals,id'],
            'salud_id'    => ['nullable', 'exists:eventos_salud,id'],
            'nombre'      => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'   => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado'      => ['nullable', 'in:activo,completado'],
            'notas'       => ['nullable', 'string'],
            'responsable' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}