<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'plan', 'activo', 'created_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role'   => 'required|in:admin,user',
            'plan'   => 'nullable|in:normal,premium',
            'activo' => 'boolean',
        ]);

        // si no manda plan, asumimos "normal"
        if (!isset($data['plan']) || $data['plan'] === null) {
            $data['plan'] = 'normal';
        }

        $user->update($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
}
