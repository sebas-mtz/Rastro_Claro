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
        $pajillas = Pajilla::with(['termo', 'animal', 'donadorExterno'])->latest()->paginate(10);

        return Inertia::render('Pajillas/Index', [
            'pajillas' => $pajillas,
        ]);
    }

    public function create()
    {
        $termos = Termo::where('estado', 'activo')->orderBy('codigo')->get();
        $termos = Termo::conOcupacion($termos);

        return Inertia::render('Pajillas/Create', [
            'termos' => $termos, // trae canastillas_detalle y espacios_libres_total
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
            'fecha_colecta' => ['nullable', 'date'],
            'capacidad_pajilla' => ['required', 'numeric', 'min:0'],
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

        $termo = Termo::findOrFail($data['termo_id']);

        // Un termo que no está activo no admite pajillas nuevas.
        if ($termo->estado !== 'activo') {
            return back()->withErrors([
                'termo_id' => "El termo {$termo->codigo} está en estado '{$termo->estado}' y no admite nuevas pajillas.",
            ])->withInput();
        }

        $porCanastilla = $termo->capacidad_canastilla;
        $totalCanastillas = $termo->numero_canastillas;

        $ocupacion = Pajilla::where('termo_id', $termo->id)
            ->where('estado', '!=', 'utilizada')
            ->whereNotNull('canastilla_numero')
            ->groupBy('canastilla_numero')
            ->selectRaw('canastilla_numero, count(*) as total')
            ->pluck('total', 'canastilla_numero');

        $asignaciones = [];
        for ($c = 1; $c <= $totalCanastillas && count($asignaciones) < $data['cantidad']; $c++) {
            $ocupadas = $ocupacion[$c] ?? 0;
            $libres = $porCanastilla - $ocupadas;

            for ($i = 0; $i < $libres && count($asignaciones) < $data['cantidad']; $i++) {
                $asignaciones[] = $c;
            }
        }

        if (count($asignaciones) < $data['cantidad']) {
            return back()->withErrors([
                'cantidad' => "No hay espacio suficiente. Solo caben " . count($asignaciones) . " pajilla(s) más en el termo {$termo->codigo}.",
            ])->withInput();
        }

        $codigos = $this->generarCodigosPajillas($data['codigo_inicial'], $data['cantidad']);

        DB::transaction(function () use ($data, $codigos, $asignaciones) {
            foreach ($codigos as $index => $codigo) {
                Pajilla::create([
                    'termo_id' => $data['termo_id'],
                    'canastilla_numero' => $asignaciones[$index],
                    'animal_id' => $data['animal_id'] ?? null,
                    'donador_externo_id' => $data['donador_externo_id'] ?? null,
                    'codigo' => $codigo,
                    'lote' => $data['lote'] ?? null,
                    'fecha_ingreso' => now()->toDateString(),
                    'fecha_colecta' => $data['fecha_colecta'] ?? null,
                    'capacidad_pajilla' => $data['capacidad_pajilla'],
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
        // Una pajilla dañada o inactiva queda bloqueada para siempre.
        if (in_array($pajilla->estado, ['dañada', 'inactiva'])) {
            return back()->withErrors([
                'estado' => 'Esta pajilla está marcada como dañada o inactiva y ya no puede modificarse.',
            ]);
        }

        $data = $request->validate([
            'termo_id' => ['required', 'exists:termos,id'],
            'origen' => ['required', 'in:interno,externo'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'donador_externo_id' => ['nullable', 'exists:donadores_externos,id'],
            'codigo' => ['required', 'string', 'max:50', 'unique:pajillas,codigo,' . $pajilla->id],
            'lote' => ['nullable', 'string', 'max:100'],
            'fecha_colecta' => ['nullable', 'date'],
            'capacidad_pajilla' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', 'in:disponible,utilizada,dañada,inactiva'],
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

        // Si cambia de termo, necesita una canastilla nueva en el termo destino.
        if ((int) $data['termo_id'] !== (int) $pajilla->termo_id) {
            $termo = Termo::findOrFail($data['termo_id']);

            if ($termo->estado !== 'activo') {
                return back()->withErrors([
                    'termo_id' => "El termo {$termo->codigo} está en estado '{$termo->estado}' y no admite pajillas.",
                ])->withInput();
            }

            $ocupacion = Pajilla::where('termo_id', $termo->id)
                ->where('id', '!=', $pajilla->id)
                ->where('estado', '!=', 'utilizada')
                ->whereNotNull('canastilla_numero')
                ->groupBy('canastilla_numero')
                ->selectRaw('canastilla_numero, count(*) as total')
                ->pluck('total', 'canastilla_numero');

            $nuevaCanastilla = null;
            for ($c = 1; $c <= $termo->numero_canastillas; $c++) {
                if (($ocupacion[$c] ?? 0) < $termo->capacidad_canastilla) {
                    $nuevaCanastilla = $c;
                    break;
                }
            }

            if (!$nuevaCanastilla) {
                return back()->withErrors([
                    'termo_id' => "El termo {$termo->codigo} no tiene espacio disponible.",
                ])->withInput();
            }

            $data['canastilla_numero'] = $nuevaCanastilla;
        }

        $pajilla->update($data);

        return redirect()->route('genetica.index')->with('success', 'Pajilla actualizada correctamente.');
    }

    public function destroy(Pajilla $pajilla)
    {
        $pajilla->delete();

        return redirect()->route('genetica.index')->with('success', 'Pajilla eliminada correctamente.');
    }

    /**
     * Genera los códigos de las pajillas a registrar y valida que ninguno
     * choque con uno ya existente en cualquier estado (nunca se repiten).
     *
     * Usa ValidationException en vez de abort(422, ...) porque abort()
     * no produce un error bag de Laravel: Inertia no puede mapearlo a
     * ningún campo del formulario y el usuario nunca lo ve. Con
     * ValidationException, el mensaje llega a `errors.codigo_inicial`
     * y el modal lo muestra bajo el campo correspondiente.
     */
    private function generarCodigosPajillas(string $codigoInicial, int $cantidad): array
    {
        if ($cantidad === 1) {
            if (Pajilla::where('codigo', $codigoInicial)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'codigo_inicial' => "El código {$codigoInicial} ya existe. Elige otro código.",
                ]);
            }

            return [$codigoInicial];
        }

        if (!preg_match('/^(.*?)(\d+)$/', $codigoInicial, $coincidencias)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'codigo_inicial' => 'El código inicial debe terminar en un número para registrar varias pajillas.',
            ]);
        }

        $prefijo = $coincidencias[1];
        $numeroInicial = (int) $coincidencias[2];
        $longitudNumero = strlen($coincidencias[2]);
        $codigos = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $numero = str_pad($numeroInicial + $i, $longitudNumero, '0', STR_PAD_LEFT);
            $codigo = $prefijo . $numero;

            if (Pajilla::where('codigo', $codigo)->exists()) {
                $dosisNumero = $i + 1;

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cantidad' => "El código {$codigo} (dosis #{$dosisNumero} del rango) ya existe. "
                        . "Ajusta el código inicial o reduce la cantidad de dosis.",
                ]);
            }

            $codigos[] = $codigo;
        }

        return $codigos;
    }
}