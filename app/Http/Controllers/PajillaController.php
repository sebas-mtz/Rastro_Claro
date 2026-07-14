<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DonadorExterno;
use App\Models\Pajilla;
use App\Models\Termo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PajillaController extends Controller
{
    public function index()
    {
        Pajilla::where('estado', 'disponible')->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<=', today())->update(['estado' => 'vencida',]);
        $pajillas = Pajilla::with(['termo', 'animal', 'donadorExterno'])->latest()->paginate(10);

        return Inertia::render('Pajillas/Index', [
            'pajillas' => $pajillas,
        ]);
    }

    public function create()
    {
        return Inertia::render('Pajillas/Create', [
            'termos' => Termo::where('estado', 'activo')->orderBy('codigo')->get(),
            'animales' => Animal::where('sexo', 'M')->orderBy('arete')->get(),
            'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'termo_id' => ['required', 'exists:termos,id'],
            'origen' => ['required', 'in:interno,externo'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'donador_externo_id' => ['nullable', 'exists:donadores_externos,id'],
            'codigo_inicial' => ['required', 'string', 'max:50'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'lote' => ['nullable', 'string', 'max:100'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['origen'] === 'interno' && empty($data['animal_id'])) {
            return back()->withErrors([
                'animal_id' => 'Debes seleccionar un semental interno.',
            ])->withInput();
        }

        if ($data['origen'] === 'externo' && empty($data['donador_externo_id'])) {
            return back()->withErrors([
                'donador_externo_id' => 'Debes seleccionar un donador externo.',
            ])->withInput();
        }

        if ($data['origen'] === 'interno') {
            $data['donador_externo_id'] = null;
        } else {
            $data['animal_id'] = null;
        }

        $codigos = $this->generarCodigosPajillas($data['codigo_inicial'], $data['cantidad']);

        DB::transaction(function () use ($data, $codigos) {
            foreach ($codigos as $codigo) {
                Pajilla::create([
                    'termo_id' => $data['termo_id'],
                    'animal_id' => $data['animal_id'] ?? null,
                    'donador_externo_id' => $data['donador_externo_id'] ?? null,
                    'codigo' => $codigo,
                    'lote' => $data['lote'] ?? null,
                    'fecha_ingreso' => now()->toDateString(),
                    'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                    'fecha_utilizacion' => null,
                    'estado' => 'disponible',
                    'observaciones' => $data['observaciones'] ?? null,
                ]);
            }
        });

        $mensaje = count($codigos) === 1
            ? 'Pajilla registrada correctamente.'
            : count($codigos) . ' pajillas registradas correctamente.';

        return redirect()->route('genetica.index')->with('success', $mensaje);
    }

    public function show(Pajilla $pajilla)
    {
        $pajilla->load(['termo', 'animal', 'donadorExterno']);

        return Inertia::render('Pajillas/Show', [
            'pajilla' => $pajilla,
        ]);
    }

    public function edit(Pajilla $pajilla)
    {
        $pajilla->load(['termo', 'animal', 'donadorExterno']);

        return Inertia::render('Pajillas/Edit', [
            'pajilla' => $pajilla,
            'termos' => Termo::where('estado', 'activo')->orderBy('codigo')->get(),
            'animales' => Animal::where('sexo', 'M')->orderBy('arete')->get(),
            'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Pajilla $pajilla)
    {
        $data = $request->validate([
            'termo_id' => ['required', 'exists:termos,id'],
            'origen' => ['required', 'in:interno,externo'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'donador_externo_id' => ['nullable', 'exists:donadores_externos,id'],
            'codigo' => ['required', 'string', 'max:50', 'unique:pajillas,codigo,' . $pajilla->id],
            'lote' => ['nullable', 'string', 'max:100'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'estado' => ['required', 'in:disponible,utilizada,dañada,vencida'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['origen'] === 'interno' && empty($data['animal_id'])) {
            return back()->withErrors([
                'animal_id' => 'Debes seleccionar un semental interno.',
            ])->withInput();
        }

        if ($data['origen'] === 'externo' && empty($data['donador_externo_id'])) {
            return back()->withErrors([
                'donador_externo_id' => 'Debes seleccionar un donador externo.',
            ])->withInput();
        }

        if ($data['origen'] === 'interno') {
            $data['donador_externo_id'] = null;
        } else {
            $data['animal_id'] = null;
        }

        unset($data['origen']);

        if ($data['estado'] === 'utilizada' && empty($pajilla->fecha_utilizacion)) {
            $data['fecha_utilizacion'] = now();
        }

        if ($data['estado'] !== 'utilizada') {
            $data['fecha_utilizacion'] = null;
        }

        $pajilla->update($data);

        return redirect()->route('genetica.index')->with('success', 'Pajilla actualizada correctamente.');
    }

    public function destroy(Pajilla $pajilla)
    {
        $pajilla->delete();

        return redirect()->route('genetica.index')->with('success', 'Pajilla eliminada correctamente.');
    }

    private function generarCodigosPajillas(string $codigoInicial, int $cantidad): array
    {
        if ($cantidad === 1) {
            if (Pajilla::where('codigo', $codigoInicial)->exists()) {
                abort(422, "La pajilla con código {$codigoInicial} ya existe.");
            }

            return [$codigoInicial];
        }

        if (!preg_match('/^(.*?)(\d+)$/', $codigoInicial, $coincidencias)) {
            abort(422, 'El código inicial debe terminar en un número para registrar varias pajillas.');
        }

        $prefijo = $coincidencias[1];
        $numeroInicial = (int) $coincidencias[2];
        $longitudNumero = strlen($coincidencias[2]);
        $codigos = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $numero = str_pad($numeroInicial + $i, $longitudNumero, '0', STR_PAD_LEFT);
            $codigo = $prefijo . $numero;

            if (Pajilla::where('codigo', $codigo)->exists()) {
                abort(422, "La pajilla con código {$codigo} ya existe.");
            }

            $codigos[] = $codigo;
        }

        return $codigos;
    }
}