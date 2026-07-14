<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Services\EstadoProductivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PartoController extends Controller
{
    public function store(Request $request, EstadoProductivoService $estadoService): RedirectResponse
    {
        $datos = $request->validate([
            'hembra_id' => 'required|exists:animals,id',
            'lote_id' => 'nullable|exists:lotes,id',
            'fecha' => 'required|date|before_or_equal:today',
            'servicio_evento_id' => 'nullable|exists:evento_reproductivos,id',
            'padre_id' => 'nullable|exists:animals,id',
            'padre_externo_id' => 'nullable|exists:donadores_externos,id',
            'tipo_parto' => 'required|in:normal,distocico,cesarea',
            'asistencia_requerida' => 'boolean',
            'complicaciones' => 'boolean',
            'detalle_complicaciones' => 'nullable|string',
            'costo' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'crias' => 'required|array|min:1',
            'crias.*.sexo' => 'required|in:macho,hembra',
            'crias.*.peso_nacimiento' => 'nullable|numeric|min:0|max:100',
            'crias.*.condicion' => 'required|in:vivo,nacido_muerto,murio_al_nacer',
            'crias.*.arete' => 'nullable|string|max:100',
            'crias.*.arete_temporal' => 'nullable|string|max:50',
            'crias.*.observaciones' => 'nullable|string',
        ]);

        if (!empty($datos['padre_id']) && !empty($datos['padre_externo_id'])) {
            return back()->withErrors([
                'padre_id' => 'Selecciona un padre interno o un donador externo, no ambos.',
            ])->withInput();
        }

        $madre = Animal::findOrFail($datos['hembra_id']);

        if (!in_array(strtolower((string) $madre->sexo), ['f', 'female', 'hembra'])) {
            return back()->withErrors([
                'hembra_id' => 'El animal seleccionado no es una hembra.',
            ])->withInput();
        }

        $padreId = null;
        $padreExternoId = null;
        $servicioEventoIdResuelto = null;

        /*
         * Si el formulario manda una gestación, servicio_evento_id puede contener:
         * - El ID del evento de servicio.
         * - El ID del evento de diagnóstico.
         *
         * Si es diagnóstico, se obtiene desde él el servicio original.
         */
        if (!empty($datos['servicio_evento_id'])) {
            $eventoRelacionado = EventoReproductivo::with([
                'servicio.pajilla',
                'diagnostico',
            ])->findOrFail($datos['servicio_evento_id']);

            if ($eventoRelacionado->tipo_evento === 'servicio') {
                $eventoServicio = $eventoRelacionado;
            } elseif ($eventoRelacionado->tipo_evento === 'diagnostico' && $eventoRelacionado->diagnostico?->servicio_evento_id) {
                $eventoServicio = EventoReproductivo::with('servicio.pajilla')
                    ->findOrFail($eventoRelacionado->diagnostico->servicio_evento_id);
            } else {
                return back()->withErrors([
                    'servicio_evento_id' => 'No se pudo localizar el servicio reproductivo que originó la gestación.',
                ])->withInput();
            }

            $servicioEventoIdResuelto = $eventoServicio->id;
            $servicio = $eventoServicio->servicio;

            if (!$servicio) {
                return back()->withErrors([
                    'servicio_evento_id' => 'El evento seleccionado no tiene un servicio reproductivo asociado.',
                ])->withInput();
            }

            if ($servicio->tipo_servicio === 'monta_natural') {
                if (empty($servicio->macho_id)) {
                    return back()->withErrors([
                        'servicio_evento_id' => 'La monta natural seleccionada no tiene un semental asociado.',
                    ])->withInput();
                }

                $padreId = $servicio->macho_id;
            }

            if (in_array($servicio->tipo_servicio, ['inseminacion_artificial', 'iatf'])) {
                $pajilla = $servicio->pajilla;

                if (!$pajilla) {
                    return back()->withErrors([
                        'servicio_evento_id' => 'El servicio de inseminación no tiene una pajilla asociada.',
                    ])->withInput();
                }

                if (!empty($pajilla->animal_id)) {
                    $padreId = $pajilla->animal_id;
                } elseif (!empty($pajilla->donador_externo_id)) {
                    $padreExternoId = $pajilla->donador_externo_id;
                } else {
                    return back()->withErrors([
                        'servicio_evento_id' => 'La pajilla no tiene un donador interno o externo asociado.',
                    ])->withInput();
                }
            }
        }

        /*
         * Si no se pudo obtener un padre desde un servicio,
         * utiliza el seleccionado manualmente.
         */
        if (empty($padreId) && empty($padreExternoId)) {
            $padreId = $datos['padre_id'] ?? null;
            $padreExternoId = $datos['padre_externo_id'] ?? null;
        }

        /*
         * Verificar el padre interno seleccionado manualmente.
         */
        if (!empty($padreId)) {
            $padre = Animal::findOrFail($padreId);
            $sexoPadre = strtolower((string) $padre->sexo);

            if (!in_array($sexoPadre, ['m', 'male', 'macho'])) {
                return back()->withErrors([
                    'padre_id' => 'El animal seleccionado como padre no es macho.',
                ])->withInput();
            }

            if ($padre->especie !== $madre->especie) {
                return back()->withErrors([
                    'padre_id' => 'El padre debe pertenecer a la misma especie que la madre.',
                ])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $evento = EventoReproductivo::create([
                'hembra_id' => $datos['hembra_id'],
                'lote_id' => $datos['lote_id'] ?? $madre->lote_id,
                'user_id' => null,
                'tipo_evento' => 'parto',
                'fecha' => $datos['fecha'],
                'costo' => $datos['costo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $parto = Parto::create([
                'evento_id' => $evento->id,
                'servicio_evento_id' => $servicioEventoIdResuelto,
                'tipo_parto' => $datos['tipo_parto'],
                'asistencia_requerida' => $datos['asistencia_requerida'] ?? false,
                'complicaciones' => $datos['complicaciones'] ?? false,
                'detalle_complicaciones' => $datos['detalle_complicaciones'] ?? null,
                'numero_crias' => count($datos['crias']),
            ]);

            foreach ($datos['crias'] as $criaDatos) {
                $animalId = null;

                if ($criaDatos['condicion'] === 'vivo') {
                    $nuevoAnimal = Animal::create([
                        'especie' => $madre->especie,
                        'alias' => null,
                        'raza' => $madre->raza,
                        'arete' => $criaDatos['arete'] ?? null,
                        'sexo' => $criaDatos['sexo'] === 'macho' ? 'M' : 'F',
                        'fecha_nac' => $datos['fecha'],
                        'peso' => $criaDatos['peso_nacimiento'] ?? null,
                        'BCS' => null,
                        'estado_productivo' => EstadoProductivoService::estadoInicial(),
                        'lote_id' => $datos['lote_id'] ?? $madre->lote_id,
                        'madre_id' => $madre->id,
                        'padre_id' => $padreId,
                        'padre_externo_id' => $padreExternoId,
                    ]);

                    $animalId = $nuevoAnimal->id;
                }

                Cria::create([
                    'parto_id' => $parto->id,
                    'animal_id' => $animalId,
                    'sexo' => $criaDatos['sexo'],
                    'peso_nacimiento' => $criaDatos['peso_nacimiento'] ?? null,
                    'condicion' => $criaDatos['condicion'],
                    'arete_temporal' => $criaDatos['arete_temporal'] ?? null,
                    'observaciones' => $criaDatos['observaciones'] ?? null,
                ]);
            }

            $estadoService->transicionPorEvento($madre, 'parto');

            DB::commit();

            return redirect()->route('reproduccion.index')
                ->with('success', 'Parto registrado correctamente.');
        } catch (Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Error al registrar el parto: ' . $e->getMessage())
                ->withInput();
        }
    }
}