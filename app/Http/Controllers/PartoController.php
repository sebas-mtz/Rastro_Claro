<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Services\EstadoProductivoService;
use App\Services\PaternidadProbableService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PartoController extends Controller
{
    /**
     * GET — devuelve los servicios candidatos (posibles orígenes) de un
     * parto, dados hembra_id y fecha. El frontend debe llamar a este
     * endpoint antes de mostrar el paso de "confirmar padre" del
     * formulario, para que la persona usuaria elija cuál fue el servicio
     * real cuando hay más de un candidato plausible (ej. dos montas casi
     * el mismo día).
     */
    public function serviciosCandidatos(
        Request $request,
        PaternidadProbableService $paternidadService
    ): JsonResponse {
        $datos = $request->validate([
            'hembra_id' => 'required|exists:animals,id',
            'fecha' => 'required|date|before_or_equal:today',
        ]);

        $hembra = Animal::findOrFail($datos['hembra_id']);
        $fechaParto = Carbon::parse($datos['fecha']);

        // Mismo origen que usa DiagnosticoGestacionController: días de
        // gestación configurables por usuario, con 150 como valor por
        // defecto si no se ha configurado nada.
        $diasGestacionPromedio = (int) data_get(
            $request->user()->settings,
            'gestation_days',
            150,
        );

        $candidatos = $paternidadService->candidatosParaParto(
            $hembra,
            $fechaParto,
            $diasGestacionPromedio
        );

        return response()->json(['candidatos' => $candidatos]);
    }

    public function store(
        Request $request,
        EstadoProductivoService $estadoService,
        PaternidadProbableService $paternidadService
    ): RedirectResponse {
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
            'salio_leche' => 'boolean',
            'observaciones_leche' => 'nullable|string|max:1000',
            'facilidad_materna' => 'boolean',
            'observaciones_maternas' => 'nullable|string|max:1000',
            'costo' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'crias' => 'required|array|min:1',
            'crias.*.sexo' => 'required|in:macho,hembra',
            'crias.*.peso_nacimiento' => 'nullable|numeric|min:0|max:100',
            'crias.*.condicion' => 'required|in:vivo,nacido_muerto,murio_al_nacer',
            'crias.*.vigor' => 'nullable|in:B,R,M',
            'crias.*.arete' => 'nullable|string|max:100',
            'crias.*.arete_temporal' => 'nullable|string|max:50',
            'crias.*.observaciones' => 'nullable|string',
            'crias.*.lote_id' => 'nullable|exists:lotes,id',
        ]);

        if (!empty($datos['padre_id']) && !empty($datos['padre_externo_id'])) {
            return back()->withErrors([
                'padre_id' => 'Selecciona un padre interno o un donador externo, no ambos.',
            ])->withInput();
        }

        $madre = Animal::findOrFail($datos['hembra_id']);
        $fechaParto = Carbon::parse($datos['fecha']);

        // Antes esto eran dos checks separados (esHembra + edad mínima).
        // puedeRegistrarParto() los consolida y además valida que la
        // hembra respete el intervalo mínimo desde su último parto —
        // validación que antes no existía en este controlador.
        [$aptaParaParto, $mensajeParto] = $madre->puedeRegistrarParto($fechaParto);
        if (!$aptaParaParto) {
            return back()->withErrors(['hembra_id' => $mensajeParto])->withInput();
        }

        $padreId = null;
        $padreExternoId = null;
        $servicioEventoIdResuelto = null;

        // ── Resolución del padre desde el servicio confirmado ───────────
        // Ya NO se busca automáticamente "el servicio más reciente" ni se
        // acepta un evento de diagnóstico como origen: el frontend debe
        // pedir los candidatos a serviciosCandidatos() (arriba) y mandar
        // aquí el servicio_evento_id que la persona usuaria confirmó como
        // el real, porque puede haber más de un servicio plausible dentro
        // de la ventana de gestación (ej. dos montas casi el mismo día) y
        // el sistema no puede decidir eso por sí solo.
        if (!empty($datos['servicio_evento_id'])) {
            $eventoServicio = EventoReproductivo::with('servicio.pajilla')
                ->where('id', $datos['servicio_evento_id'])
                ->where('hembra_id', $datos['hembra_id'])
                ->where('tipo_evento', 'servicio')
                ->first();

            if (!$eventoServicio || !$eventoServicio->servicio) {
                return back()->withErrors([
                    'servicio_evento_id' => 'El servicio seleccionado no es válido para esta hembra.',
                ])->withInput();
            }

            $servicioEventoIdResuelto = $eventoServicio->id;
            [$padreId, $padreExternoId] = $paternidadService->resolverPadre($eventoServicio->servicio);
        }

        /*
         * Si no se confirmó un servicio (por ejemplo, porque no hubo
         * ningún candidato dentro de la ventana de gestación esperada),
         * se permite asignar el padre manualmente.
         */
        if (empty($padreId) && empty($padreExternoId)) {
            $padreId = $datos['padre_id'] ?? null;
            $padreExternoId = $datos['padre_externo_id'] ?? null;
        }

        /*
         * Verificar el padre interno (ya sea resuelto desde el servicio o
         * asignado manualmente).
         */
        if (!empty($padreId)) {
            $padre = Animal::findOrFail($padreId);
            $sexoPadre = strtolower((string) $padre->sexo);

            if (!in_array($sexoPadre, ['m', 'male', 'macho'])) {
                return back()->withErrors([
                    'padre_id' => 'El animal seleccionado como padre no es macho.',
                ])->withInput();
            }

            if (!$padre->esAptoParaReproduccion($fechaParto)) {
                return back()->withErrors([
                    'padre_id' => "El semental '{$padre->alias}' no cumple con la edad mínima requerida para reproducción."
                ])->withInput();
            }

            if ($padre->especie !== $madre->especie) {
                return back()->withErrors([
                    'padre_id' => 'El padre debe pertenecer a la misma especie que la madre.',
                ])->withInput();
            }
        }

        foreach ($datos['crias'] as $indice => $criaDatos) {
            if ($criaDatos['condicion'] === 'vivo' && empty($criaDatos['vigor'])) {
                return back()->withErrors([
                    "crias.{$indice}.vigor" => 'Selecciona el vigor de cada cría viva.',
                ])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $esPartoNormal = $datos['tipo_parto'] === 'normal';
            $salioLeche = $request->boolean('salio_leche');
            $facilidadMaterna = $request->boolean('facilidad_materna');

            $evento = EventoReproductivo::create([
                'hembra_id' => $datos['hembra_id'],
                'lote_id' => $datos['lote_id'] ?? $madre->lote_id,
                'user_id' => $request->user()->id,
                'tipo_evento' => 'parto',
                'fecha' => $datos['fecha'],
                'costo' => $datos['costo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $parto = Parto::create([
                'evento_id' => $evento->id,
                'servicio_evento_id' => $servicioEventoIdResuelto,
                'tipo_parto' => $datos['tipo_parto'],
                'asistencia_requerida' => ! $esPartoNormal && $request->boolean('asistencia_requerida'),
                'complicaciones' => ! $esPartoNormal && $request->boolean('complicaciones'),
                'detalle_complicaciones' => ! $esPartoNormal && $request->boolean('complicaciones')
                    ? ($datos['detalle_complicaciones'] ?? null)
                    : null,
                'numero_crias' => count($datos['crias']),
                'salio_leche' => $salioLeche,
                'observaciones_leche' => $salioLeche ? null : ($datos['observaciones_leche'] ?? null),
                'facilidad_materna' => $facilidadMaterna,
                'observaciones_maternas' => $facilidadMaterna
                    ? null
                    : ($datos['observaciones_maternas'] ?? null),
            ]);

            foreach ($datos['crias'] as $criaDatos) {
                $animalId = null;

                if ($criaDatos['condicion'] === 'vivo') {
                    $nuevoAnimal = Animal::create([
                        'especie' => $madre->especie,
                        'alias' => null,
                        'raza' => $madre->raza,
                        'arete' => $criaDatos['arete'] ?? null,
                        'sexo' => $criaDatos['sexo'] === 'macho' ? 'M' : 'H',
                        'fecha_nac' => $datos['fecha'],
                        'peso' => $criaDatos['peso_nacimiento'] ?? null,
                        'BCS' => null,
                        'estado_productivo' => EstadoProductivoService::estadoInicial($madre->especie),
                        'lote_id' => $criaDatos['lote_id'] ?? $datos['lote_id'] ?? $madre->lote_id,
                        'madre_id' => $madre->id,
                        'padre_id' => $padreId,
                        'padre_externo_id' => $padreExternoId,
                    ]);

                    $animalId = $nuevoAnimal->id;

                    if (!is_null($criaDatos['peso_nacimiento'])) {
                        Pesaje::create([
                            'animal_id' => $nuevoAnimal->id,
                            'fecha' => $datos['fecha'],
                            'peso' => $criaDatos['peso_nacimiento'],
                            'notas' => 'Peso al nacimiento',
                        ]);
                    }
                }

                Cria::create([
                    'parto_id' => $parto->id,
                    'animal_id' => $animalId,
                    'sexo' => $criaDatos['sexo'],
                    'peso_nacimiento' => $criaDatos['peso_nacimiento'] ?? null,
                    'condicion' => $criaDatos['condicion'],
                    'vigor' => $criaDatos['condicion'] === 'vivo' ? $criaDatos['vigor'] : null,
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