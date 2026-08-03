<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Animal;
use App\Models\Produccion;
use App\Models\InventarioInsumo;
use App\Models\EventoSalud;
use App\Services\AlertaOperativaService;
use App\Services\CostoService;
use App\Services\IndicadoresOvinosService;
use Carbon\Carbon;

class CustomController extends Controller
{
    public function __construct(
        protected CostoService $costoService,
        protected IndicadoresOvinosService $indicadores,
        protected AlertaOperativaService $alertas,
    ) {
    }

    public function home()
    {
        $hoy = Carbon::today();

        // 🐄 1) RESUMEN SUPERIOR (summary)
        $animalsActive = Animal::count();

        // diferencia vs mes pasado (aprox)
        $inicioMesActual = now()->copy()->startOfMonth();
        $finMesActual    = now()->copy()->endOfMonth();
        $inicioMesAnt    = now()->copy()->subMonth()->startOfMonth();
        $finMesAnt       = now()->copy()->subMonth()->endOfMonth();

        $nuevosEsteMes = Animal::whereBetween('created_at', [$inicioMesActual, $finMesActual])->count();
        $nuevosMesAnt  = Animal::whereBetween('created_at', [$inicioMesAnt, $finMesAnt])->count();
        $animalsDiff   = $nuevosEsteMes - $nuevosMesAnt;

        // próximos partos (ajusta según tu tabla de salud)
        $upcomingBirths = EventoSalud::whereBetween('fecha_aplicacion', [$hoy, $hoy->copy()->addDays(7)])
            ->count();

        // alertas de vacunación (pendientes o próximas)
        $vaccinationAlerts = EventoSalud::where('fecha_aplicacion', '<=', $hoy->copy()->addDays(3))
            ->count();

        // inventario de alimento
        $totalAlimento = InventarioInsumo::sum('existencias');   // ajusta nombre de columna si es distinto
        $capacidadMax  = 3000; // pon aquí un valor "meta" de kilos totales
        $foodInventoryPercent = $capacidadMax > 0
            ? round(($totalAlimento / $capacidadMax) * 100)
            : 0;

        // estimar días disponibles (consumo diario aproximado)
        $consumoDiario = 200; // aquí luego podemos calcularlo con alimentaciones reales
        $foodDaysAvailable = $consumoDiario > 0
            ? floor($totalAlimento / $consumoDiario)
            : 0;

        $summary = [
            'animalsActive'        => $animalsActive,
            'animalsDiff'          => $animalsDiff,
            'upcomingBirths'       => $upcomingBirths,
            'vaccinationAlerts'    => $vaccinationAlerts,
            'foodInventoryPercent' => $foodInventoryPercent,
            'foodDaysAvailable'    => $foodDaysAvailable,
        ];

        // 🐑 2) DISTRIBUCIÓN DEL REBAÑO POR RAZA
        // El sistema es exclusivamente ovino, así que agrupar por especie ya no
        // aporta información: se agrupa por raza principal del catálogo.
        // Se agrupa por la expresión completa (no por el alias) para cumplir
        // con el modo ONLY_FULL_GROUP_BY de MySQL.
        $expresionRaza = "COALESCE(razas.nombre, animals.raza_original, animals.raza, 'Sin raza')";

        $speciesDistribution = Animal::query()
            ->leftJoin('razas', 'animals.raza_id', '=', 'razas.id')
            ->selectRaw("{$expresionRaza} as nombre, COUNT(*) as total")
            ->groupByRaw($expresionRaza)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->nombre,
                'value' => (int) $row->total,
                'icon'  => '🐑',
            ]);

        // 🥛 3) PRODUCCIÓN POR MES (últimos 6 meses)
        $productionByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->copy()->subMonths($i);
            $inicio = $mes->copy()->startOfMonth();
            $fin    = $mes->copy()->endOfMonth();

            $milk = Produccion::where('tipo', 'leche')
                ->whereBetween('fecha', [$inicio, $fin])
                ->sum('valor');

            $eggs = Produccion::where('tipo', 'huevo')
                ->whereBetween('fecha', [$inicio, $fin])
                ->sum('valor');

            $meat = Produccion::where('tipo', 'carne')
                ->whereBetween('fecha', [$inicio, $fin])
                ->sum('valor');

            $productionByMonth[] = [
                'month' => $mes->format('M'),
                'milk'  => (float) $milk,
                'eggs'  => (float) $eggs,
                'meat'  => (float) $meat,
            ];
        }

        // 🚨 4) ALERTAS (para la lista de abajo)
        $alerts = [];

        if ($vaccinationAlerts > 0) {
            $alerts[] = [
                'type'     => 'danger',
                'title'    => "{$vaccinationAlerts} animales con vacunas vencidas o próximas",
                'subtitle' => 'Revisa el módulo de Salud para reagendar',
                'badge'    => 'Urgente',
            ];
        }

        if ($foodInventoryPercent < 30) {
            $alerts[] = [
                'type'     => 'warning',
                'title'    => 'Inventario de alimento bajo',
                'subtitle' => "Solo queda {$foodDaysAvailable} días estimados",
                'badge'    => 'Atención',
            ];
        }

        if ($animalsDiff > 0) {
            $alerts[] = [
                'type'     => 'success',
                'title'    => "Tu hato creció en +{$animalsDiff} animales este mes",
                'subtitle' => 'Buen manejo reproductivo 👏',
                'badge'    => 'Buenas noticias',
            ];
        }

        // 💵 5) COSTOS Y UTILIDAD DEL MES
        $comparacionMes = $this->costoService->compararIngresosCostos(
            $inicioMesActual->toDateString(),
            $finMesActual->toDateString()
        );

        // 🐑 6) INDICADORES OVINOS DEL REBAÑO
        $inventario = $this->indicadores->inventario();
        $reproduccion = $this->indicadores->reproduccion(
            $inicioMesActual->toDateString(),
            $finMesActual->toDateString()
        );
        $economico = $this->indicadores->economico(
            $inicioMesActual->toDateString(),
            $finMesActual->toDateString()
        );

        return Inertia::render('Custom/Home', [
            'summary'            => $summary,
            'speciesDistribution'=> $speciesDistribution,
            'productionByMonth'  => $productionByMonth,
            // Las alertas del dashboard son ahora las operativas del rebaño.
            'alerts'             => $alerts,
            'alertasOperativas'  => $this->alertas->todas(),
            'costos'             => $comparacionMes,
            'rebano'             => [
                'activos'               => $inventario['total_activos'],
                'borregas_reproductoras'=> $inventario['borregas_reproductoras'],
                'sementales'            => $inventario['sementales'],
                'sin_identificador'     => $inventario['sin_identificador'],
                'gestaciones_activas'   => $reproduccion['gestaciones_activas'],
                'partos_proximos'       => $reproduccion['partos_proximos'],
                'crias_periodo'         => $reproduccion['crias_nacidas'],
                'prolificidad'          => $reproduccion['prolificidad'],
                'precio_estimado'       => $economico['precio_estimado_rebano'],
            ],
        ]);
    }

    public function animals()
    {
        return Inertia::render('Custom/Animals');
    }

    public function splash()
    {
        return Inertia::render('Custom/Splash');
    }

    public function login()
    {
        return Inertia::render('Custom/Login');
    }
}
