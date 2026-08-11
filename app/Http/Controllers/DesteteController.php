<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\Destete;
use App\Models\DesteteCria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Services\CriaDisponibilidadService;
use App\Services\EstadoProductivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DesteteController extends Controller
{
    public function store(
        Request $request,
        CriaDisponibilidadService $disponibilidadService,
    ): RedirectResponse
    {
        $estados = array_values(array_unique(array_merge(
            ...array_values(EstadoProductivoService::estadosManualesPorEspecie()),
        )));

        $datos = $request->validate([
            'parto_id' => ['required', 'exists:partos,id'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'estado_madre' => ['required', Rule::in(['bueno', 'regular', 'malo'])],
            'estado_productivo_madre' => ['required', Rule::in($estados)],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'crias' => ['required', 'array', 'min:1'],
            'crias.*.cria_id' => ['required', 'distinct', 'exists:crias,id'],
            'crias.*.peso_destete' => ['nullable', 'numeric', 'min:0.01', 'max:9999'],
            'crias.*.estado_destino' => ['required', Rule::in($estados)],
            'crias.*.lote_id' => ['nullable', 'exists:lotes,id'],
        ]);

        $parto = Parto::with([
            'evento.hembra',
            'crias.animal.muerte',
            'crias.animal.ventas',
            'destete',
        ])
            ->findOrFail($datos['parto_id']);

        if ($parto->destete) {
            throw ValidationException::withMessages([
                'parto_id' => 'Este parto ya tiene un destete registrado.',
            ]);
        }

        if ($parto->evento->fecha->gt($datos['fecha'])) {
            throw ValidationException::withMessages([
                'fecha' => 'La fecha del destete no puede ser anterior a la fecha del parto.',
            ]);
        }

        $estadosPorEspecie = EstadoProductivoService::estadosManualesPorEspecie();
        $estadosMadre = $estadosPorEspecie[$parto->evento->hembra?->especie] ?? [];

        if (!in_array($datos['estado_productivo_madre'], $estadosMadre, true)) {
            throw ValidationException::withMessages([
                'estado_productivo_madre' => 'Selecciona un estado productivo válido para la especie de la madre.',
            ]);
        }

        $criasDelParto = $parto->crias->keyBy('id');
        $criasActivas = $parto->crias
            ->filter(fn (Cria $cria) =>
                $disponibilidadService->clasificar($cria)['disponible_destete']
            )
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
        $criasRecibidas = collect($datos['crias'])
            ->pluck('cria_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if (!$criasActivas) {
            throw ValidationException::withMessages([
                'crias' => 'No se puede registrar el destete porque ninguna cría continúa disponible.',
            ]);
        }

        if ($criasActivas !== $criasRecibidas) {
            throw ValidationException::withMessages([
                'crias' => 'El destete debe incluir únicamente todas las crías que continúan disponibles.',
            ]);
        }

        foreach ($datos['crias'] as $detalle) {
            $cria = $criasDelParto->get((int) $detalle['cria_id']);
            if (!$cria || !$cria->animal_id || $cria->condicion !== 'vivo') {
                throw ValidationException::withMessages([
                    'crias' => 'Una de las crías no pertenece al parto o no tiene un animal asociado.',
                ]);
            }

            if (!$disponibilidadService->clasificar($cria)['disponible_destete']) {
                throw ValidationException::withMessages([
                    'crias' => 'Una de las crías ya no está disponible para destete.',
                ]);
            }

            if ($detalle['peso_destete'] === null) {
                throw ValidationException::withMessages([
                    'crias' => 'Captura el peso de cada cría disponible.',
                ]);
            }

            $estadosCria = $estadosPorEspecie[$cria->animal?->especie] ?? [];
            if (!in_array($detalle['estado_destino'], $estadosCria, true)) {
                throw ValidationException::withMessages([
                    'crias' => 'Selecciona un estado productivo válido para cada cría.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $parto, $datos, $criasDelParto) {
            $evento = EventoReproductivo::create([
                'hembra_id' => $parto->evento->hembra_id,
                'lote_id' => $parto->evento->hembra?->lote_id,
                // La ruta está protegida por auth; se guarda quién registró el evento.
                'user_id' => $request->user()->id,
                'tipo_evento' => 'destete',
                'fecha' => $datos['fecha'],
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $destete = Destete::create([
                'evento_id' => $evento->id,
                'parto_id' => $parto->id,
                'estado_madre' => $datos['estado_madre'],
                'estado_productivo_madre' => $datos['estado_productivo_madre'],
                'tipo_nacimiento' => $this->tipoNacimiento(count($datos['crias'])),
            ]);

            $parto->evento->hembra->update([
                'estado_productivo' => $datos['estado_productivo_madre'],
            ]);

            foreach ($datos['crias'] as $detalle) {
                $cria = $criasDelParto->get((int) $detalle['cria_id']);
                $animal = $cria->animal;

                DesteteCria::create([
                    'destete_id' => $destete->id,
                    'cria_id' => $cria->id,
                    'peso_destete' => $detalle['peso_destete'] ?? null,
                    'estado_destino' => $detalle['estado_destino'],
                ]);

                if ($detalle['peso_destete'] !== null) {
                    Pesaje::updateOrCreate(
                        ['animal_id' => $animal->id, 'fecha' => $datos['fecha']],
                        [
                            'peso' => $detalle['peso_destete'],
                            'notas' => 'Pesaje registrado durante el destete.',
                        ],
                    );
                    $this->sincronizarPesoActual($animal);
                }

                $actualizaciones = [
                    'estado_productivo' => $detalle['estado_destino'],
                ];

                if (!empty($detalle['lote_id'])) {
                    $actualizaciones['lote_id'] = $detalle['lote_id'];
                }

                $animal->update($actualizaciones);
            }
        });

        return back()->with('success', 'Destete registrado correctamente.');
    }

    private function tipoNacimiento(int $numeroCrias): string
    {
        return match ($numeroCrias) {
            1 => 'simple',
            2 => 'gemelar',
            3 => 'triple',
            4 => 'cuadruple',
            default => "{$numeroCrias}_crias",
        };
    }

    private function sincronizarPesoActual(Animal $animal): void
    {
        $ultimoPeso = Pesaje::where('animal_id', $animal->id)
            ->orderByDesc('fecha')
            ->value('peso');

        $animal->update(['peso' => $ultimoPeso]);
    }

}
