<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\EventoReproductivo;
use App\Models\ServicioReproductivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioReproductivoController extends Controller
{
    // POST /reproduccion/servicios
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'hembra_id'       => 'required|exists:animals,id',
            'lote_id'         => 'nullable|exists:lotes,id',
            'fecha'           => 'required|date|before_or_equal:today',
            'tipo_servicio'   => 'required|in:monta_natural,inseminacion_artificial,iatf',
            'macho_id'        => 'nullable|exists:animals,id',
            'pajilla_codigo'  => 'nullable|string|max:100',
            'pajilla_raza'    => 'nullable|string|max:80',
            'pajilla_origen'  => 'nullable|string|max:100',
            'tecnico_id'      => 'nullable|exists:users,id',
            'tecnico_externo' => 'nullable|string|max:100',
            'numero_servicio' => 'nullable|integer|min:1|max:10',
            'costo'           => 'nullable|numeric|min:0',
            'observaciones'   => 'nullable|string',
        ]);

        // Validar que el animal sea hembra
        $hembra = Animal::findOrFail($datos['hembra_id']);
        if (!in_array(strtolower($hembra->sexo), ['hembra', 'f', 'female'])) {
            return redirect()->back()
                ->withErrors(['hembra_id' => 'El animal seleccionado no es hembra'])
                ->withInput();
        }

        // Monta natural requiere toro
        if ($datos['tipo_servicio'] === 'monta_natural' && empty($datos['macho_id'])) {
            return redirect()->back()
                ->withErrors(['macho_id' => 'La monta natural requiere seleccionar un toro'])
                ->withInput();
        }

        // IA requiere código de pajilla
        if (in_array($datos['tipo_servicio'], ['inseminacion_artificial', 'iatf']) && empty($datos['pajilla_codigo'])) {
            return redirect()->back()
                ->withErrors(['pajilla_codigo' => 'La inseminación artificial requiere el código de pajilla'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $evento = EventoReproductivo::create([
                'hembra_id'     => $datos['hembra_id'],
                'lote_id'       => $datos['lote_id'] ?? null,
                'user_id' => null,
                'tipo_evento'   => 'servicio',
                'fecha'         => $datos['fecha'],
                'costo'         => $datos['costo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            ServicioReproductivo::create([
                'evento_id'        => $evento->id,
                'macho_id'        => $datos['macho_id'] ?? null,
                'tipo_servicio'   => $datos['tipo_servicio'],
                'pajilla_codigo'  => $datos['pajilla_codigo'] ?? null,
                'pajilla_raza'    => $datos['pajilla_raza'] ?? null,
                'pajilla_origen'  => $datos['pajilla_origen'] ?? null,
                'tecnico_id'      => $datos['tecnico_id'] ?? null,
                'tecnico_externo' => $datos['tecnico_externo'] ?? null,
                'numero_servicio' => $datos['numero_servicio'] ?? 1,
            ]);

            DB::commit();

            return redirect()->route('reproduccion.index')
                ->with('success', 'Servicio registrado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar el servicio: ' . $e->getMessage())
                ->withInput();
        }
    }
}