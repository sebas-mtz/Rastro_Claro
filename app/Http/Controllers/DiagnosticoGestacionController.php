<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DiagnosticoGestacion;
use App\Models\EventoReproductivo;
use App\Services\EstadoProductivoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DiagnosticoGestacionController extends Controller
{
    public function store(Request $request, EstadoProductivoService $estadoService): RedirectResponse
    {
        $datos = $request->validate([
            'hembra_id' => 'required|exists:animals,id',
            'lote_id' => 'nullable|exists:lotes,id',
            'fecha' => 'required|date|before_or_equal:today',
            'servicio_evento_id' => 'nullable|exists:evento_reproductivos,id',
            'metodo' => 'required|in:tacto_rectal,ultrasonido,laboratorio',
            'resultado' => 'required|in:positivo,negativo,repetir',
            'dias_gestacion_estimados' => 'nullable|integer|min:1|max:283',
            'veterinario_id' => 'nullable|exists:users,id',
            'veterinario_externo' => 'nullable|string|max:100',
            'costo' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $hembra = Animal::findOrFail($datos['hembra_id']);
        $fechaDiagnostico = Carbon::parse($datos['fecha']);
        $eventoServicio = null;
        $servicioEventoIdResuelto = null;
        $diasGestacionEstimados = $datos['dias_gestacion_estimados'] ?? null;
        $fechaProbableParto = null;

        /*
         * Primero intenta utilizar el servicio seleccionado.
         */
        if (!empty($datos['servicio_evento_id'])) {
            $eventoServicio = EventoReproductivo::whereKey($datos['servicio_evento_id'])
                ->where('hembra_id', $datos['hembra_id'])
                ->where('tipo_evento', 'servicio')
                ->first();

            if (!$eventoServicio) {
                return back()->withErrors([
                    'servicio_evento_id' => 'El evento seleccionado no es un servicio de esta hembra.',
                ])->withInput();
            }
        }

        /*
         * Si no seleccionaron uno, busca el último servicio anterior
         * o igual a la fecha del diagnóstico.
         */
        if (!$eventoServicio) {
            $eventoServicio = EventoReproductivo::where('hembra_id', $datos['hembra_id'])
                ->where('tipo_evento', 'servicio')
                ->whereDate('fecha', '<=', $datos['fecha'])
                ->latest('fecha')
                ->first();
        }

        if ($eventoServicio) {
            $servicioEventoIdResuelto = $eventoServicio->id;
        }

        if ($datos['resultado'] === 'positivo') {
            /*
             * Si hay servicio vinculado, el parto probable se calcula desde
             * la fecha del servicio.
             */
            if ($eventoServicio) {
                $fechaProbableParto = $eventoServicio->fecha
                    ->copy()
                    ->addDays(283)
                    ->format('Y-m-d');

                /*
                 * Si no escribieron los días de gestación, se calculan
                 * desde el servicio hasta el diagnóstico.
                 */
                if (empty($diasGestacionEstimados)) {
                    $diasGestacionEstimados = $eventoServicio->fecha
                        ->copy()
                        ->startOfDay()
                        ->diffInDays($fechaDiagnostico->copy()->startOfDay());
                }
            } else {
                /*
                 * Sin servicio registrado, necesitamos los días estimados
                 * para calcular cuánto falta.
                 */
                if (empty($diasGestacionEstimados)) {
                    return back()->withErrors([
                        'dias_gestacion_estimados' => 'Indica los días estimados de gestación cuando no existe un servicio vinculado.',
                    ])->withInput();
                }

                $diasRestantes = max(0, 283 - $diasGestacionEstimados);

                $fechaProbableParto = $fechaDiagnostico
                    ->copy()
                    ->addDays($diasRestantes)
                    ->format('Y-m-d');
            }
        } else {
            $diasGestacionEstimados = null;
            $fechaProbableParto = null;
        }

        try {
            DB::transaction(function () use (
                $datos,
                $hembra,
                $estadoService,
                $servicioEventoIdResuelto,
                $diasGestacionEstimados,
                $fechaProbableParto
            ) {
                $evento = EventoReproductivo::create([
                    'hembra_id' => $datos['hembra_id'],
                    'lote_id' => $datos['lote_id'] ?? $hembra->lote_id,
                    'user_id' =>null,
                    'tipo_evento' => 'diagnostico',
                    'fecha' => $datos['fecha'],
                    'costo' => $datos['costo'] ?? null,
                    'observaciones' => $datos['observaciones'] ?? null,
                ]);

                DiagnosticoGestacion::create([
                    'evento_id' => $evento->id,
                    'servicio_evento_id' => $servicioEventoIdResuelto,
                    'metodo' => $datos['metodo'],
                    'resultado' => $datos['resultado'],
                    'dias_gestacion_estimados' => $diasGestacionEstimados,
                    'fecha_probable_parto' => $fechaProbableParto,
                    'veterinario_id' => $datos['veterinario_id'] ?? null,
                    'veterinario_externo' => $datos['veterinario_externo'] ?? null,
                ]);

                $estadoService->transicionPorEvento(
                    $hembra,
                    'diagnostico',
                    $datos['resultado']
                );
            });

            return redirect()
                ->route('reproduccion.index')
                ->with('success', 'Diagnóstico registrado correctamente.');
        } catch (Throwable $e) {
            return back()
                ->with('error', 'Error al registrar el diagnóstico: ' . $e->getMessage())
                ->withInput();
        }
    }
}