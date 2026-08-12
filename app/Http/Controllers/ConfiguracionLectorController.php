<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionLector;
use App\Support\NormalizadorLectura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Ajustes del lector de aretes del rancho.
 *
 * Quién puede editarlos: el dueño de la cuenta, o el superadministrador.
 *
 * Es una excepción deliberada a la regla de que la configuración es cosa del
 * superadministrador. Esa regla protege lo que afecta a todo el sistema o al
 * dinero —fórmulas, márgenes, catálogos críticos—; esto es la puesta a punto
 * del equipo físico de un rancho concreto. Si hiciera falta el
 * superadministrador, cada cliente con un lector poco común dependería de
 * soporte para algo que puede resolver en dos minutos, que es justamente lo
 * que esta pantalla viene a evitar.
 */
class ConfiguracionLectorController extends Controller
{
    public function show(Request $request)
    {
        $this->autorizar();

        $config = ConfiguracionLector::delRancho();

        return Inertia::render('Herramientas/ConfiguracionLector', [
            'configuracion' => $config,
            'conexiones' => ConfiguracionLector::CONEXIONES,
            'baudRates' => ConfiguracionLector::BAUD_RATES,
        ]);
    }

    public function update(Request $request)
    {
        $this->autorizar();

        $validated = $request->validate([
            'prefijo_descartar' => 'nullable|string|max:20',
            'sufijo_descartar' => 'nullable|string|max:20',
            'solo_digitos' => 'boolean',
            // 0 significa "sin longitud fija"; se guarda como vacío.
            'longitud_esperada' => 'nullable|integer|min:0|max:64',
            'tipo_conexion' => ['required', Rule::in(array_keys(ConfiguracionLector::CONEXIONES))],
            'baud_rate' => ['required', Rule::in(ConfiguracionLector::BAUD_RATES)],
            'modelo_lector' => 'nullable|string|max:120',
            'notas' => 'nullable|string|max:2000',
        ], [
            'baud_rate.in' => 'Esa velocidad no es una de las habituales en lectores de arete.',
        ]);

        $validated['longitud_esperada'] = ($validated['longitud_esperada'] ?? 0) > 0
            ? $validated['longitud_esperada']
            : null;

        $validated['solo_digitos'] = $request->boolean('solo_digitos');

        ConfiguracionLector::delRancho()->update($validated);

        return back()->with('success', 'Ajustes del lector guardados.');
    }

    /**
     * Prueba una lectura contra los ajustes SIN guardarlos.
     *
     * Permite ver el efecto de un prefijo o un sufijo antes de aplicarlo, que
     * es lo que evita dejar la configuración en un estado que rompa la lectura
     * de todo el rancho.
     */
    public function probar(Request $request)
    {
        $this->autorizar();

        $validated = $request->validate([
            'lectura' => 'required|string|max:255',
            'prefijo_descartar' => 'nullable|string|max:20',
            'sufijo_descartar' => 'nullable|string|max:20',
            'solo_digitos' => 'boolean',
            'longitud_esperada' => 'nullable|integer|min:0|max:64',
        ]);

        // Configuración de prueba, en memoria: nada toca la base de datos.
        $simulada = new ConfiguracionLector([
            'prefijo_descartar' => $validated['prefijo_descartar'] ?? null,
            'sufijo_descartar' => $validated['sufijo_descartar'] ?? null,
            'solo_digitos' => $request->boolean('solo_digitos'),
            'longitud_esperada' => ($validated['longitud_esperada'] ?? 0) > 0
                ? $validated['longitud_esperada']
                : null,
        ]);

        $normalizador = new NormalizadorLectura($simulada);
        $resultado = $normalizador->explicar($validated['lectura']);

        $esperada = $normalizador->longitudEsperada();

        return response()->json(array_merge($resultado, [
            'longitud_esperada' => $esperada,
            'coincide' => $resultado['longitud'] === $esperada,
            'iso' => \App\Support\CodigoIso11784::desde($resultado['normalizado'])?->aArreglo(),
        ]));
    }

    /**
     * El dueño configura su propio equipo; el superadministrador puede entrar
     * a cualquiera para dar soporte.
     */
    private function autorizar(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && ($user->esDuenoDeCuenta() || $user->isSuperAdmin()),
            403,
            'Solo el dueño del rancho puede ajustar la configuración del lector.'
        );
    }
}
