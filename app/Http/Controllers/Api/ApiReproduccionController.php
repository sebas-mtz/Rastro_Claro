<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\ServicioReproductivo;
use App\Models\Animal;
use Illuminate\Http\Request;

class ApiReproduccionController extends Controller
{
    // ── Gestaciones ───────────────────────────────────────────────────────
    public function gestaciones(Request $request)
    {
        $eventos = EventoReproductivo::with(['hembra', 'servicio'])
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'hembra_id'       => $e->hembra_id,
                'arete'           => $e->hembra?->arete,
                'alias'           => $e->hembra?->alias,
                'tipo_evento'     => $e->tipo_evento,
                'fecha'           => $e->fecha,
                'estado'          => $e->estado ?? 'Gestante',
                'fecha_parto_est' => $e->fecha_parto_est ?? '',
                'semental'        => $e->macho?->arete ?? '',
                'metodo'          => $e->metodo ?? 'Monta natural',
            ]);

        return response()->json($eventos);
    }

    public function storeGestacion(Request $request)
    {
        $validated = $request->validate([
            'hembra_id'       => 'required|exists:animals,id',
            'macho_id'        => 'nullable|exists:animals,id',
            'fecha'           => 'required|date',
            'fecha_parto_est' => 'nullable|date',
            'tipo_evento'     => 'nullable|string',
            'estado'          => 'nullable|string',
            'metodo'          => 'nullable|string',
        ]);

        $evento = EventoReproductivo::create($validated);

        return response()->json([
            'message' => 'Gestación registrada',
            'evento'  => $evento,
        ], 201);
    }

    // ── Partos ────────────────────────────────────────────────────────────
    public function partos(Request $request)
    {
        $partos = Parto::with('animal')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'animal_id'      => $p->animal_id,
                'arete'          => $p->animal?->arete,
                'alias'          => $p->animal?->alias,
                'fecha'          => $p->fecha,
                'num_crias'      => $p->num_crias ?? 1,
                'peso_cria'      => $p->peso_cria ?? 0,
                'sexo_cria'      => $p->sexo_cria ?? '',
                'complicaciones' => $p->complicaciones ?? 'Ninguna',
            ]);

        return response()->json($partos);
    }

    public function storeParto(Request $request)
    {
        $validated = $request->validate([
            'animal_id'      => 'required|exists:animals,id',
            'fecha'          => 'required|date',
            'num_crias'      => 'nullable|integer',
            'peso_cria'      => 'nullable|numeric',
            'sexo_cria'      => 'nullable|string',
            'complicaciones' => 'nullable|string',
        ]);

        $parto = Parto::create($validated);

        return response()->json([
            'message' => 'Parto registrado',
            'parto'   => $parto,
        ], 201);
    }
}