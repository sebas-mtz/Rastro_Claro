<?php

namespace App\Services;

use App\Models\Reproduccion;
use App\Models\Animal;
use Illuminate\Http\Request;

class ReproduccionService
{
    public function getReproduccionesPaginated(Request $request)
    {
        $query = Reproduccion::with(['hembra.lote', 'macho.lote', 'lote', 'registradoPor'])
            ->latest('fecha');

        if ($request->tipo_evento) $query->where('tipo_evento', $request->tipo_evento);
        if ($request->estado) $query->where('estado', $request->estado);
        if ($request->fecha_desde) $query->where('fecha', '>=', $request->fecha_desde);
        if ($request->fecha_hasta) $query->where('fecha', '<=', $request->fecha_hasta);
        if ($request->hembra_id) $query->where('hembra_id', $request->hembra_id);

        return $query->paginate(10)->through(function ($r) {
            return [
                'id' => $r->id,
                'fecha' => $r->fecha?->format('Y-m-d'),
                'tipo_evento' => $r->tipo_evento,
                'estado' => $r->estado,
                'metodo' => $r->metodo,
                'diagnostico' => $r->diagnostico,
                'costo' => $r->costo,

                'hembra' => [
                    'id' => $r->hembra?->id,
                    'alias' => $r->hembra?->alias ?? $r->hembra?->arete ?? 'Sin alias',
                    'especie' => $r->hembra?->especie,
                    'lote' => $r->hembra?->lote?->nombre,
                ],

                'macho' => $r->macho ? [
                    'id' => $r->macho->id,
                    'alias' => $r->macho->alias ?? $r->macho->arete ?? 'Sin alias',
                    'especie' => $r->macho->especie,
                    'lote' => $r->macho->lote?->nombre,
                ] : null,

                'lote_nombre' => $r->lote?->nombre ?? $r->hembra?->lote?->nombre ?? null,

                'parto' => [
                    'numero_crias' => $r->numero_crias,
                    'peso_total_crias' => $r->peso_total_crias,
                    'complicaciones' => (bool) $r->complicaciones,
                ],

                'registrado_por' => $r->registradoPor?->name ?? null,
                'observaciones' => $r->observaciones,
            ];
        });
    }

    public function getAnimalesDisponiblesParaReproduccion()
    {
        $noDisponibles = ['sacrificado', 'faenado', 'vendido'];

        return Animal::with('lote')
            ->whereNotIn('estado_productivo', $noDisponibles)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'alias' => $a->alias ?? $a->arete ?? 'Sin alias',
                    'arete' => $a->arete,
                    'especie' => $a->especie,
                    'sexo' => $a->sexo ?? null,
                    'lote_nombre' => $a->lote?->nombre ?? 'Sin lote',
                ];
            });
    }
}
