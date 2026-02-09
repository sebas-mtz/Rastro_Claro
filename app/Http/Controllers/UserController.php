<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Lista de usuarios para el panel de admin.
     */
    public function index()
    {
        // Traemos datos básicos
        $users = User::select('id', 'name', 'email', 'role', 'plan', 'activo', 'created_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Actualizar rol y/o plan de un usuario.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|string',      // admin, vet, user, etc.
            'plan' => 'nullable|string',      // normal, premium, etc.
            'activo' => 'nullable|boolean',
        ]);

        // Si no viene activo, lo dejamos igual
        if (!array_key_exists('activo', $data)) {
            unset($data['activo']);
        }

        $user->update($data);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }
}
