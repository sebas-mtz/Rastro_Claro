<?php

namespace App\Services;

use App\Models\Cria;

class CriaDisponibilidadService
{
    public function clasificar(Cria $cria): array
    {
        if ($cria->condicion === 'nacido_muerto') {
            return $this->resultado(
                disponible: false,
                situacion: 'nacido_muerto',
                causa: 'Nació muerta',
                observacion: $cria->observaciones,
            );
        }

        if ($cria->condicion === 'murio_al_nacer') {
            return $this->resultado(
                disponible: false,
                situacion: 'murio_al_nacer',
                causa: 'Murió al nacer',
                observacion: $cria->observaciones,
            );
        }

        $animal = $cria->animal;

        if (!$animal) {
            return $this->resultado(
                disponible: false,
                situacion: 'sin_animal',
                causa: 'No tiene un animal asociado',
                observacion: $cria->observaciones,
            );
        }

        if ($animal->muerte || $animal->estado_productivo === 'muerto') {
            return $this->resultado(
                disponible: false,
                situacion: 'muerto',
                fecha: $animal->muerte?->fecha?->format('Y-m-d'),
                causa: $animal->muerte?->causa ?? 'Muerte registrada',
                observacion: $animal->muerte?->observaciones ?: $cria->observaciones,
            );
        }

        $venta = $animal->ventas
            ->where('estado_venta', 'completada')
            ->sortByDesc('fecha_venta')
            ->first();

        if (!$venta && $animal->estado_productivo === 'vendido') {
            $venta = $animal->ventas
                ->where('estado_venta', '!=', 'cancelada')
                ->sortByDesc('fecha_venta')
                ->first();
        }

        if ($venta || $animal->estado_productivo === 'vendido') {
            return $this->resultado(
                disponible: false,
                situacion: 'vendido',
                fecha: $venta?->fecha_venta?->format('Y-m-d'),
                causa: 'Animal vendido',
                observacion: $venta?->observaciones ?: $cria->observaciones,
            );
        }

        if (in_array($animal->estado_productivo, ['faeneado', 'sacrificado'], true)) {
            return $this->resultado(
                disponible: false,
                situacion: $animal->estado_productivo,
                causa: ucfirst($animal->estado_productivo),
                observacion: $cria->observaciones,
            );
        }

        return $this->resultado(
            disponible: true,
            situacion: 'disponible',
            observacion: $cria->observaciones,
        );
    }

    private function resultado(
        bool $disponible,
        string $situacion,
        ?string $fecha = null,
        ?string $causa = null,
        ?string $observacion = null,
    ): array {
        return [
            'disponible_destete' => $disponible,
            'situacion' => $situacion,
            'fecha_baja' => $fecha,
            'causa_baja' => $causa,
            'observacion_baja' => $observacion,
        ];
    }
}
