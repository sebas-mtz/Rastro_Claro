<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\ServicioReproductivo;
use Inertia\Inertia;
use Inertia\Response;

class GenealogiasController extends Controller
{
    /**
     * GET /genealogias/{animal}
     *
     * Devuelve la página de genealogía con:
     *  - arbol_ancestros: árbol hacia arriba, hasta 4 generaciones
     *  - descendientes:   árbol hacia abajo, hasta 3 generaciones
     */
    public function show(Animal $animal): Response
    {
        return Inertia::render('Genealogias/Show', [
            'animal'          => $this->formatAnimal($animal),
            'arbol_ancestros' => $this->construirAncestros($animal, 4),
            'descendientes'   => $this->construirDescendientes($animal, 3),
        ]);
    }

    // ─── Árbol de ancestros ───────────────────────────────────────────────

    /**
     * Construye recursivamente el árbol de ancestros.
     *
     * Retorna: ['padre' => nodo|null, 'madre' => nodo|null]
     * donde nodo = ['animal' => [...], 'padre' => ..., 'madre' => ...]
     */
    private function construirAncestros(Animal $animal, int $generaciones): array
    {
        if ($generaciones === 0) {
            return ['padre' => null, 'madre' => null];
        }

        [$madre, $padre] = $this->resolverPadres($animal);

        return [
            'padre' => $padre
                ? array_merge(
                    $this->formatAnimal($padre),
                    $this->construirAncestros($padre, $generaciones - 1)
                )
                : null,

            'madre' => $madre
                ? array_merge(
                    $this->formatAnimal($madre),
                    $this->construirAncestros($madre, $generaciones - 1)
                )
                : null,
        ];
    }

    /**
     * Encuentra la madre y el padre biológico de un animal
     * navegando por: Cria → Parto → EventoReproductivo → hembra / servicio.macho
     *
     * @return array [Animal|null $madre, Animal|null $padre]
     */
    private function resolverPadres(Animal $animal): array
    {
        $cria = Cria::with([
            'parto.evento.hembra',
            'parto.evento.servicio.macho',
        ])
        ->where('animal_id', $animal->id)
        ->first();

        $madre = $cria?->parto?->evento?->hembra;
        $padre = $cria?->parto?->evento?->servicio?->macho;

        return [$madre, $padre];
    }

    // ─── Árbol de descendientes ───────────────────────────────────────────

    /**
     * Construye recursivamente la lista de descendientes.
     * Retorna un array plano de nodos, cada uno con su propia lista de hijos.
     */
    private function construirDescendientes(Animal $animal, int $generaciones): array
    {
        if ($generaciones === 0) {
            return [];
        }

        $hijos = $this->resolverHijos($animal);

        return array_map(function (Animal $hijo) use ($generaciones) {
            return array_merge(
                $this->formatAnimal($hijo),
                ['hijos' => $this->construirDescendientes($hijo, $generaciones - 1)]
            );
        }, $hijos);
    }

    /**
     * Devuelve los hijos directos de un animal, ya sea como madre o como padre.
     *
     * Como MADRE (hembra):
     *   EventoReproductivo (hembra_id, tipo: parto) → Parto → Crias → Animal
     *
     * Como PADRE (macho):
     *   ServicioReproductivo (macho_id) → Parto.servicio_evento_id → Crias → Animal
     *
     * @return Animal[]
     */
    private function resolverHijos(Animal $animal): array
    {
        $hijos = [];

        if ($animal->sexo === 'F') {
            // ── Como madre ────────────────────────────────────────────────
            $partos = EventoReproductivo::where('hembra_id', $animal->id)
                ->where('tipo_evento', 'parto')
                ->with(['parto.crias.animal'])
                ->get();

            foreach ($partos as $evento) {
                foreach ($evento->parto?->crias ?? [] as $cria) {
                    if ($cria->animal_id && $cria->animal) {
                        $hijos[] = $cria->animal;
                    }
                }
            }
        } else {
            // ── Como padre (toro / semental) ──────────────────────────────
            $servicios = ServicioReproductivo::where('macho_id', $animal->id)
                ->pluck('evento_id');

            if ($servicios->isNotEmpty()) {
                $partos = Parto::whereIn('servicio_evento_id', $servicios)
                    ->with(['crias.animal'])
                    ->get();

                foreach ($partos as $parto) {
                    foreach ($parto->crias as $cria) {
                        if ($cria->animal_id && $cria->animal) {
                            $hijos[] = $cria->animal;
                        }
                    }
                }
            }
        }

        // Eliminar duplicados por si un animal aparece más de una vez
        $vistos = [];
        return array_filter($hijos, function (Animal $a) use (&$vistos) {
            if (in_array($a->id, $vistos)) return false;
            $vistos[] = $a->id;
            return true;
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Serializa un Animal a array con solo los campos necesarios para el frontend.
     */
    private function formatAnimal(Animal $animal): array
    {
        return [
            'id'        => $animal->id,
            'arete'     => $animal->arete,
            'alias'     => $animal->alias,
            'sexo'      => $animal->sexo,
            'raza'      => $animal->raza,
            'especie'   => $animal->especie,
            'fecha_nac' => $animal->fecha_nac?->format('d/m/Y'),
            'estado'    => $animal->estado_productivo,
        ];
    }
}