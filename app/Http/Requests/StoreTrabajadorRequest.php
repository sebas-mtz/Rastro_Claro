<?php

namespace App\Http\Requests;

use App\Models\Trabajador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can('create', Trabajador::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'nullable|string|max:120',

            // El formato oficial mexicano: 18 y 13 caracteres respectivamente.
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
            'activo' => 'boolean',

            'contacto_emergencia' => 'nullable|string|max:150',
            'telefono_emergencia' => 'nullable|string|max:30',
            'observaciones' => 'nullable|string|max:2000',

            // Vínculo opcional con una cuenta del sistema. Único: una cuenta no
            // puede pertenecer a dos trabajadores.
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('trabajadores', 'user_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del trabajador es obligatorio.',
            'puesto_id.required' => 'Selecciona el puesto del trabajador.',
            'puesto_id.exists' => 'El puesto seleccionado no existe en el catálogo.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'rfc.min' => 'El RFC debe tener 12 o 13 caracteres.',
            'sueldo.min' => 'El sueldo no puede ser negativo.',
            'costo_jornada.min' => 'El costo por jornada no puede ser negativo.',
            'costo_hora.min' => 'El costo por hora no puede ser negativo.',
            'fecha_contratacion.before_or_equal' => 'La fecha de contratación no puede ser futura.',
            'user_id.unique' => 'Esa cuenta ya está enlazada a otro trabajador.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // CURP y RFC se guardan siempre en mayúsculas, como en los documentos.
        $this->merge(array_filter([
            'curp' => $this->curp ? strtoupper(trim($this->curp)) : null,
            'rfc' => $this->rfc ? strtoupper(trim($this->rfc)) : null,
        ], fn ($valor) => $valor !== null));
    }
}
