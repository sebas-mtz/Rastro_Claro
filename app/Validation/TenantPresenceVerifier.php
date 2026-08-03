<?php

namespace App\Validation;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Limita las reglas `exists` y `unique` a los registros de la cuenta activa.
 *
 * Sin esto, un `exists:animals,id` acepta el id de un ejemplar de otra cuenta:
 * el scope global de Eloquent no interviene en las consultas del validador.
 */
class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    /** @var array<string>|null Tablas a filtrar, resueltas una sola vez. */
    private ?array $tablasProtegidas = null;

    protected function table($table)
    {
        $query = parent::table($table);

        // Se filtra por el rancho de quien hace la petición, no por su id
        // personal: varias personas pueden compartir una misma cuenta y todas
        // deben poder referirse a los mismos animales, lotes y razas.
        $cuentaId = AppServiceProvider::cuentaActiva();

        if ($cuentaId && in_array($table, $this->tablasProtegidas(), true)) {
            $query->where($table.'.owner_id', $cuentaId);
        }

        return $query;
    }

    /**
     * La lista se arma a partir de los modelos declarados en tenancy.models,
     * que es la fuente de verdad de qué es privado por cuenta. Antes se leía
     * `tenancy.tables`, pero esa lista existe para la migración retroactiva de
     * owner_id y no incluye las tablas que ya nacieron con la columna, así que
     * dejaba sin filtrar módulos completos (costos, razas, bajas, documentos,
     * valuaciones y trabajadores, entre otros).
     *
     * @return array<string>
     */
    private function tablasProtegidas(): array
    {
        if ($this->tablasProtegidas !== null) {
            return $this->tablasProtegidas;
        }

        $tablas = [];

        foreach (config('tenancy.models', []) as $modelClass) {
            if (is_a($modelClass, Model::class, true)) {
                $tablas[] = (new $modelClass)->getTable();
            }
        }

        // Se conserva la lista histórica por si alguna tabla participa en
        // tenancy sin tener un modelo declarado.
        $tablas = array_merge($tablas, config('tenancy.tables', []));

        return $this->tablasProtegidas = array_values(array_unique($tablas));
    }
}
