<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Tratamiento;

class EstadoActualAnimalService
{
    public function obtener(Animal $animal): array
    {
        $terminales = [
            'muerto' => ['Fallecido', 'El animal está dado de baja por fallecimiento.'],
            'vendido' => ['Vendido', 'El animal fue vendido y ya no está disponible.'],
            'faeneado' => ['Faenado', 'El animal fue dado de baja por faena.'],
            'sacrificado' => ['Sacrificado', 'El animal fue dado de baja por sacrificio.'],
        ];

        if (isset($terminales[$animal->estado_productivo])) {
            [$titulo, $detalle] = $terminales[$animal->estado_productivo];

            return [[
                'tipo' => 'terminal',
                'titulo' => $titulo,
                'detalle' => $detalle,
                'fecha' => $animal->muerte?->fecha?->format('Y-m-d'),
            ]];
        }

        $notas = [];
        $tratamiento = Tratamiento::where('animal_id', $animal->id)
            ->where('estado', Tratamiento::ESTADO_ACTIVO)
            ->whereDate('fecha_inicio', '<=', today())
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', today());
            })
            ->latest('fecha_inicio')
            ->first();

        if ($tratamiento) {
            $notas[] = [
                'tipo' => 'tratamiento',
                'titulo' => 'En tratamiento',
                'detalle' => $tratamiento->nombre
                    . ($tratamiento->fecha_fin
                        ? " · previsto hasta {$tratamiento->fecha_fin->format('Y-m-d')}"
                        : ''),
                'fecha' => $tratamiento->fecha_inicio->format('Y-m-d'),
            ];
        }

        if ($animal->esHembra()) {
            $ultimoParto = EventoReproductivo::where('hembra_id', $animal->id)
                ->where('tipo_evento', 'parto')
                ->with(['parto.destete.evento'])
                ->latest('fecha')
                ->first();

            if ($ultimoParto?->parto?->destete?->evento) {
                $fechaDestete = $ultimoParto->parto->destete->evento->fecha;
                if ($fechaDestete->lte(today()) && $fechaDestete->diffInDays(today()) <= 30) {
                    $notas[] = [
                        'tipo' => 'destete',
                        'titulo' => 'En descanso después del destete',
                        'detalle' => 'La madre terminó recientemente el periodo de lactancia.',
                        'fecha' => $fechaDestete->format('Y-m-d'),
                    ];
                }
            } elseif ($ultimoParto && $ultimoParto->fecha->diffInDays(today()) <= 60) {
                $esLactancia = $animal->estado_productivo === 'lactancia'
                    || $ultimoParto->parto?->salio_leche;
                $notas[] = [
                    'tipo' => 'reproduccion',
                    'titulo' => $esLactancia ? 'En lactancia' : 'Parto reciente',
                    'detalle' => $esLactancia
                        ? 'Se encuentra en periodo de lactancia después de su parto.'
                        : 'Tuvo un parto recientemente.',
                    'fecha' => $ultimoParto->fecha->format('Y-m-d'),
                ];
            } elseif ($animal->estado_productivo === 'lactancia') {
                $notas[] = [
                    'tipo' => 'reproduccion',
                    'titulo' => 'En lactancia',
                    'detalle' => 'El estado productivo actual indica lactancia.',
                    'fecha' => null,
                ];
            }
        }

        $vacunacion = EventoSalud::where('animal_id', $animal->id)
            ->where('tipo', EventoSalud::TIPO_VACUNACION)
            ->where('estado', EventoSalud::ESTADO_APLICADA)
            ->whereDate('fecha_aplicacion', '>=', today()->subDays(30))
            ->with('vacuna:id,nombre')
            ->latest('fecha_aplicacion')
            ->first();

        if ($vacunacion) {
            $notas[] = [
                'tipo' => 'vacunacion',
                'titulo' => 'Vacunado recientemente',
                'detalle' => $vacunacion->vacuna?->nombre
                    ?? $vacunacion->diagnostico
                    ?? 'Vacunación aplicada',
                'fecha' => $vacunacion->fecha_aplicacion?->format('Y-m-d'),
            ];
        }

        if (!$notas) {
            $notas[] = [
                'tipo' => 'productivo',
                'titulo' => 'Estado productivo actual',
                'detalle' => $animal->estado_productivo ?: 'Sin estado productivo definido.',
                'fecha' => null,
            ];
        }

        return $notas;
    }
}
