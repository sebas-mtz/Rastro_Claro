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

class PartoController extends Controller
{
    public function store(Request $request, EstadoProductivoService $estadoService): RedirectResponse
    {
        $datos = $request->validate([
            'hembra_id'              => 'required|exists:animals,id',
            'lote_id'                => 'nullable|exists:lotes,id',
            'fecha'                  => 'required|date|before_or_equal:today',
            'servicio_evento_id'     => 'nullable|exists:evento_reproductivos,id',
            'padre_id'               => 'nullable|exists:animals,id', // ← nuevo
            'tipo_parto'             => 'required|in:normal,distocico,cesarea',
            'asistencia_requerida'   => 'boolean',
            'complicaciones'         => 'boolean',
            'detalle_complicaciones' => 'nullable|string',
            'costo'                  => 'nullable|numeric|min:0',
            'observaciones'          => 'nullable|string',
            'crias'                  => 'required|array|min:1',
            'crias.*.sexo'           => 'required|in:macho,hembra',
            'crias.*.peso_nacimiento'=> 'nullable|numeric|min:0|max:100',
            'crias.*.condicion'      => 'required|in:vivo,nacido_muerto,murio_al_nacer',
            'crias.*.arete'          => 'nullable|string|max:100',
            'crias.*.arete_temporal' => 'nullable|string|max:50',
            'crias.*.observaciones'  => 'nullable|string',
        ]);

        $madre = Animal::findOrFail($datos['hembra_id']);

        // ── Resolver el padre ─────────────────────────────────────────────
        // Prioridad 1: macho del servicio vinculado
        // Prioridad 2: padre asignado manualmente en el formulario
        $padreId = null;
        if (!empty($datos['servicio_evento_id'])) {
            $eventoServicio = EventoReproductivo::with('servicio')
                ->find($datos['servicio_evento_id']);
            $padreId = $eventoServicio?->servicio?->macho_id;
        } elseif (!empty($datos['padre_id'])) {
            $padreId = $datos['padre_id'];
        }

        try {
            DB::beginTransaction();

            // 1 — Evento padre
            $evento = EventoReproductivo::create([
                'hembra_id'     => $datos['hembra_id'],
                'lote_id'       => $datos['lote_id'] ?? $madre->lote_id,
                'user_id'       => null,
                'tipo_evento'   => 'parto',
                'fecha'         => $datos['fecha'],
                'costo'         => $datos['costo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // 2 — Detalle del parto
            $parto = Parto::create([
                'evento_id'              => $evento->id,
                'servicio_evento_id'     => $datos['servicio_evento_id'] ?? null,
                'tipo_parto'             => $datos['tipo_parto'],
                'asistencia_requerida'   => $datos['asistencia_requerida'] ?? false,
                'complicaciones'         => $datos['complicaciones'] ?? false,
                'detalle_complicaciones' => $datos['detalle_complicaciones'] ?? null,
                'numero_crias'           => count($datos['crias']),
            ]);

            // 3 — Registrar cada cría
            foreach ($datos['crias'] as $criaDatos) {
                $animalId = null;

                if ($criaDatos['condicion'] === 'vivo') {
                    $nuevoAnimal = Animal::create([
                        'especie'           => $madre->especie,
                        'raza'              => $madre->raza,
                        'arete'             => $criaDatos['arete'] ?? null,
                        'sexo'              => $criaDatos['sexo'] === 'macho' ? 'M' : 'F',
                        'fecha_nac'         => $datos['fecha'],
                        'peso'              => $criaDatos['peso_nacimiento'] ?? null,
                        'estado_productivo' => EstadoProductivoService::estadoInicial(),
                        'lote_id'           => $madre->lote_id,
                        'madre_id'          => $madre->id,
                        'padre_id'          => $padreId, // ← viene del servicio o del formulario
                    ]);
                    $animalId = $nuevoAnimal->id;
                }

                Cria::create([
                    'parto_id'        => $parto->id,
                    'animal_id'       => $animalId,
                    'sexo'            => $criaDatos['sexo'],
                    'peso_nacimiento' => $criaDatos['peso_nacimiento'] ?? null,
                    'condicion'       => $criaDatos['condicion'],
                    'arete_temporal'  => $criaDatos['arete_temporal'] ?? null,
                    'observaciones'   => $criaDatos['observaciones'] ?? null,
                ]);
            }

            // 4 — La madre pasa a lactancia automáticamente
            $estadoService->transicionPorEvento($madre, 'parto');

            DB::commit();

            return redirect()->route('reproduccion.index')
                ->with('success', 'Parto registrado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar el parto: ' . $e->getMessage());
        }
    }
}