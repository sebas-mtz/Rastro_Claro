<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EventoSalud;

class StoreEventoSaludRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si tienes políticas
    }

    public function rules(): array
    {
        return [
            'animal_id'        => ['nullable', 'exists:animals,id', 'required_without:lote_id'],
            'lote_id'          => ['nullable', 'exists:lotes,id', 'required_without:animal_id'],
            'tipo'             => ['required', 'in:consulta,vacunacion,revision,emergencia'],
            'fecha_programada' => ['required', 'date'],
            'fecha_aplicacion' => ['nullable', 'date'],
            'diagnostico'      => ['nullable', 'string', 'max:255'],
            'tratamiento'      => ['nullable', 'string', 'max:255'],
            'vacuna_id'        => ['nullable', 'required_if:tipo,vacunacion', 'exists:vacunas,id'],
            'dosis'            => ['nullable', 'string', 'max:100'],
            'lote_vacuna'      => ['nullable', 'string', 'max:100'],
            'observaciones'    => ['nullable', 'string'],
            'estado'           => ['nullable', 'in:pendiente,aplicada,vencida'],
            'responsable'      => ['nullable', 'string', 'max:150'],
        ];
    }
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('animal_id') && $this->filled('lote_id')) {
                $validator->errors()->add(
                    'animal_id',
                    'Solo puedes seleccionar un animal o un lote, no ambos.'
                );
            }
        });
    }
    public function messages(): array
{
    return [
        'animal_id.required_without' => 'Debes seleccionar un animal o un lote.',
        'lote_id.required_without'   => 'Debes seleccionar un animal o un lote.',
    ];
}
}