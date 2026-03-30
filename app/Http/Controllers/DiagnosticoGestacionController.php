<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticoGestacion;
use App\Models\EventoReproductivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticoGestacionController extends Controller
{
    // POST /reproduccion/diagnosticos
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'hembra_id'                => 'required|exists:animals,id',
            'lote_id'                  => 'nullable|exists:lotes,id',
            'fecha'                    => 'required|date|before_or_equal:today',
            'servicio_evento_id'         => 'nullable|exists:evento_reproductivos,id',
            'metodo'                   => 'required|in:tacto_rectal,ultrasonido,laboratorio',
            'resultado'                => 'required|in:positivo,negativo,repetir',
            'dias_gestacion_estimados' => 'nullable|integer|min:1|max:283',
            'veterinario_id'           => 'nullable|exists:users,id',
            'veterinario_externo'      => 'nullable|string|max:100',
            'costo'                    => 'nullable|numeric|min:0',
            'observaciones'            => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Calcular fecha probable de parto (283 días desde el servicio)
            $fechaProbableParto = null;

            if ($datos['resultado'] === 'positivo') {
                if (!empty($datos['servicio_evento_id'])) {
                    $eventoServicio = EventoReproductivo::findOrFail($datos['servicio_evento_id']);
                    $fechaProbableParto = $eventoServicio->fecha->addDays(283)->format('Y-m-d');
                } else {
                    // Si no se vinculó servicio, buscar el último de esta hembra
                    $ultimoServicio = EventoReproductivo::where('hembra_id', $datos['hembra_id'])
                        ->where('tipo_evento', 'servicio')
                        ->latest('fecha')
                        ->first();

                    $fechaBase = $ultimoServicio ? $ultimoServicio->fecha : now();
                    $fechaProbableParto = $fechaBase->addDays(283)->format('Y-m-d');
                }
            }

            // Crear evento padre en reproductive_events
            $evento = EventoReproductivo::create([
                'hembra_id'     => $datos['hembra_id'],
                'lote_id'       => $datos['lote_id'] ?? null,
                'user_id' => null,
                'tipo_evento'   => 'diagnostico',
                'fecha'         => $datos['fecha'],
                'costo'         => $datos['costo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // Crear detalle en pregnancy_diagnoses
            DiagnosticoGestacion::create([
                'evento_id'                 => $evento->id,
                'servicio_evento_id'         => $datos['servicio_evento_id'] ?? null,
                'metodo'                   => $datos['metodo'],
                'resultado'                => $datos['resultado'],
                'dias_gestacion_estimados' => $datos['dias_gestacion_estimados'] ?? null,
                'fecha_probable_parto'     => $fechaProbableParto,
                'veterinario_id'           => $datos['veterinario_id'] ?? null,
                'veterinario_externo'      => $datos['veterinario_externo'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('reproduccion.index')
                ->with('success', 'Diagnóstico registrado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Error al registrar el diagnóstico: ' . $e->getMessage())
                ->withInput();
        }
    }
}