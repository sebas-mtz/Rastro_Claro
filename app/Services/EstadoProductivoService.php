<?php

namespace App\Services;

use App\Models\Animal;

class EstadoProductivoService
{
    // ── Catálogo manual por especie (etapa de vida / rol productivo) ──────
    //
    // El sistema actualmente solo maneja Ovinos. Se deja como arreglo con
    // una sola clave (en vez de simplificar a una lista plana) para no
    // romper el resto del código —AnimalController, LoteController y el
    // frontend— que espera "especiesPorEspecie" como estructura clave/valor.
    // Si en el futuro se reactiva otra especie, basta con agregar su clave
    // aquí; es la única fuente de verdad de la que dependen las validaciones
    // de especie en StoreAnimalRequest/UpdateAnimalRequest.
    public static function estadosManualesPorEspecie(): array
    {
        return [
            'Ovino' => ['Cordero', 'Vientre', 'Semental', 'N/A'],
        ];
    }

    public static function estadosPorEspecie(): array
    {
        return self::estadosManualesPorEspecie();
    }

    public static function estadoInicial(string $especie): string
    {
        return match ($especie) {
            'Ovino' => 'Cordero',
            default => 'N/A',
        };
    }

    // ── Estados automáticos de reproducción — NO aparecen en el select ────
    public static function estadosAutomaticos(): array
    {
        return ['empadre', 'gestante', 'lactancia', 'vacia'];
    }

    // ── Especies que tienen módulo reproductivo activo ────────────────────
    public static function especiesConReproduccion(): array
    {
        return ['Ovino'];
    }

    // ── Transición automática por evento reproductivo ─────────────────────
    /**
     * Actualiza el estado_productivo del animal según el evento ocurrido.
     *
     * servicio             → empadre
     * diagnóstico positivo → gestante
     * diagnóstico negativo → vacia
     * diagnóstico repetir  → vacia
     * parto                → lactancia
     *
     * No toca especies fuera de especiesConReproduccion().
     */
    public function transicionPorEvento(
        Animal $animal,
        string $tipoEvento,
        ?string $resultadoDiagnostico = null
    ): void {
        if (!in_array($animal->especie, self::especiesConReproduccion())) {
            return;
        }

        $nuevoEstado = match(true) {
            $tipoEvento === 'servicio'                                            => 'empadre',
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'positivo'  => 'gestante',
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'negativo'  => 'vacia',
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'repetir'   => 'vacia',
            $tipoEvento === 'parto'                                                => 'lactancia',
            default                                                                => null,
        };

        if ($nuevoEstado && $animal->estado_productivo !== $nuevoEstado) {
            $animal->update(['estado_productivo' => $nuevoEstado]);
        }
    }

    // ── Estados de sistema (no reproductivos) ──────────────────────────────
    //
    // NOTA: 'muerto' va en minúscula a propósito — así se compara en
    // Animal::booted() y en varios checks de AnimalController
    // ($animal->estado_productivo === 'muerto'). Si el frontend necesita
    // esta lista (por ejemplo para agrupar tarjetas), debe recibirla como
    // prop desde el controlador en vez de hardcodearla, precisamente para
    // evitar que se desincronice el casing como ya pasó una vez.
    public static function estadosSistema(): array
    {
        return ['Faeneado', 'Vendido', 'Sacrificado', 'muerto'];
    }

    // ── Todos los valores que el sistema puede escribir ───────────────────
    public static function todosLosValores(): array
    {
        $manuales = array_unique(array_merge(...array_values(self::estadosManualesPorEspecie())));

        return array_unique(array_merge(
            $manuales,
            self::estadosAutomaticos(),
            self::estadosSistema()
        ));
    }
}