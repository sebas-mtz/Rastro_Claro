<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Produccion;      // ajusta si tu modelo se llama distinto
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class PrediccionController extends Controller
{
    public function index()
    {
        $hoy    = Carbon::today();
        $hace30 = $hoy->copy()->subDays(30);
        $hace60 = $hoy->copy()->subDays(60);

        // ============================
        // 1) PRODUCCIÓN
        // ============================

        $prodUltimos30 = Produccion::whereBetween('fecha', [$hace30, $hoy])
            ->selectRaw('tipo, SUM(cantidad) as total')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $prodPrevios30 = Produccion::whereBetween('fecha', [$hace60, $hace30])
            ->selectRaw('tipo, SUM(cantidad) as total')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $tipos = ['leche', 'huevo', 'carne'];

        $predicciones = [];

        foreach ($tipos as $tipo) {
            $total30   = $prodUltimos30->get($tipo)->total ?? 0;
            $totalPrev = $prodPrevios30->get($tipo)->total ?? 0;

            $promedioDiario = $total30 / 30;
            $proyeccionMes  = $promedioDiario * 30;

            $cambio    = null;
            $tendencia = 'estable';

            if ($totalPrev > 0) {
                $cambio = (($total30 - $totalPrev) / $totalPrev) * 100;

                if ($cambio > 5) {
                    $tendencia = 'subiendo';
                } elseif ($cambio < -5) {
                    $tendencia = 'bajando';
                }
            }

            $predicciones[$tipo] = [
                'total_30_dias'     => round($total30, 2),
                'promedio_diario'   => round($promedioDiario, 2),
                'proyeccion_30dias' => round($proyeccionMes, 2),
                'cambio_porcentaje' => $cambio ? round($cambio, 1) : null,
                'tendencia'         => $tendencia,
            ];
        }

        // ============================
        // 2) ARMAR CONTEXTO PARA LA IA
        // ============================

        $contexto = "Datos del rastro para predicciones (últimos 30 días):\n\n";

        $contexto .= "Producción:\n";
        foreach ($tipos as $tipo) {
            $p = $predicciones[$tipo] ?? null;
            if ($p) {
                $contexto .= "- {$tipo}: total {$p['total_30_dias']}, promedio diario {$p['promedio_diario']}, proyección 30 días {$p['proyeccion_30dias']}";
                if (!is_null($p['cambio_porcentaje'])) {
                    $contexto .= ", cambio vs mes anterior: {$p['cambio_porcentaje']}%, tendencia: {$p['tendencia']}";
                }
                $contexto .= "\n";
            }
        }

        $contexto .= "\nCon base en estos datos, genera un análisis en lenguaje sencillo para un productor ganadero.";

        // ============================
        // 3) LLAMAR A LA IA (OpenAI)
        // ============================

        $analisisIa = null;
        $apiKey = config('services.openai.key'); // viene de .env -> OPENAI_API_KEY

        if (!$apiKey) {
            $analisisIa = "La IA no está configurada (falta OPENAI_API_KEY).";
        } else {
            try {
                $response = Http::withToken($apiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini', // o el modelo que quieras usar
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Eres un asesor ganadero experto en producción y planificación. Responde en español con consejos claros y prácticos.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $contexto,
                            ],
                        ],
                        'temperature' => 0.5,
                    ]);

                if ($response->failed()) {
                    $analisisIa = "No se pudo obtener el análisis de la IA. Intenta más tarde.";
                } else {
                    $json = $response->json();
                    $analisisIa = $json['choices'][0]['message']['content'] ?? 'La IA no devolvió contenido utilizable.';
                }
            } catch (\Throwable $e) {
                $analisisIa = "Error al llamar a la IA: " . $e->getMessage();
            }
        }

        // ============================
        // 4) ENVIAR A LA VISTA
        // ============================

        return Inertia::render('Predicciones/Index', [
            'predicciones' => $predicciones,
            'hoy'          => $hoy->toDateString(),
            'analisisIa'   => $analisisIa,
        ]);
    }
}
