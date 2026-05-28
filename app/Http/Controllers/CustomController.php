<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Animal;
use App\Models\Produccion;
use App\Models\InventarioInsumo;
use App\Models\EventoSalud;
use Carbon\Carbon;

class CustomController extends Controller
{
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

        // 🐐 2) DISTRIBUCIÓN POR ESPECIE
        $rows = Animal::selectRaw('especie, COUNT(*) as total')
            ->groupBy('especie')
            ->get();

        $speciesDistribution = $rows->map(function ($row) {
            $icons = [
                'bovino'  => '🐄',
                'porcino' => '🐖',
                'ovino'   => '🐑',
                'caprino' => '🐐',
                'equino'  => '🐎',
                'ave'     => '🐓',
            ];

            $key  = strtolower($row->especie ?? 'otro');
            $icon = $icons[$key] ?? '🐾';

            return [
                'name'  => ucfirst($row->especie ?? 'Otro'),
                'value' => (int) $row->total,
                'icon'  => $icon,
            ];
        });

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

        return Inertia::render('Custom/Home', [
            'summary'            => $summary,
            'speciesDistribution'=> $speciesDistribution,
            'productionByMonth'  => $productionByMonth,
            'alerts'             => $alerts,
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
