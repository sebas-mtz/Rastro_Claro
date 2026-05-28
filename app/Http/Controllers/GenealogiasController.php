<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Inertia\Inertia;
use Inertia\Response;

class GenealogiasController extends Controller
{
    /**
     * GET /genealogias/{animal}
     */
    public function show(Animal $animal): Response
    {
        return Inertia::render('Genealogias/Show', [
            'animal'          => $this->formatAnimal($animal),
            'arbol_ancestros' => $this->construirAncestros($animal->id, 4),
            'descendientes'   => $this->construirDescendientes($animal->id, 3),
        ]);
    }

    // ─── Ancestros ────────────────────────────────────────────────────────

    /**
     * Carga todas las generaciones de ancestros usando una query por generación
     * (máximo 4 queries para 4 generaciones) y arma el árbol recursivo.
     *
     * Retorna: ['padre' => nodo|null, 'madre' => nodo|null]
     * donde nodo = [...campos del animal, 'padre' => ..., 'madre' => ...]
     */
    private function construirAncestros(int $animalId, int $generaciones): array
    {
        $niveles = $this->cargarNivelesAncestros($animalId, $generaciones);
        return $this->armarArbolAncestros($animalId, $niveles, 1, $generaciones);
    }

    /**
     * Carga generación por generación acumulando solo los ids necesarios.
     *
     * @return array  [
     *   1 => ['map' => [animalId => [madre_id, padre_id]], 'datos' => [id => formatAnimal]],
     *   2 => [...],
     *   ...
     * ]
     */
    private function cargarNivelesAncestros(int $raizId, int $maxGen): array
    {
        $niveles     = [];
        $idsActuales = [$raizId];

        for ($gen = 1; $gen <= $maxGen; $gen++) {
            if (empty($idsActuales)) break;

            // 1) Leer madre_id / padre_id de los animales del nivel anterior
            $parentescos = Animal::whereIn('id', $idsActuales)
                ->get(['id', 'madre_id', 'padre_id']);

            $map            = [];
            $idsSiguientes  = [];

            foreach ($parentescos as $a) {
                $map[$a->id] = ['madre_id' => $a->madre_id, 'padre_id' => $a->padre_id];
                if ($a->madre_id) $idsSiguientes[] = $a->madre_id;
                if ($a->padre_id) $idsSiguientes[] = $a->padre_id;
            }

            $idsSiguientes = array_values(array_unique(array_filter($idsSiguientes)));

            if (empty($idsSiguientes)) break;

            // 2) Cargar datos completos de esa generación
            $datos = Animal::whereIn('id', $idsSiguientes)
                ->get()
                ->keyBy('id')
                ->map(fn($a) => $this->formatAnimal($a))
                ->toArray();

            $niveles[$gen] = compact('map', 'datos');

            $idsActuales = $idsSiguientes;
        }

        return $niveles;
    }

    /**
     * Arma el árbol de forma recursiva usando los datos ya cargados en memoria.
     * No hace más queries.
     */
    private function armarArbolAncestros(int $animalId, array $niveles, int $genActual, int $maxGen): array
    {
        if ($genActual > $maxGen || !isset($niveles[$genActual])) {
            return ['padre' => null, 'madre' => null];
        }

        $map   = $niveles[$genActual]['map']   ?? [];
        $datos = $niveles[$genActual]['datos']  ?? [];

        $parentesco = $map[$animalId] ?? null;
        $madreId    = $parentesco['madre_id'] ?? null;
        $padreId    = $parentesco['padre_id'] ?? null;

        $nodoMadre = null;
        $nodoPadre = null;

        if ($madreId && isset($datos[$madreId])) {
            $nodoMadre = array_merge(
                $datos[$madreId],
                $this->armarArbolAncestros($madreId, $niveles, $genActual + 1, $maxGen)
            );
        }

        if ($padreId && isset($datos[$padreId])) {
            $nodoPadre = array_merge(
                $datos[$padreId],
                $this->armarArbolAncestros($padreId, $niveles, $genActual + 1, $maxGen)
            );
        }

        return ['padre' => $nodoPadre, 'madre' => $nodoMadre];
    }

    // ─── Descendientes ────────────────────────────────────────────────────

    /**
     * Carga todos los descendientes usando una query por generación (máx 3),
     * luego arma el árbol en memoria.
     */
    private function construirDescendientes(int $animalId, int $generaciones): array
    {
        $niveles     = [];
        $idsActuales = [$animalId];

        for ($gen = 1; $gen <= $generaciones; $gen++) {
            if (empty($idsActuales)) break;

            $hijos = Animal::where(function ($q) use ($idsActuales) {
                    $q->whereIn('madre_id', $idsActuales)
                      ->orWhereIn('padre_id', $idsActuales);
                })
                ->get()
                ->map(fn($a) => $this->formatAnimal($a))
                ->toArray();

            if (empty($hijos)) break;

            $niveles[$gen] = $hijos;
            $idsActuales   = array_column($hijos, 'id');
        }

        return $this->armarArbolDescendientes($animalId, $niveles, 1, $generaciones);
    }

    private function armarArbolDescendientes(int $animalId, array $niveles, int $genActual, int $maxGen): array
    {
        if ($genActual > $maxGen || !isset($niveles[$genActual])) {
            return [];
        }

        $hijosDirectos = array_values(array_filter(
            $niveles[$genActual],
            fn($a) => $a['madre_id'] === $animalId || $a['padre_id'] === $animalId
        ));

        return array_map(function ($hijo) use ($niveles, $genActual, $maxGen) {
            return array_merge(
                $hijo,
                ['hijos' => $this->armarArbolDescendientes($hijo['id'], $niveles, $genActual + 1, $maxGen)]
            );
        }, $hijosDirectos);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function formatAnimal(Animal $animal): array
    {
        return [
            'id'        => $animal->id,
            'arete'     => $animal->arete,
            'alias'     => $animal->alias,
            'sexo'      => $animal->sexo,
            'raza'      => $animal->raza,
            'especie'   => $animal->especie,
            'fecha_nac' => $animal->fecha_nac,
            'estado'    => $animal->estado_productivo,
            'madre_id'  => $animal->madre_id,
            'padre_id'  => $animal->padre_id,
        ];
    }
}