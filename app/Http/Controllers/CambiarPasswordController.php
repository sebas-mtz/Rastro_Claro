<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CambiarPasswordController extends Controller
{
    /** Muestra el formulario de cambio obligatorio de contraseña */
    public function show(): Response
    {
        return Inertia::render('Auth/CambiarPassword');
    }

    /** Procesa el cambio de contraseña */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Za-z]/',  // al menos una letra
                'regex:/[0-9]/',     // al menos un número
            ],
        ], [
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'=> 'Las contraseñas no coinciden.',
            'password.regex'    => 'La contraseña debe contener letras y números.',
        ]);

        $user = $request->user();
        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('success', '¡Contraseña actualizada! Bienvenido a ' . config('app.name') . '.');
    }
}
