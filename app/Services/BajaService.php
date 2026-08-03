<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Baja;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Salidas del rebaño.
 *
 * Dar de baja un ejemplar lo saca del conteo de activos pero NO borra nada:
 * su historial de pesajes, sanidad, costos, valuación y genealogía se conserva
 * íntegro para poder consultarlo después.
 */
class BajaService
{
    /**
     * Registra la salida y desactiva el ejemplar, todo en una transacción.
     */
    public function registrar(Animal $animal, array $datos): Baja
    {
        return DB::transaction(function () use ($animal, $datos) {
            $baja = Baja::create([
                'animal_id' => $animal->id,
                'fecha' => $datos['fecha'],
                'tipo_salida' => $datos['tipo_salida'],
                'causa' => $datos['causa'] ?? null,
                'diagnostico' => $datos['diagnostico'] ?? null,
                'responsable_id' => $datos['responsable_id'] ?? null,
                'precio_salida' => $datos['precio_salida'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'documento' => $datos['documento'] ?? null,
                'registrado_por' => Auth::id(),
            ]);

            $animal->update([
                'activo' => false,
                'fecha_baja' => $datos['fecha'],
            ]);

            return $baja;
        });
    }

    /**
     * Revierte una baja registrada por error y devuelve el ejemplar al rebaño.
     */
    public function revertir(Baja $baja): void
    {
        DB::transaction(function () use ($baja) {
            $animal = $baja->animal;

            $baja->delete();

            // Solo se reactiva si ya no queda ninguna otra baja vigente.
            if ($animal && ! $animal->bajas()->exists()) {
                $animal->update(['activo' => true, 'fecha_baja' => null]);
            }
        });
    }

    /**
     * Indicadores de salida del rebaño para un periodo.
     *
     * Mortalidad = (fallecimientos / promedio del inventario) × 100.
     * Devuelve null cuando no hay inventario, en vez de dividir entre cero.
     */
    public function indicadores(?string $desde = null, ?string $hasta = null): array
    {
        $bajas = Baja::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->get();

        $activos = Animal::activo()->count();
        $totalHistorico = $activos + $bajas->count();

        $fallecimientos = $bajas->where('tipo_salida', Baja::FALLECIMIENTO)->count();

        return [
            'activos' => $activos,
            'bajas_periodo' => $bajas->count(),
            'por_tipo' => $bajas->groupBy('tipo_salida')->map->count(),
            'fallecimientos' => $fallecimientos,
            'porcentaje_mortalidad' => $totalHistorico > 0
                ? round(($fallecimientos / $totalHistorico) * 100, 2)
                : null,
            'ingresos_por_salidas' => round((float) $bajas->sum('precio_salida'), 2),
        ];
    }
}
