<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'estado' => ['nullable', 'in:pendiente,completada,suspendida,vencida'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $usuario = Auth::user();

        $tareas = Tarea::query()
            ->with([
                'asignado:id,name,email',
                'creador:id,name,email',
            ])
            ->where('asignado_a', $usuario->id)
            ->when($request->estado === 'vencida', function ($query) {
                $query->where('estado', 'pendiente')
                    ->where('fecha_recordatorio', '<', now());
            })
            ->when(
                $request->estado && $request->estado !== 'vencida',
                fn ($query) => $query->where('estado', $request->estado)
            )
            ->when($request->buscar, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })
            ->orderByRaw("
                CASE
                    WHEN estado = 'pendiente' THEN 1
                    WHEN estado = 'suspendida' THEN 2
                    WHEN estado = 'completada' THEN 3
                END
            ")
            ->orderBy('fecha_recordatorio')
            ->paginate(10)
            ->withQueryString();

        $usuarios = User::query()
            ->whereKey($usuario->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $resumen = [
            'pendientes' => Tarea::where('asignado_a', $usuario->id)
                ->where('estado', 'pendiente')
                ->where('fecha_recordatorio', '>=', now())
                ->count(),

            'vencidas' => Tarea::where('asignado_a', $usuario->id)
                ->where('estado', 'pendiente')
                ->where('fecha_recordatorio', '<', now())
                ->count(),

            'completadas' => Tarea::where('asignado_a', $usuario->id)
                ->where('estado', 'completada')
                ->count(),

            'suspendidas' => Tarea::where('asignado_a', $usuario->id)
                ->where('estado', 'suspendida')
                ->count(),
        ];

        return Inertia::render('Tareas/Index', [
            'tareas' => $tareas,
            'usuarios' => $usuarios,
            'resumen' => $resumen,
            'filtros' => $request->only('estado', 'buscar'),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'asignado_a' => ['nullable'],
            'fecha_recordatorio' => ['required', 'date', 'after:now'],
        ]);

        Tarea::create([
            ...$datos,
            'asignado_a' => Auth::id(),
            'creado_por' => Auth::id(),
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Tarea creada correctamente.');
    }

    public function update(Request $request, Tarea $tarea)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'asignado_a' => ['nullable'],
            'fecha_recordatorio' => ['required', 'date'],
        ]);

        $tarea->update([
            ...$datos,
            'asignado_a' => Auth::id(),

            // Se permite volver a notificar si cambió la fecha.
            'notificada_en' => $tarea->fecha_recordatorio->toDateTimeString()
                !== $datos['fecha_recordatorio']
                    ? null
                    : $tarea->notificada_en,
        ]);

        return back()->with('success', 'Tarea actualizada correctamente.');
    }

    public function completar(Tarea $tarea)
    {
        abort_unless($tarea->asignado_a === Auth::id(), 403);

        $tarea->update([
            'estado' => 'completada',
            'completada_en' => now(),
            'suspendida_en' => null,
        ]);

        return back()->with('success', 'Tarea completada.');
    }

    public function suspender(Tarea $tarea)
    {
        abort_unless($tarea->asignado_a === Auth::id(), 403);

        $tarea->update([
            'estado' => 'suspendida',
            'suspendida_en' => now(),
        ]);

        return back()->with('success', 'Tarea suspendida.');
    }

    public function reactivar(Tarea $tarea)
    {
        abort_unless($tarea->asignado_a === Auth::id(), 403);

        $tarea->update([
            'estado' => 'pendiente',
            'suspendida_en' => null,
            'completada_en' => null,
        ]);

        return back()->with('success', 'Tarea reactivada.');
    }

    public function destroy(Tarea $tarea)
    {
        abort_unless(
            $tarea->creado_por === Auth::id()
            || $tarea->asignado_a === Auth::id(),
            403
        );

        $tarea->delete();

        return back()->with('success', 'Tarea eliminada.');
    }
}
