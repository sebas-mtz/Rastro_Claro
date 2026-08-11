<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Services\EstadoProductivoService;
class StoreAnimalRequest extends FormRequest

{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'especie' => 'required|string|in:' . implode(',', array_keys(EstadoProductivoService::estadosPorEspecie())),

            'alias' => 'nullable|string|max:255',
            'raza' => 'nullable|string|max:255',

            'arete' => 'required|string|max:255',

            // nuevos campos
            'siniiga_id' => 'nullable|string|max:255',
            'identificador' => 'nullable|string|max:255',
            'numero_registro' => 'nullable|string|max:255',
            'grado_pureza' => 'nullable|string|max:255',
            'lectura_microchip' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',

            'sexo' => 'required|in:M,H',

            'fecha_nac' => 'nullable|date',

            'peso' => 'nullable|numeric',
            'BCS' => 'nullable|numeric',

        'concepcion_historica' => 'required_with:madre_id|in:monta_natural,monta_controlada,inseminacion_artificial,iatf,transferencia_embriones,fiv',
'tipo_nacimiento_historico' => 'required_with:madre_id|in:simple,gemelar,triple,cuadruple,quintuple',
'tipo_parto_origen' => 'required_with:madre_id|in:normal,distocico,cesarea',

'estado_productivo' => [
            'nullable',
            'string',
            Rule::in(EstadoProductivoService::todosLosValores()),
        ],
            'lote_id' => 'nullable|exists:lotes,id',

            // genealogía
            'madre_id' => 'nullable|exists:animals,id',
            'padre_id' => 'nullable|exists:animals,id',

            // padre externo
            'padre_externo_id' => 'nullable|exists:donadores_externos,id',
        ];
    }
}