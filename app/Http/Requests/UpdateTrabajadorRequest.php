<?php

namespace App\Http\Requests;

use App\Models\Trabajador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trabajador = $this->route('trabajador');

        return Auth::user()?->can('update', $trabajador) ?? false;
    }

    public function rules(): array
    {
        $trabajador = $this->route('trabajador');

        return [
            'nombre' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'nullable|string|max:120',

            'curp' => 'nullable|string|size:18',
            'rfc' => 'nullable|string|min:12|max:13',

            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date|before:today',

            'fecha_contratacion' => 'nullable|date|before_or_equal:today',
            'puesto_id' => ['required', Rule::exists('puestos_trabajador', 'id')],
            'area' => 'nullable|string|max:80',
            'tipo_contratacion' => ['nullable', Rule::in(array_keys(Trabajador::TIPOS_CONTRATACION))],

            'sueldo' => 'nullable|numeric|min:0|max:99999999.99',
            'costo_jornada' => 'nullable|numeric|min:0|max:99999999.99',
            'costo_hora' => 'nullable|numeric|min:0|max:99999999.99',

            'horario' => 'nullable|string|max:120',

            'contacto_emergencia' => 'nullable|string|max:150',
            'telefono_emergencia' => 'nullable|string|max:30',
            'observaciones' => 'nullable|string|max:2000',

            'user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('trabajadores', 'user_id')->ignore($trabajador?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del trabajador es obligatorio.',
            'puesto_id.required' => 'Selecciona el puesto del trabajador.',
            'sueldo.min' => 'El sueldo no puede ser negativo.',
            'costo_jornada.min' => 'El costo por jornada no puede ser negativo.',
            'costo_hora.min' => 'El costo por hora no puede ser negativo.',
            'user_id.unique' => 'Esa cuenta ya está enlazada a otro trabajador.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'curp' => $this->curp ? strtoupper(trim($this->curp)) : null,
            'rfc' => $this->rfc ? strtoupper(trim($this->rfc)) : null,
        ], fn ($valor) => $valor !== null));
    }
}
