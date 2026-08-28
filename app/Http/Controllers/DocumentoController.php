<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Baja;
use App\Models\Documento;
use App\Models\EventoSalud;
use App\Models\Parto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentoController extends Controller
{
    /**
     * Modelos a los que se puede adjuntar un documento. La lista es cerrada
     * a propósito: evita que se envíe una clase arbitraria desde el formulario.
     */
    private const ADJUNTABLES = [
        'animal' => Animal::class,
        'baja' => Baja::class,
        'evento_salud' => EventoSalud::class,
        'parto' => Parto::class,
        'venta' => Venta::class,
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'documentable_tipo' => ['required', Rule::in(array_keys(self::ADJUNTABLES))],
            'documentable_id' => 'required|integer',
            'tipo' => ['required', Rule::in(array_keys(Documento::TIPOS))],
            'nombre' => 'nullable|string|max:255',
            'fecha_documento' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
            'archivo' => [
                'required',
                'file',
                'mimes:' . implode(',', Documento::EXTENSIONES),
                'max:' . Documento::TAMANO_MAXIMO_KB,
            ],
        ]);

        $modelo = self::ADJUNTABLES[$validated['documentable_tipo']];

        // El scope de tenencia hace que un registro de otra cuenta no exista
        // para esta consulta, así que no se puede adjuntar a lo ajeno.
        $registro = $modelo::find($validated['documentable_id']);

        if (! $registro) {
            return back()->withErrors([
                'documentable_id' => 'No se encontró el registro al que quieres adjuntar el documento.',
            ]);
        }

        $archivo = $request->file('archivo');

        // Disco privado: no queda expuesto por URL directa.
        $ruta = $archivo->store('documentos/' . Auth::id(), Documento::DISCO);

        Documento::create([
            'documentable_type' => $modelo,
            'documentable_id' => $registro->getKey(),
            'tipo' => $validated['tipo'],
            // `nombre` es opcional: si no se envía no viene en validated().
            'nombre' => ($validated['nombre'] ?? null) ?: $archivo->getClientOriginalName(),
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'fecha_documento' => $validated['fecha_documento'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'subido_por' => Auth::id(),
        ]);

        return back()->with('success', 'Documento adjuntado correctamente.');
    }

    /**
     * Descarga protegida: el scope de tenencia impide que una cuenta acceda a
     * los documentos de otra, y el archivo nunca se sirve por URL pública.
     */
    public function download(Documento $documento)
    {
        abort_unless(
            Storage::disk(Documento::DISCO)->exists($documento->ruta),
            404,
            'El archivo ya no está disponible.'
        );

        return Storage::disk(Documento::DISCO)->download(
            $documento->ruta,
            $documento->nombre_original ?: $documento->nombre
        );
    }

    public function destroy(Documento $documento)
    {
        Storage::disk(Documento::DISCO)->delete($documento->ruta);

        $documento->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
