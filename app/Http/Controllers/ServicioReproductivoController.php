<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\EventoReproductivo;
use App\Models\Pajilla;
use App\Models\ServicioReproductivo;
use App\Services\EstadoProductivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioReproductivoController extends Controller
{
    // POST /reproduccion/servicios
    public function store(
        Request $request,
        EstadoProductivoService $estadoService
    ): RedirectResponse {
        $datos = $request->validate([
            'hembra_id'       => 'required|exists:animals,id',
            'lote_id'         => 'nullable|exists:lotes,id',
            'fecha'           => 'required|date|before_or_equal:today',
            'tipo_servicio'   => 'required|in:monta_natural,inseminacion_artificial,iatf',
            'macho_id'        => 'nullable|exists:animals,id',
            'pajilla_id'      => 'nullable|exists:pajillas,id',
            'tecnico_id'      => 'nullable|exists:users,id',
            'tecnico_externo' => 'nullable|string|max:100',
            'numero_servicio' => 'nullable|integer|min:1|max:10',
            'costo'           => 'nullable|numeric|min:0',
            'observaciones'   => 'nullable|string',
        ]);

        // Validar que el animal seleccionado sea hembra
        $hembra = Animal::findOrFail($datos['hembra_id']);

        if (!in_array(strtolower($hembra->sexo), ['hembra', 'f', 'female'])) {
            return redirect()->back()
                ->withErrors([
                    'hembra_id' => 'El animal seleccionado no es hembra',
                ])
                ->withInput();
        }

        // Monta natural requiere un semental
        if (
            $datos['tipo_servicio'] === 'monta_natural'
            && empty($datos['macho_id'])
        ) {
            return redirect()->back()
                ->withErrors([
                    'macho_id' => 'La monta natural requiere seleccionar un semental',
                ])
                ->withInput();
        }

        // IA o IATF requieren una pajilla
        if (
            in_array(
                $datos['tipo_servicio'],
                ['inseminacion_artificial', 'iatf']
            )
            && empty($datos['pajilla_id'])
        ) {
            return redirect()->back()
                ->withErrors([
                    'pajilla_id' => 'La inseminación artificial requiere seleccionar una pajilla',
                ])
                ->withInput();
        }

        try {
            DB::transaction(function () use (
                $datos,
                $hembra,
                $estadoService
            ): void {
                $pajilla = null;

                /*
                 * Se bloquea la pajilla durante la transacción para evitar
                 * que dos servicios utilicen la misma pajilla al mismo tiempo.
                 */
                if (
                    in_array(
                        $datos['tipo_servicio'],
                        ['inseminacion_artificial', 'iatf']
                    )
                ) {
                    $pajilla = Pajilla::query()
                        ->lockForUpdate()
                        ->findOrFail($datos['pajilla_id']);

                    if ($pajilla->estado === 'utilizada') {
                        throw ValidationException::withMessages([
                            'pajilla_id' => 'La pajilla seleccionada ya fue utilizada.',
                        ]);
                    }
                }

                $evento = EventoReproductivo::create([
                    'hembra_id'     => $datos['hembra_id'],
                    'lote_id'       => $datos['lote_id'] ?? null,
                    'user_id'       => null,
                    'tipo_evento'   => 'servicio',
                    'fecha'         => $datos['fecha'],
                    'costo'         => $datos['costo'] ?? null,
                    'observaciones' => $datos['observaciones'] ?? null,
                ]);

                ServicioReproductivo::create([
                    'evento_id'       => $evento->id,
                    'macho_id'        => $datos['macho_id'] ?? null,
                    'tipo_servicio'   => $datos['tipo_servicio'],
                    'pajilla_id'      => $datos['pajilla_id'] ?? null,
                    'tecnico_id'      => $datos['tecnico_id'] ?? null,
                    'tecnico_externo' => $datos['tecnico_externo'] ?? null,
                    'numero_servicio' => $datos['numero_servicio'] ?? 1,
                ]);

                /*
                 * Cambiar la pajilla a utilizada únicamente después
                 * de registrar correctamente el servicio.
                 */
                if ($pajilla !== null) {
                    $pajilla->estado = 'utilizada';
                    $pajilla->save();
                }

                $estadoService->transicionPorEvento($hembra, 'servicio');
            });

            return redirect()->route('reproduccion.index')
                ->with('success', 'Servicio registrado correctamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Error al registrar el servicio: ' . $e->getMessage())
                ->withInput();
        }
    }
}