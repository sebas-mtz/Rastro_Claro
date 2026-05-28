<?php

namespace App\Http\Controllers;

use App\Models\HistorialActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Módulo de Trabajadores — gestión de usuarios del sistema.
 * Solo accesible para Administradores.
 */
class TrabajadorController extends Controller
{
    // ─── INDEX ────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $this->autorizarAdmin();

        $trabajadores = User::select(
                'id','name','email','telefono','role','activo',
                'creado_por','ultimo_login','created_at'
            )
            ->with('creadoPor:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'email'       => $u->email,
                'telefono'    => $u->telefono,
                'role'        => $u->role,
                'roleLabel'   => $u->roleLabel(),
                'activo'      => $u->activo,
                'creadoPor'   => $u->creadoPor?->name,
                'ultimo_login'=> $u->ultimo_login?->diffForHumans(),
                'created_at'  => $u->created_at->format('d/m/Y'),
            ]);

        // Historial global (últimas 50 acciones)
        $historial = \App\Models\HistorialActividad::orderByDesc('created_at')
            ->limit(50)
            ->get(['id','user_nombre','modulo','accion','descripcion','created_at'])
            ->map(fn($h) => [
                'id'          => $h->id,
                'user_nombre' => $h->user_nombre,
                'modulo'      => $h->modulo,
                'accion'      => $h->accion,
                'descripcion' => $h->descripcion,
                'fecha'       => $h->created_at->format('d/m/Y H:i'),
                'icono'       => \App\Models\HistorialActividad::iconoPorAccion($h->accion),
            ]);

        return Inertia::render('Trabajadores/Index', [
            'trabajadores'  => $trabajadores,
            'historial'     => $historial,
            'roles'         => collect(User::ROLES)->map(fn($r) => [
                'value' => $r,
                'label' => (new User(['role' => $r]))->roleLabel(),
            ])->values(),
            'currentUserId' => Auth::id(),
            'puedeCrear'    => Auth::user()->isAdmin(),
            'puedeEditar'   => Auth::user()->isAdmin(),
        ]);
    }

    // ─── STORE (crear trabajador) ─────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $this->autorizarAdmin();

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'role'     => ['required', Rule::in(User::ROLES)],
        ], [
            'email.unique' => 'Ya existe un usuario con ese correo.',
        ]);

        if ($validated['role'] === User::ROLE_ADMINISTRADOR && !Auth::user()->isAdmin()) {
            return back()->with('error', 'Solo un Administrador puede crear otros Administradores.');
        }

        // Generar contraseña temporal segura
        $passwordTemporal = \Illuminate\Support\Str::password(12);

        $trabajador = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'telefono'          => $validated['telefono'] ?? null,
            'role'              => $validated['role'],
            'password'          => \Illuminate\Support\Facades\Hash::make($passwordTemporal),
            'activo'            => true,
            'creado_por'        => Auth::id(),
            'email_verified_at' => now(),
            'must_change_password' => true,
        ]);

        // Enviar invitación por correo
        try {
            \Illuminate\Support\Facades\Mail::to($trabajador->email)
                ->send(new \App\Mail\InvitacionTrabajador($trabajador, $passwordTemporal, Auth::user()));
            $mensajeCorreo = " Se envió invitación a {$trabajador->email}.";
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando invitación: ' . $e->getMessage());
            $mensajeCorreo = " (No se pudo enviar el correo — contraseña temporal: {$passwordTemporal})";
        }

        HistorialActividad::registrar(
            'trabajadores', 'crear',
            "Creó al trabajador {$trabajador->name} ({$trabajador->roleLabel()})",
            $trabajador
        );

        return redirect()->route('trabajadores.index')
            ->with('success',"Trabajador {$trabajador->name} creado correctamente.{$mensajeCorreo}");
    }

    // ─── UPDATE (editar trabajador) ───────────────────────────────

    public function update(Request $request, User $trabajador): RedirectResponse
    {
        $this->autorizarAdmin();

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required','email','max:150', Rule::unique('users','email')->ignore($trabajador->id)],
            'telefono' => 'nullable|string|max:20',
            'role'     => ['required', Rule::in(User::ROLES)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Solo admins pueden cambiar un usuario a administrador
        if ($validated['role'] === User::ROLE_ADMINISTRADOR && !Auth::user()->isAdmin()) {
            return back()->with('error', 'No tienes permiso para asignar el rol de Administrador.');
        }

        // No permitir que un admin se quite el rol a sí mismo si es el único admin
        if ($trabajador->id === Auth::id()
            && $validated['role'] !== User::ROLE_ADMINISTRADOR
            && $trabajador->isAdmin()
        ) {
            $otrosAdmins = User::where('role', User::ROLE_ADMINISTRADOR)
                ->where('id', '!=', Auth::id())
                ->count();
            if ($otrosAdmins === 0) {
                return back()->with('error', 'No puedes quitarte el rol de Administrador si eres el único administrador.');
            }
        }

        $antes = $trabajador->only(['name','email','telefono','role']);

        $datosActualizar = [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'role'     => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $datosActualizar['password'] = Hash::make($validated['password']);
        }

        $trabajador->update($datosActualizar);

        HistorialActividad::registrar(
            'trabajadores', 'editar',
            "Editó al trabajador {$trabajador->name}",
            $trabajador,
            $antes,
            $trabajador->only(['name','email','telefono','role'])
        );

        return redirect()->route('trabajadores.index')
            ->with('success', "Trabajador {$trabajador->name} actualizado.");
    }

    // ─── TOGGLE ACTIVO (activar/desactivar) ───────────────────────

    public function toggleActivo(Request $request, User $trabajador): RedirectResponse
    {
        $this->autorizarAdmin();

        // No desactivar al propio admin que lo gestiona
        if ($trabajador->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $nuevoEstado = !$trabajador->activo;
        $trabajador->update(['activo' => $nuevoEstado]);

        $accion = $nuevoEstado ? 'activó' : 'desactivó';
        HistorialActividad::registrar(
            'trabajadores',
            $nuevoEstado ? 'activar' : 'desactivar',
            "Se {$accion} al trabajador {$trabajador->name}",
            $trabajador
        );

        $label = $nuevoEstado ? 'activado' : 'desactivado';
        return redirect()->route('trabajadores.index')
            ->with('success', "Trabajador {$trabajador->name} {$label}.");
    }

    // ─── HISTORIAL DE UN TRABAJADOR ───────────────────────────────

    public function historial(User $trabajador): Response
    {
        $this->autorizarAdmin();

        $historial = \App\Models\HistorialActividad::where('user_id', $trabajador->id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn($h) => [
                'id'          => $h->id,
                'modulo'      => $h->modulo,
                'accion'      => $h->accion,
                'descripcion' => $h->descripcion,
                'modelo'      => $h->modelo,
                'modelo_id'   => $h->modelo_id,
                'fecha'       => $h->created_at->format('d/m/Y H:i'),
                'icono'       => \App\Models\HistorialActividad::iconoPorAccion($h->accion),
            ]);

        return Inertia::render('Trabajadores/Historial', [
            'trabajador' => [
                'id'        => $trabajador->id,
                'name'      => $trabajador->name,
                'email'     => $trabajador->email,
                'roleLabel' => $trabajador->roleLabel(),
            ],
            'historial' => $historial,
        ]);
    }

    // ─── DESTROY (no borrar — solo desactivar si tiene historial) ─

    public function destroy(User $trabajador): RedirectResponse
    {
        $this->autorizarAdmin();

        if ($trabajador->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $tieneHistorial = \App\Models\HistorialActividad::where('user_id', $trabajador->id)->exists();

        if ($tieneHistorial) {
            // Solo desactivar, nunca borrar con historial
            $trabajador->update(['activo' => false]);
            HistorialActividad::registrar(
                'trabajadores', 'desactivar',
                "Desactivó al trabajador {$trabajador->name} (tiene historial de actividad)",
                $trabajador
            );
            return redirect()->route('trabajadores.index')
                ->with('warning', "El trabajador {$trabajador->name} tiene historial de actividad y fue desactivado en lugar de eliminado.");
        }

        HistorialActividad::registrar(
            'trabajadores', 'eliminar',
            "Eliminó al trabajador {$trabajador->name}",
            $trabajador
        );
        $trabajador->delete();

        return redirect()->route('trabajadores.index')
            ->with('success', "Trabajador eliminado.");
    }

    // ─── Helpers privados ─────────────────────────────────────────

    private function autorizarAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Solo los Administradores pueden gestionar trabajadores.');
        }
        if (!$user->activo) {
            abort(403, 'Tu cuenta está desactivada.');
        }
    }
}