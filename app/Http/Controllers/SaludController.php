<?php

namespace App\Http\Controllers;

use App\Models\Salud;
use App\Models\Animal;
use App\Models\Vacuna;
use App\Models\Tratamiento; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SaludController extends Controller
{
    // 📅 Mostrar calendario y listas
    public function calendar(Request $request)
{
    $monthParam = $request->string('month')->toString(); // "2025-11"
    $start = $monthParam
        ? Carbon::parse($monthParam.'-01')->startOfMonth()
        : now()->startOfMonth();
    $end = (clone $start)->endOfMonth();
    $hoy = now()->startOfDay();

    // 1) Registros de salud del mes
    $registros = Salud::with(['animal:id,arete,especie', 'vacuna:id,nombre'])
        ->whereBetween('fecha_programada', [$start, $end])
        ->orderBy('fecha_programada')
        ->get();

    // 2) Semáforo del calendario
    $priority = ['red'=>3,'yellow'=>2,'green'=>1];
    $events = [];
    foreach ($registros as $r) {
        $key = $r->fecha_programada->format('Y-m-d');
        $c   = $r->color; // accesor del modelo
        if ($c && (!isset($events[$key]) || $priority[$c] > $priority[$events[$key]])) {
            $events[$key] = $c;
        }
    }

    // 3) Listas básicas
    $appointments = $registros->map(fn($r)=>[
        'id'     => $r->id,
        'fecha'  => $r->fecha_programada->format('Y-m-d'),
        'hora'   => $r->hora,
        'animal' => $r->animal?->arete ?? '#'.$r->animal_id,
        'vacuna' => $r->vacuna?->nombre,
        'notas'  => $r->observaciones,
        'estado' => $r->estado,
    ])->values();

    $pending = $registros
        ->filter(fn($r)=>!$r->fecha_aplicacion && $r->estado !== 'aplicada')
        ->map(fn($r)=>[
            'id'     => $r->id,
            'fecha'  => $r->fecha_programada->format('Y-m-d'),
            'hora'   => $r->hora,
            'animal' => $r->animal?->arete ?? '#'.$r->animal_id,
            'vacuna' => $r->vacuna?->nombre,
            'notas'  => $r->observaciones,
        ])->values();

    $done = $registros
        ->filter(fn($r)=>$r->fecha_aplicacion || $r->estado === 'aplicada')
        ->map(fn($r)=>[
            'id'     => $r->id,
            'fecha'  => ($r->fecha_aplicacion?->format('Y-m-d')) ?? $r->fecha_programada->format('Y-m-d'),
            'hora'   => $r->hora,
            'animal' => $r->animal?->arete ?? '#'.$r->animal_id,
            'vacuna' => $r->vacuna?->nombre,
            'notas'  => $r->observaciones,
        ])->values();

    // 4) Alertas de vacunación
    $alerts = [
        'up_to_date' => $registros->whereNotNull('fecha_aplicacion')->map(fn($r)=>[
            'title'    => $r->vacuna?->nombre,
            'subtitle' => "Aplicada a ".($r->animal?->arete ?? '#'.$r->animal_id),
            'level'    => 'green',
        ])->values(),
        'due_soon' => $registros
            ->filter(fn($r)=>!$r->fecha_aplicacion && $r->fecha_programada->between($hoy, $hoy->copy()->addDays(7)))
            ->map(fn($r)=>[
                'title'    => $r->vacuna?->nombre,
                'subtitle' => ($r->animal?->arete ?? '#'.$r->animal_id)
                              .' en '.$r->fecha_programada->diffInDays($hoy).' días',
                'level'    => 'yellow',
            ])->values(),
        'overdue' => $registros
            ->filter(fn($r)=>!$r->fecha_aplicacion && $r->fecha_programada->lt($hoy))
            ->map(fn($r)=>[
                'title'    => $r->vacuna?->nombre,
                'subtitle' => "Atrasada para ".($r->animal?->arete ?? '#'.$r->animal_id),
                'level'    => 'red',
            ])->values(),
    ];

    // 5) TRATAMIENTOS DESDE BD
    $tratamientos = Tratamiento::with('animal:id,arete')
        ->whereBetween('fecha_inicio', [$start, $end])
        ->orWhereBetween('fecha_fin', [$start, $end])
        ->orderBy('fecha_inicio')
        ->get();

    $treatments = $tratamientos->map(fn($t)=>[
        'id'     => $t->id,
        'nombre' => $t->nombre,
        'animal' => $t->animal?->arete ?? '#'.$t->animal_id,
        'rango'  => $t->fecha_inicio->format('d/m/Y').' – '.($t->fecha_fin?->format('d/m/Y') ?? 'sin fecha fin'),
        'estado' => $t->estado, // "activo" | "completado"
    ])->values();

    // 6) RECOMENDACIONES DINÁMICAS
    // Tomamos especies existentes y vacunas configuradas
    $species = Animal::select('especie')->distinct()->pluck('especie');
    $recommendations = [];

    foreach ($species as $i => $esp) {
        $vacunasEsp = Vacuna::where(function($q) use ($esp){
                $q->where('especie_objetivo', $esp)
                  ->orWhereNull('especie_objetivo');
            })
            ->orderBy('nombre')
            ->get();

        if ($vacunasEsp->isEmpty()) continue;

        $recommendations[] = [
            'id'         => $i + 1,
            'especie'    => $esp,
            'edad'       => 'Según categoría del hato',
            'frecuencia' => $vacunasEsp->first()->pauta ?? 'Anual',
            'vacunas'    => $vacunasEsp->pluck('nombre')->values()->all(),
        ];
    }

    // 7) Combos para el formulario
    $animals = Animal::select('id','arete')->orderBy('arete')->get();
    $vacunas = Vacuna::select('id','nombre')->orderBy('nombre')->get();

    // 8) Enviamos TODO al componente Health
    return Inertia::render('Custom/Health', [
        'events'          => $events,
        'alerts'          => $alerts,
        'appointments'    => $appointments,
        'pending'         => $pending,
        'done'            => $done,
        'treatments'      => $treatments,
        'recommendations' => $recommendations,
        'animals'         => $animals,
        'vacunas'         => $vacunas,
        'year'            => (int) $start->year,
        'month'           => (int) $start->month,
        'month_iso'       => $start->format('Y-m'),
    ]);
}


    // 🩺 Guardar nueva cita
    public function storeAppointment(Request $request)
    {
        $data = $request->validate([
            'animal_id' => ['required','exists:animals,id'],
            'vacuna_id' => ['nullable','exists:vacunas,id'],
            'fecha'     => ['required','date'],
            'hora'      => ['nullable','date_format:H:i'],
            'notas'     => ['nullable','string','max:500'],
        ]);

        Salud::create([
            'animal_id'        => $data['animal_id'],
            'vacuna_id'        => $data['vacuna_id'] ?? null,
            'fecha_programada' => $data['fecha'],
            'hora'             => $data['hora'] ?? null,
            'observaciones'    => $data['notas'] ?? null,
            'estado'           => 'pendiente',
        ]);

        $monthIso = Carbon::parse($data['fecha'])->format('Y-m');

        return redirect()
            ->route('health.custom', ['month' => $monthIso])
            ->with('success','Cita creada correctamente');
    }

    // ✅ Marcar cita como realizada
    public function complete(Salud $salud, Request $request)
    {
        $fecha = $request->input('fecha_aplicacion');

        $salud->update([
            'estado'           => 'aplicada',
            'fecha_aplicacion' => $fecha
                ? Carbon::parse($fecha)->toDateString()
                : now()->toDateString(),
        ]);

        $monthIso = ($salud->fecha_programada ?? now())->format('Y-m');

        return redirect()
            ->route('health.custom', ['month' => $monthIso])
            ->with('success', 'Cita marcada como realizada');
    }
}
