<?php

namespace App\Services;

use App\Models\Animal;

class EstadoProductivoService
{
    

// Lo que el sistema asigna automáticamente — NO aparece en el select
public static function estadosAutomaticos(): array
{
    return ['empadre', 'gestante', 'lactancia', 'vacia'];
}
public static function estadosManualesPorEspecie(): array
{
    return [
        'Bovino'  => ['Vaca seca', 'En crecimiento', 'Reproductor', 'Reemplazo', 'Mantenimiento', 'Desecho'],
        'Caprino' => ['En crecimiento', 'Reproductor', 'reemplazo', 'mantenimiento', 'desecho'],
        'Ovino'   => ['En crecimiento', 'Reproductor', 'reemplazo', 'mantenimiento', 'desecho'],
        'Porcino' => ['En crecimiento', 'Reproductor', 'reemplazo', 'mantenimiento', 'desecho'],
        'Equino'  => ['En entrenamiento', 'Reproductor', 'En descanso', 'reemplazo', 'desecho'],
        'Aves de corral (gallinas y pollitos)' => ['Postura', 'En descanso', 'En crecimiento', 'desecho'],
        'Gallos'  => ['En crecimiento', 'Reproductor', 'De pelea / exhibición', 'En entrenamiento', 'En descanso', 'desecho'],
    ];
}
// El catálogo completo — para filtros, reportes, o validaciones internas
public static function estadosPorEspecie(): array
{
    $automaticos = self::estadosAutomaticos();

    return array_map(
        fn($estados) => array_merge($estados, $automaticos),
        self::estadosManualesPorEspecie()
    );
}
    // ── Especies que tienen módulo reproductivo activo ────────────────────

    public static function especiesConReproduccion(): array
    {
        return ['Bovino', 'Caprino', 'Ovino', 'Porcino'];
    }

    // ── Transición automática por evento reproductivo ─────────────────────

    /**
     * Actualiza el estado_productivo del animal según el evento ocurrido.
     *
     * servicio            → empadre
     * diagnóstico positivo → gestante
     * diagnóstico negativo → vacia
     * diagnóstico repetir  → vacia
     * parto               → lactancia
     *
     * No toca equinos, gallos ni aves.
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
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'positivo' => 'gestante',
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'negativo' => 'vacia',
            $tipoEvento === 'diagnostico' && $resultadoDiagnostico === 'repetir'  => 'vacia',
            $tipoEvento === 'parto'                                               => 'lactancia',
            default                                                               => null,
        };

        if ($nuevoEstado && $animal->estado_productivo !== $nuevoEstado) {
            $animal->update(['estado_productivo' => $nuevoEstado]);
        }
    }

    // ── Estado inicial para crías nacidas vivas ───────────────────────────

    /**
     * Las crías siempre nacen en 'reemplazo' independientemente de la especie.
     * El ganadero las mueve manualmente cuando crecen.
     */
    public static function estadoInicial(): string
    {
        return 'reemplazo';
    }

    // ── Todos los valores que el sistema puede escribir ───────────────────

    /**
     * Útil si en algún momento quieres agregar validación in: en el Request.
     * Incluye los estados manuales de todas las especies + los que escribe
     * código interno (reproducción, faenas, ventas).
     */
    public static function todosLosValores(): array
    {
        $manuales = array_unique(array_merge(...array_values(self::estadosPorEspecie())));

        $sistema = ['empadre', 'gestante', 'lactancia', 'vacia', 'reemplazo',
                    'faeneado', 'vendido', 'sacrificado'];

        return array_unique(array_merge($manuales, $sistema));
    }
}