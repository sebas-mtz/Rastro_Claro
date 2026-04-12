<?php

namespace App\Http\Controllers;

use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioInsumoController extends Controller
{
    public function store(Request $request)
    {
        $data    = $this->validateData($request);
        $payload = $this->buildPayload($data);
        InventarioInsumo::create($payload);

        return back()->with('success', 'Insumo agregado correctamente.');
    }

    /**
     * Solo actualiza campos descriptivos (nombre, tipo, marca, unidad, nutrición, auto_rellenar).
     * Nunca toca existencias ni costo_promedio — eso es exclusivo de reabastecer().
     * Los snapshots en alimentaciones pasadas no se alteran: los cambios aplican
     * solo a consumos futuros.
     */
    public function update(Request $request, InventarioInsumo $item)
    {
        $data = $request->validate([
            'nombre'             => ['required', 'string', 'max:255'],
            'tipo'               => ['required', 'string', 'max:100'],
            'marca'              => ['nullable', 'string', 'max:100'],
            'unidad'             => ['nullable', 'string', 'max:50'],
            'MS'                 => ['nullable', 'numeric', 'min:0'],
            'PB'                 => ['nullable', 'numeric', 'min:0'],
            'EM'                 => ['nullable', 'numeric', 'min:0'],
            'FDN'                => ['nullable', 'numeric', 'min:0'],
            'auto_rellenar'      => ['nullable', 'boolean'],
            'cantidad_rellenado' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $data['auto_rellenar'] = (bool) ($data['auto_rellenar'] ?? false);

        if (!$data['auto_rellenar']) {
            $data['cantidad_rellenado'] = null;
        }

        $item->update($data);

        $fueUsado = DB::table('racion_insumo')
            ->where('inventario_insumo_id', $item->id)
            ->exists();

        $mensaje = $fueUsado
            ? 'Insumo actualizado. Los cambios aplican a consumos futuros; el historial anterior se conserva intacto.'
            : 'Insumo actualizado correctamente.';

        return back()->with('success', $mensaje);
    }

    public function reabastecer(Request $request, InventarioInsumo $item)
    {
        $data = $request->validate([
            'cantidad'    => ['required', 'numeric', 'min:0.01'],
            'costo_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cantidadNueva    = (float) $data['cantidad'];
        $costoNuevo       = (float) ($data['costo_total'] ?? 0);
        $existenciaActual = (float) $item->existencias;
        $costoKgActual    = (float) ($item->costo_promedio ?? 0);
        $nuevaExistencia  = $existenciaActual + $cantidadNueva;

        $nuevoCostoPromedio = $nuevaExistencia > 0
            ? round(($existenciaActual * $costoKgActual + $costoNuevo) / $nuevaExistencia, 2)
            : 0;

        $item->update([
            'existencias'    => $nuevaExistencia,
            'costo_promedio' => $nuevoCostoPromedio,
        ]);

        // Si antes no había stock y ahora sí, reactivar programaciones pausadas
        // que dependan de este insumo (solo si todos sus insumos tienen stock).
        if ($existenciaActual <= 0 && $nuevaExistencia > 0) {
            $this->reactivarProgramacionesSiPosible($item->id);
        }

        return back()->with('success', 'Inventario actualizado correctamente.');
    }

    /**
     * Reglas de eliminación/desactivación:
     * - Nunca fue usado en ninguna ración → eliminar físicamente.
     * - Fue usado en alguna ración archivada → desactivar (nunca borrar).
     * - Está en una ración ACTIVA → bloquear, pedir archivar la ración primero.
     */
    public function destroy(InventarioInsumo $item)
    {
        $enRacionActiva = DB::table('racion_insumo')
            ->join('racions', 'racions.id', '=', 'racion_insumo.racion_id')
            ->where('racion_insumo.inventario_insumo_id', $item->id)
            ->where('racions.activo', true)
            ->exists();

        if ($enRacionActiva) {
            return back()->withErrors([
                'insumo' => "No se puede desactivar \"{$item->nombre}\" porque está en raciones activas. Archiva primero esas raciones.",
            ]);
        }

        $fueUsado = DB::table('racion_insumo')
            ->where('inventario_insumo_id', $item->id)
            ->exists();

        if ($fueUsado) {
            $item->update([
                'activo'         => false,
                'desactivado_at' => now(),
            ]);

            return back()->with('success', "Insumo \"{$item->nombre}\" desactivado. Su historial se conserva intacto.");
        }

        $item->delete();

        return back()->with('success', 'Insumo eliminado correctamente.');
    }

    /**
     * PUT /alimentacion/inventario/{item}/reactivar
     */
    public function reactivar(InventarioInsumo $item)
    {
        $item->update([
            'activo'         => true,
            'desactivado_at' => null,
        ]);

        return back()->with('success', "Insumo \"{$item->nombre}\" reactivado.");
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function reactivarProgramacionesSiPosible(int $insumoId): void
    {
        $racionIds = DB::table('racion_insumo')
            ->where('inventario_insumo_id', $insumoId)
            ->pluck('racion_id');

        foreach ($racionIds as $racionId) {
            $hayOtroSinStock = DB::table('racion_insumo')
                ->join('inventario_insumos', 'inventario_insumos.id', '=', 'racion_insumo.inventario_insumo_id')
                ->where('racion_insumo.racion_id', $racionId)
                ->where('inventario_insumos.existencias', '<=', 0)
                ->exists();

            if (!$hayOtroSinStock) {
                DB::table('programacion_alimentacions')
                    ->where('racion_id', $racionId)
                    ->where('activa', false)
                    ->update(['activa' => true, 'updated_at' => now()]);
            }
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nombre'             => ['required', 'string', 'max:255'],
            'tipo'               => ['required', 'string', 'max:100'],
            'marca'              => ['nullable', 'string', 'max:100'],
            'existencias'        => ['required', 'numeric', 'min:0.01'],
            'unidad'             => ['nullable', 'string', 'max:50'],
            'costo_total'        => ['nullable', 'numeric', 'min:0'],
            'MS'                 => ['nullable', 'numeric', 'min:0'],
            'PB'                 => ['nullable', 'numeric', 'min:0'],
            'EM'                 => ['nullable', 'numeric', 'min:0'],
            'FDN'                => ['nullable', 'numeric', 'min:0'],
            'auto_rellenar'      => ['nullable', 'boolean'],
            'cantidad_rellenado' => ['nullable', 'numeric', 'min:0.01'],
        ]);
    }

    private function buildPayload(array $data): array
    {
        $payload = $data;

        $payload['costo_promedio'] = (
            isset($data['costo_total']) &&
            $data['costo_total'] !== '' &&
            (float) $data['existencias'] > 0
        )
            ? round((float) $data['costo_total'] / (float) $data['existencias'], 2)
            : null;

        unset($payload['costo_total']);

        $payload['auto_rellenar'] = (bool) ($data['auto_rellenar'] ?? false);

        if (!$payload['auto_rellenar']) {
            $payload['cantidad_rellenado'] = null;
        }

        return $payload;
    }
}