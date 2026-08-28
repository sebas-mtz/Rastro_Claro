<?php

namespace App\Http\Requests;

use App\Models\EventoSalud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventoSaludRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['sometimes','nullable','exists:animals,id',],

            'lote_id' => ['sometimes','nullable','exists:lotes,id',],

            'tipo' => [
                'sometimes',
                Rule::in([
                    EventoSalud::TIPO_CONSULTA,
                    EventoSalud::TIPO_VACUNACION,
                    EventoSalud::TIPO_REVISION,
                    EventoSalud::TIPO_EMERGENCIA,
                ]),
            ],

            'vacuna_id' => ['sometimes','nullable','exists:vacunas,id',],

            'fecha_programada' => ['sometimes','date',],

            'fecha_aplicacion' => ['sometimes','nullable','date',],

            'diagnostico' => ['sometimes','nullable','string','max:1000',],

            'observaciones' => ['sometimes','nullable','string',],

            'estado' => ['sometimes',Rule::in([EventoSalud::ESTADO_PENDIENTE,
                    EventoSalud::ESTADO_APLICADA,
                    EventoSalud::ESTADO_VENCIDA,
                ]),
            ],
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