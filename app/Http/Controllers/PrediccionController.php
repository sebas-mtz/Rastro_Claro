<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * PrediccionController
 *
 * La verificación de plan se hace en la RUTA (middleware 'plan:premium').
 * Este controlador asume que el usuario ya tiene acceso.
 */
class PrediccionController extends Controller
{
    public function index(Request $request)
    {
        $hoy    = Carbon::today();
        $hace30 = $hoy->copy()->subDays(30);
        $hace60 = $hoy->copy()->subDays(60);

        $prodUltimos30 = Produccion::whereBetween('fecha', [$hace30, $hoy])
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $prodPrevios30 = Produccion::whereBetween('fecha', [$hace60, $hace30])
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $tipos = ['leche', 'huevo', 'carne'];
        $predicciones = [];

        foreach ($tipos as $tipo) {
            $total30   = (float) ($prodUltimos30->get($tipo)->total ?? 0);
            $totalPrev = (float) ($prodPrevios30->get($tipo)->total ?? 0);

            $promedioDiario = $total30 / 30;
            $proyeccionMes  = $promedioDiario * 30;

            $cambio    = null;
            $tendencia = 'estable';

            if ($totalPrev > 0) {
                $cambio = (($total30 - $totalPrev) / $totalPrev) * 100;
                $tendencia = match (true) {
                    $cambio > 5  => 'subiendo',
                    $cambio < -5 => 'bajando',
                    default      => 'estable',
                };
            }

            $predicciones[$tipo] = [
                'total_30_dias'     => round($total30, 2),
                'promedio_diario'   => round($promedioDiario, 2),
                'proyeccion_30dias' => round($proyeccionMes, 2),
                'cambio_porcentaje' => $cambio !== null ? round($cambio, 1) : null,
                'tendencia'         => $tendencia,
            ];
        }

        $analisisIa = $this->obtenerAnalisisIa($predicciones, $tipos);

        return Inertia::render('Predicciones/Index', [
            'predicciones' => $predicciones,
            'hoy'          => $hoy->toDateString(),
            'analisisIa'   => $analisisIa,
        ]);
    }

    private function obtenerAnalisisIa(array $predicciones, array $tipos): string
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return 'El análisis con IA no está configurado (falta OPENAI_API_KEY).';
        }

        $contexto = "Datos del rastro para predicciones (últimos 30 días):\n\nProducción:\n";

        foreach ($tipos as $tipo) {
            $p = $predicciones[$tipo] ?? null;
            if (!$p) continue;

            $contexto .= "- {$tipo}: total {$p['total_30_dias']}, "
                . "promedio diario {$p['promedio_diario']}, "
                . "proyección 30 días {$p['proyeccion_30dias']}";

            if ($p['cambio_porcentaje'] !== null) {
                $contexto .= ", cambio vs mes anterior: {$p['cambio_porcentaje']}%, tendencia: {$p['tendencia']}";
            }

            $contexto .= "\n";
        }

        $contexto .= "\nCon base en estos datos, genera un análisis en lenguaje sencillo para un productor ganadero.";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'temperature' => 0.5,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Eres un asesor ganadero experto en producción y planificación. Responde en español con consejos claros y prácticos.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $contexto,
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI respondió con error', ['status' => $response->status()]);
                return 'No se pudo obtener el análisis de la IA. Intenta más tarde.';
            }

            return $response->json('choices.0.message.content')
                ?? 'La IA no devolvió contenido utilizable.';
        } catch (\Throwable $e) {
            Log::error('Error llamando a OpenAI', ['error' => $e->getMessage()]);
            return 'Error al conectar con el servicio de IA. Intenta más tarde.';
        }
    }
}
