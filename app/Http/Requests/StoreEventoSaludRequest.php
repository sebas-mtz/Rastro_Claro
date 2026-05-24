<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EventoSalud;

class StoreEventoSaludRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id'        => ['required', 'exists:animals,id'],
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
            // ── Nuevo campo de costo ──────────────────────────────────────
            'costo'            => ['nullable', 'numeric', 'min:0'],
            'etapa_animal'     => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'vacuna_id.required_if' => 'Debes seleccionar una vacuna cuando el tipo es vacunación.',
            'animal_id.exists'      => 'El animal seleccionado no existe.',
        ];
    }
}
