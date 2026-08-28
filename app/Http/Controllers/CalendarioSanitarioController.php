<?php

namespace App\Http\Controllers;

use App\Models\EventoSalud;
use App\Services\IndicadoresOvinosService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Calendario sanitario del rebaño.
 *
 * Separa las actividades en vencidas, del día, próximas y completadas, para
 * que el trabajo pendiente se vea de un vistazo.
 */
class CalendarioSanitarioController extends Controller
{
    public function __construct(protected IndicadoresOvinosService $indicadores)
    {
    }

    public function index(Request $request)
    {
        // Marca como vencidas las que ya pasaron de fecha antes de listar.
        EventoSalud::sincronizarVencidos();

        $hoy = now()->toDateString();
        $horizonte = now()->addDays((int) ($request->dias ?? 30))->toDateString();

        $base = fn () => EventoSalud::query()
            ->with(['animal:id,arete,alias', 'lote:id,nombre', 'vacuna:id,nombre'])
            ->when($request->tipo, fn ($q) => $q->where('tipo', $request->tipo))
            ->orderBy('fecha_programada');

        $vencidas = (clone $base())
            ->where('estado', '!=', EventoSalud::ESTADO_APLICADA)
            ->whereDate('fecha_programada', '<', $hoy)
            ->get();

        $delDia = (clone $base())
            ->where('estado', '!=', EventoSalud::ESTADO_APLICADA)
            ->whereDate('fecha_programada', $hoy)
            ->get();

        $proximas = (clone $base())
            ->where('estado', '!=', EventoSalud::ESTADO_APLICADA)
            ->whereBetween('fecha_programada', [now()->addDay()->toDateString(), $horizonte])
            ->get();

        $completadas = (clone $base())
            ->where('estado', EventoSalud::ESTADO_APLICADA)
            ->whereDate('fecha_aplicacion', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('fecha_aplicacion')
            ->limit(50)
            ->get();

        return Inertia::render('Salud/Calendario', [
            'vencidas' => $vencidas,
            'delDia' => $delDia,
            'proximas' => $proximas,
            'completadas' => $completadas,
            // Hitos reproductivos que también ocupan al equipo
            'partosProximos' => $this->indicadores->partosProximos(21),
            'filtros' => $request->only(['tipo', 'dias']),
            'tipos' => [
                EventoSalud::TIPO_VACUNACION => 'Vacunación',
                EventoSalud::TIPO_CONSULTA => 'Consulta',
                EventoSalud::TIPO_REVISION => 'Revisión',
                EventoSalud::TIPO_EMERGENCIA => 'Emergencia',
            ],
        ]);
    }
}
