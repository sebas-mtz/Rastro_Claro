<?php

namespace App\Http\Controllers;

use App\Models\Racion;
use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RacionController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($data) {
            $nutrition    = $this->calcularNutricionPromedio($data['insumos']);
            $costos       = $this->calcularCostos($data['insumos']);
            $sinValores   = (bool) ($data['sin_valores_nutricion'] ?? false);

            $racion = Racion::create([
                'nombre'      => $data['nombre'],
                'MS'          => $this->resolverNutricional($data['MS'] ?? null, $nutrition['MS'], $sinValores),
                'PB'          => $this->resolverNutricional($data['PB'] ?? null, $nutrition['PB'], $sinValores),
                'EM'          => $this->resolverNutricional($data['EM'] ?? null, $nutrition['EM'], $sinValores),
                'FDN'         => $this->resolverNutricional($data['FDN'] ?? null, $nutrition['FDN'], $sinValores),
                'minerales'   => $data['minerales'] ?: null,
                'precio_kg'   => $costos['precio_kg'],
                'costo_total' => $costos['costo_total'],
            ]);

            $this->syncInsumos($racion, $data['insumos']);
        });

        return back()->with('success', 'Ración creada correctamente.');
    }

    public function update(Request $request, Racion $racion)
    {
        $data          = $this->validateData($request);
        $tieneConsumos = $racion->tieneConsumos();

        DB::transaction(function () use ($data, $racion) {
            $nutrition  = $this->calcularNutricionPromedio($data['insumos']);
            $costos     = $this->calcularCostos($data['insumos']);
            $sinValores = (bool) ($data['sin_valores_nutricion'] ?? false);

            $racion->update([
                'nombre'      => $data['nombre'],
                'MS'          => $this->resolverNutricional($data['MS'] ?? null, $nutrition['MS'], $sinValores),
                'PB'          => $this->resolverNutricional($data['PB'] ?? null, $nutrition['PB'], $sinValores),
                'EM'          => $this->resolverNutricional($data['EM'] ?? null, $nutrition['EM'], $sinValores),
                'FDN'         => $this->resolverNutricional($data['FDN'] ?? null, $nutrition['FDN'], $sinValores),
                'minerales'   => $data['minerales'] ?: null,
                'precio_kg'   => $costos['precio_kg'],
                'costo_total' => $costos['costo_total'],
            ]);

            $this->syncInsumos($racion, $data['insumos']);
        });

        $mensaje = $tieneConsumos
            ? 'Ración actualizada. Los cambios aplican a consumos futuros; el historial anterior se conserva intacto.'
            : 'Ración actualizada correctamente.';

        return back()->with('success', $mensaje);
    }

    public function destroy(Racion $racion)
{
    if ($racion->tieneConsumos()) {
        $programacionesPausadas = DB::table('programacion_alimentacions')
            ->where('racion_id', $racion->id)
            ->where('activa', true)
            ->count();

        $racion->update(['activo' => false, 'archivado_at' => now()]);

        DB::table('programacion_alimentacions')
            ->where('racion_id', $racion->id)
            ->where('activa', true)
            ->update(['activa' => false, 'updated_at' => now()]);

        $mensaje = "Ración \"{$racion->nombre}\" archivada. Su historial se conserva intacto.";
        if ($programacionesPausadas > 0) {
            $mensaje .= " Se pausaron {$programacionesPausadas} programación(es) de alimentación.";
        }

        return back()->with('success', $mensaje);
    }

    if ($racion->programaciones()->where('activa', true)->exists()) {
        return back()->withErrors([
            'racion' => "No se puede eliminar \"{$racion->nombre}\" porque tiene programaciones activas.",
        ]);
    }

    $racion->insumos()->detach();
    $racion->delete();
    return back()->with('success', 'Ración eliminada correctamente.');
}
    public function reactivar(Racion $racion)
    {
        $racion->update(['activo' => true, 'archivado_at' => null]);
        return back()->with('success', "Ración \"{$racion->nombre}\" reactivada.");
    }

    public function verificarDisponibilidad(Request $request)
    {
        $data = $request->validate([
            'insumos'            => ['required', 'array', 'min:1'],
            'insumos.*.id'       => ['required', 'exists:inventario_insumos,id'],
            'insumos.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        ]);

        $faltantes = [];

        foreach ($data['insumos'] as $row) {
            $item = InventarioInsumo::find($row['id']);
            if (!$item) continue;

            $requerido  = (float) $row['cantidad'];
            $disponible = (float) ($item->existencias ?? 0);

            if ($disponible < $requerido) {
                $faltantes[] = [
                    'insumo'     => $item->nombre,
                    'requerido'  => $requerido,
                    'disponible' => $disponible,
                    'faltante'   => round($requerido - $disponible, 2),
                    'unidad'     => $item->unidad ?? 'kg',
                ];
            }
        }

        return response()->json(['disponible' => empty($faltantes), 'faltantes' => $faltantes]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Lógica de resolución de valores nutrimentales:
     *
     * | sin_valores_nutricion | manual enviado | calculado | resultado   |
     * |-----------------------|----------------|-----------|-------------|
     * | true                  | cualquiera     | cualquiera| null        |
     * | false                 | número         | cualquiera| manual      |
     * | false                 | null / ''      | número    | calculado   |
     * | false                 | null / ''      | null      | null        |
     */
    private function resolverNutricional(mixed $manual, mixed $calculado, bool $sinValores): mixed
    {
        if ($sinValores) return null;
        if ($manual !== null && $manual !== '') return $manual;
        return $calculado;
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nombre'                => ['required', 'string', 'max:255'],
            'MS'                    => ['nullable', 'numeric', 'min:0'],
            'PB'                    => ['nullable', 'numeric', 'min:0'],
            'EM'                    => ['nullable', 'numeric', 'min:0'],
            'FDN'                   => ['nullable', 'numeric', 'min:0'],
            'minerales'             => ['nullable', 'string'],
            'costo_total'           => ['nullable', 'numeric', 'min:0'],
            'sin_valores_nutricion' => ['nullable', 'boolean'],
            'insumos'               => ['required', 'array', 'min:1'],
            'insumos.*.id'          => ['required', 'exists:inventario_insumos,id'],
            'insumos.*.cantidad'    => ['required', 'numeric', 'min:0.01'],
        ]);
    }

    private function syncInsumos(Racion $racion, array $insumos): void
    {
        $sync = [];
        foreach ($insumos as $i) {
            $sync[$i['id']] = ['cantidad' => $i['cantidad']];
        }
        $racion->insumos()->sync($sync);
    }

    private function calcularCostos(array $insumos): array
    {
        $totalCosto = $totalCantidad = 0;

        foreach ($insumos as $row) {
            $item = InventarioInsumo::find($row['id']);
            if (!$item) continue;
            $cantidad       = (float) $row['cantidad'];
            $totalCosto    += $cantidad * (float) ($item->costo_promedio ?? 0);
            $totalCantidad += $cantidad;
        }

        return [
            'costo_total' => round($totalCosto, 2),
            'precio_kg'   => $totalCantidad > 0 ? round($totalCosto / $totalCantidad, 2) : null,
        ];
    }

    private function calcularNutricionPromedio(array $insumos): array
    {
        $campos           = ['MS', 'PB', 'EM', 'FDN'];
        $totales          = array_fill_keys($campos, 0);
        $cantidadConDatos = array_fill_keys($campos, 0);

        foreach ($insumos as $row) {
            $item = InventarioInsumo::find($row['id']);
            if (!$item) continue;
            $cantidad = (float) $row['cantidad'];
            foreach ($campos as $campo) {
                if ($item->{$campo} !== null) {
                    $totales[$campo]          += $cantidad * (float) $item->{$campo};
                    $cantidadConDatos[$campo] += $cantidad;
                }
            }
        }

        return array_combine($campos, array_map(
            fn ($c) => $cantidadConDatos[$c] > 0
                ? round($totales[$c] / $cantidadConDatos[$c], 2)
                : null,
            $campos
        ));
    }
}