<?php

namespace App\Http\Requests;

use App\Models\ActividadTrabajador;
use App\Models\Trabajador;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreActividadTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can('registrarActividad', Trabajador::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'trabajador_id' => ['required', Rule::exists('trabajadores', 'id')],
            'tipo_actividad' => ['required', Rule::in(array_keys(ActividadTrabajador::TIPOS))],

            'animal_id' => ['nullable', Rule::exists('animals', 'id')],
            'lote_id' => ['nullable', Rule::exists('lotes', 'id')],
            'faena_id' => ['nullable', Rule::exists('faenas', 'id')],

            'fecha' => 'required|date|before_or_equal:today',
            'hora_inicio' => 'nullable|date_format:H:i',
            // Regla directa de Laravel: la hora final debe ser posterior.
            'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio',

            'modalidad_pago' => ['required', Rule::in(array_keys(ActividadTrabajador::MODALIDADES_PAGO))],
            'horas_trabajadas' => 'nullable|numeric|min:0|max:24',
            'jornadas' => 'nullable|numeric|min:0|max:31',

            'costo_hora' => 'nullable|numeric|min:0|max:99999999.99',
            'costo_jornada' => 'nullable|numeric|min:0|max:99999999.99',

            'distribuir_entre_animales' => 'boolean',

            'descripcion' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'trabajador_id.required' => 'Selecciona al trabajador que realizó la actividad.',
            'trabajador_id.exists' => 'El trabajador seleccionado no existe.',
            'tipo_actividad.required' => 'Indica qué tipo de actividad se realizó.',
            'fecha.before_or_equal' => 'La fecha de la actividad no puede ser futura.',
            'hora_fin.after' => 'La hora de finalización debe ser posterior a la de inicio.',
            'horas_trabajadas.min' => 'Las horas trabajadas no pueden ser negativas.',
            'horas_trabajadas.max' => 'Una actividad no puede durar más de 24 horas en un mismo día.',
            'jornadas.min' => 'Las jornadas no pueden ser negativas.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $trabajador = Trabajador::find($this->trabajador_id);

            if (! $trabajador) {
                return;   // ya lo reportó la regla exists
            }

            // Una persona inactiva no recibe trabajo nuevo. Su historial se
            // conserva y se sigue consultando; lo que se impide es sumarle
            // actividades después de su baja.
            if (! $trabajador->activo) {
                $validator->errors()->add(
                    'trabajador_id',
                    'Este trabajador está inactivo. Reactívalo antes de asignarle nuevas actividades.'
                );
            }

            // Un ejemplar concreto y un lote a la vez vuelve ambiguo el reparto.
            if ($this->animal_id && $this->lote_id && $this->boolean('distribuir_entre_animales')) {
                $validator->errors()->add(
                    'lote_id',
                    'Elige repartir entre el lote o dirigir la actividad a un solo ejemplar, no ambos.'
                );
            }

            $this->validarTiempo($validator);
        });
    }

    /**
     * El importe depende del tiempo: si no hay forma de conocerlo, se dice,
     * en vez de guardar un costo de cero que parecería trabajo sin costo.
     */
    private function validarTiempo(Validator $validator): void
    {
        if ($this->modalidad_pago === ActividadTrabajador::PAGO_JORNADA) {
            return;   // sin jornadas explícitas se asume una completa
        }

        $tieneHoras = $this->filled('horas_trabajadas');
        $tieneReloj = $this->filled('hora_inicio') && $this->filled('hora_fin');

        if (! $tieneHoras && ! $tieneReloj) {
            $validator->errors()->add(
                'horas_trabajadas',
                'Captura las horas trabajadas o las horas de inicio y finalización.'
            );
        }
    }
}
