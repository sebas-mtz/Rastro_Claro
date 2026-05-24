<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permitir a cualquier usuario autenticado que llegue a este request
        return true;
    }

    public function rules(): array
    {
        return [
            'especie'           => ['required', 'string', 'max:255'],
            'raza'              => ['nullable', 'string', 'max:255'],
            'arete'             => ['required', 'string', 'max:255', 'unique:animals,arete'],
            'sexo'              => ['required', 'in:M,F'],
            'fecha_nac'         => ['nullable', 'date'],
            'peso'              => ['nullable', 'numeric', 'min:0'],
            'BCS'               => ['nullable', 'numeric', 'min:0', 'max:9'],
            'estado_productivo' => ['nullable', 'string', 'max:255'],
            'lote_id'           => ['nullable', 'exists:lotes,id'],
        ];
    }
}
