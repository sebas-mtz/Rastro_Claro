<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tarea;
use Illuminate\Http\Request;

class ApiTrabajadorController extends Controller
{
    public function index(Request $request)
    {
        $trabajadores = User::when($request->activo !== null, fn($q) => $q->where('activo', $request->activo))
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'nombre'        => $t->name,
                'email'         => $t->email,
                'rol'           => $t->role ?? $t->rol ?? 'Peón',
                'telefono'      => $t->telefono ?? '',
                'salario'       => $t->salario ?? 0,
                'activo'        => $t->activo ?? true,
                'fecha_ingreso' => $t->fecha_ingreso ?? $t->created_at?->format('Y-m-d'),
                'tareas'        => Tarea::where('user_id', $t->id)
                    ->whereDate('created_at', today())
                    ->get()
                    ->map(fn($ta) => [
                        'id'          => $ta->id,
                        'descripcion' => $ta->descripcion,
                        'estado'      => $ta->estado ?? 'Pendiente',
                        'hora'        => $ta->hora ?? '',
                    ]),
            ]);

        return response()->json($trabajadores);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'rol'       => 'nullable|string',
            'telefono'  => 'nullable|string',
            'salario'   => 'nullable|numeric',
            'password'  => 'nullable|string|min:8',
        ]);

        $trabajador = User::create([
            'name'      => $validated['nombre'],
            'email'     => $validated['email'],
            'role'      => $validated['rol'] ?? 'Peón',
            'telefono'  => $validated['telefono'] ?? null,
            'salario'   => $validated['salario'] ?? 0,
            'activo'    => true,
            'password'  => bcrypt($validated['password'] ?? 'password123'),
        ]);

        return response()->json([
            'message'     => 'Trabajador registrado',
            'trabajador'  => $trabajador,
        ], 201);
    }

    public function show(User $user)
    {
        $tareas = Tarea::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'id'            => $user->id,
            'nombre'        => $user->name,
            'email'         => $user->email,
            'rol'           => $user->role ?? 'Peón',
            'telefono'      => $user->telefono ?? '',
            'salario'       => $user->salario ?? 0,
            'activo'        => $user->activo ?? true,
            'fecha_ingreso' => $user->created_at?->format('Y-m-d'),
            'tareas'        => $tareas,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nombre'   => 'sometimes|string|max:255',
            'rol'      => 'nullable|string',
            'telefono' => 'nullable|string',
            'salario'  => 'nullable|numeric',
            'activo'   => 'nullable|boolean',
        ]);

        $user->update([
            'name'     => $validated['nombre'] ?? $user->name,
            'role'     => $validated['rol']    ?? $user->role,
            'telefono' => $validated['telefono'] ?? $user->telefono,
            'salario'  => $validated['salario']  ?? $user->salario,
            'activo'   => $validated['activo']   ?? $user->activo,
        ]);

        return response()->json([
            'message'    => 'Trabajador actualizado',
            'trabajador' => $user,
        ]);
    }
}