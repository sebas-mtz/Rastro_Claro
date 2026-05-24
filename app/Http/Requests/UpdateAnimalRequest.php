<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El parámetro de ruta es {animal}, así que obtenemos su id:
        $animalId = $this->route('animal')?->id;

        return [
            'especie'           => ['required', 'string', 'max:255'],
            'raza'              => ['nullable', 'string', 'max:255'],
            'arete'             => [
                'required',
                'string',
                'max:255',
                Rule::unique('animals', 'arete')->ignore($animalId),
            ],
            'sexo'              => ['required', 'in:M,F'],
            'fecha_nac'         => ['nullable', 'date'],
            'peso'              => ['nullable', 'numeric', 'min:0'],
            'BCS'               => ['nullable', 'numeric', 'min:0', 'max:9'],
            'estado_productivo' => ['nullable', 'string', 'max:255'],
            'lote_id'           => ['nullable', 'exists:lotes,id'],
        ];
    }
}
