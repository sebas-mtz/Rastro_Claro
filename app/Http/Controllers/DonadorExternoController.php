<?php

namespace App\Http\Controllers;

use App\Models\DonadorExterno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DonadorExternoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:100',
                'unique:donadores_externos,codigo',
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'raza' => [
                'nullable',
                'string',
                'max:100',
            ],
            'proveedor' => [
                'nullable',
                'string',
                'max:150',
            ],
            'registro_genealogico' => [
                'nullable',
                'string',
                'max:150',
            ],
            'pais_origen' => [
                'nullable',
                'string',
                'max:100',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DonadorExterno::create($datos);

        return redirect()
            ->back()
            ->with(
                'success',
                'Donador externo registrado correctamente.'
            );
    }
}