<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EventoSalud;

class UpdateEventoSaludRequest extends FormRequest
{
    /**
     * Antes devolvía false sin condición, lo que hacía que actualizar un evento
     * de salud respondiera 403 siempre. El acceso al registro ya está acotado
     * por el scope de tenencia (owner_id) del modelo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mismas reglas que el alta, pero todas opcionales para permitir
     * actualizaciones parciales.
     */
    public function rules(): array
    {
        return [
            'animal_id'        => ['sometimes', 'nullable', 'exists:animals,id'],
            'lote_id'          => ['sometimes', 'nullable', 'exists:lotes,id'],
            'tipo'             => ['sometimes', \Illuminate\Validation\Rule::in(array_keys(EventoSalud::TIPOS))],
            'fecha_programada' => ['sometimes', 'date'],
            'fecha_aplicacion' => ['sometimes', 'nullable', 'date'],
            'diagnostico'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'tratamiento'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'vacuna_id'        => ['sometimes', 'nullable', 'exists:vacunas,id'],
            'dosis'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'costo'            => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'periodo_retiro_dias' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
            'producto'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'via_administracion' => ['sometimes', 'nullable', \Illuminate\Validation\Rule::in(array_keys(EventoSalud::VIAS_ADMINISTRACION))],
            'lote_vacuna'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'observaciones'    => ['sometimes', 'nullable', 'string'],
            'estado'           => ['sometimes', 'nullable', 'in:pendiente,aplicada,vencida'],
            'responsable'      => ['sometimes', 'nullable', 'string', 'max:150'],
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
}
