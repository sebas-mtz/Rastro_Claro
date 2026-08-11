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
            'hembra_ids'      => 'required|array|min:1',
            'hembra_ids.*'    => 'integer|exists:animals,id',
            'fecha'           => 'required|date|before_or_equal:today',
            'tipo_servicio'   => 'required|in:monta_natural,monta_controlada,inseminacion_artificial,iatf,transferencia_embriones,fiv',
            'macho_id'        => 'nullable|exists:animals,id',
            'pajilla_ids'     => 'nullable|array',
            'pajilla_ids.*'   => 'integer|exists:pajillas,id',
            'tecnico_id'      => 'nullable|exists:users,id',
            'tecnico_externo' => 'nullable|string|max:100',
            'numero_servicio' => 'nullable|integer|min:1|max:10',
            'costo'           => 'nullable|numeric|min:0',
            'observaciones'   => 'nullable|string',
        ]);

        $esMonta     = in_array($datos['tipo_servicio'], ['monta_natural', 'monta_controlada']);
        $usaPajillas = in_array($datos['tipo_servicio'], ['inseminacion_artificial', 'iatf', 'transferencia_embriones', 'fiv']);

        $hembraIds = array_values(array_unique($datos['hembra_ids']));
        $hembras   = Animal::whereIn('id', $hembraIds)->get()->keyBy('id');

        // Validar que todas las seleccionadas sean hembra
        foreach ($hembraIds as $hembraId) {
            if (!$hembras->get($hembraId)?->esHembra()) {
                return redirect()->back()
                    ->withErrors(['hembra_ids' => 'Uno de los animales seleccionados no es hembra.'])
                    ->withInput();
            }
        }

        $macho = null;

        if ($esMonta) {
            if (empty($datos['macho_id'])) {
                return redirect()->back()
                    ->withErrors(['macho_id' => 'La monta requiere seleccionar un semental.'])
                    ->withInput();
            }

            $macho = Animal::findOrFail($datos['macho_id']);

            if (strtoupper((string) $macho->sexo) !== 'M') {
                return redirect()->back()
                    ->withErrors(['macho_id' => 'El animal seleccionado como semental no es macho.'])
                    ->withInput();
            }

            foreach ($hembras as $hembra) {
                if ($hembra->especie !== $macho->especie) {
                    return redirect()->back()
                        ->withErrors(['macho_id' => 'El semental debe pertenecer a la misma especie que las hembras seleccionadas.'])
                        ->withInput();
                }
            }
        }

        $pajillaIds = [];

        if ($usaPajillas) {
            $pajillaIds = array_values($datos['pajilla_ids'] ?? []);

            if (count($pajillaIds) !== count($hembraIds)) {
                return redirect()->back()
                    ->withErrors(['pajilla_ids' => 'Debe haber una pajilla asignada por cada hembra seleccionada.'])
                    ->withInput();
            }

            if (count(array_unique($pajillaIds)) !== count($pajillaIds)) {
                return redirect()->back()
                    ->withErrors(['pajilla_ids' => 'No se puede asignar la misma pajilla a más de una hembra.'])
                    ->withInput();
            }
        }

        try {
            DB::transaction(function () use (
                $request, $datos, $hembras, $hembraIds, $pajillaIds, $usaPajillas, $macho, $estadoService
            ): void {
                $pajillasBloqueadas = collect();

                if ($usaPajillas) {
                    /*
                     * Se bloquean todas las pajillas involucradas durante la
                     * transacción para evitar que dos servicios usen la misma
                     * pajilla al mismo tiempo.
                     */
                    $pajillasBloqueadas = Pajilla::query()
                        ->whereIn('id', $pajillaIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($pajillaIds as $pajillaId) {
                        $pajilla = $pajillasBloqueadas->get($pajillaId);

                        if (!$pajilla || $pajilla->estado === 'utilizada') {
                            throw ValidationException::withMessages([
                                'pajilla_ids' => 'Una de las pajillas seleccionadas ya no está disponible.',
                            ]);
                        }
                    }
                }

                foreach ($hembraIds as $index => $hembraId) {
                    $hembra    = $hembras->get($hembraId);
                    $pajillaId = $usaPajillas ? $pajillaIds[$index] : null;
                    $pajilla   = $pajillaId ? $pajillasBloqueadas->get($pajillaId) : null;

                    $evento = EventoReproductivo::create([
                        'hembra_id'     => $hembra->id,
                        'lote_id'       => $hembra->lote_id,
                        'user_id'       => $request->user()->id,
                        'tipo_evento'   => 'servicio',
                        'fecha'         => $datos['fecha'],
                        'costo'         => $datos['costo'] ?? null,
                        'observaciones' => $datos['observaciones'] ?? null,
                    ]);

                    ServicioReproductivo::create([
                        'evento_id'       => $evento->id,
                        'macho_id'        => $macho?->id,
                        'tipo_servicio'   => $datos['tipo_servicio'],
                        'pajilla_id'      => $pajilla?->id,
                        'tecnico_id'      => $datos['tecnico_id'] ?? null,
                        'tecnico_externo' => $datos['tecnico_externo'] ?? null,
                        'numero_servicio' => $datos['numero_servicio'] ?? 1,
                    ]);

                    if ($pajilla) {
                        $pajilla->estado = 'utilizada';
                        $pajilla->save();
                    }

                    $estadoService->transicionPorEvento($hembra, 'servicio');
                }
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