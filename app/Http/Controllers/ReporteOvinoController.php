<?php

namespace App\Http\Controllers;

use App\Services\AlertaOperativaService;
use App\Services\EtapaVidaService;
use App\Services\IndicadoresOvinosService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReporteOvinoController extends Controller
{
    public function __construct(
        protected IndicadoresOvinosService $indicadores,
        protected AlertaOperativaService $alertas,
    ) {
    }

    public function index(Request $request)
    {
        $desde = $request->desde;
        $hasta = $request->hasta;

        return Inertia::render('Reportes/Ovinos', [
            'resumen' => $this->indicadores->resumen($desde, $hasta),
            'costosPorLote' => $this->indicadores->costosPorLote($desde, $hasta),
            'alertas' => $this->alertas->todas(),
            'etapas' => EtapaVidaService::ETIQUETAS,
            'filtros' => ['desde' => $desde, 'hasta' => $hasta],
            // Las fórmulas se documentan en la propia pantalla para que el
            // usuario sepa cómo se calculó cada indicador.
            'formulas' => [
                'prolificidad' => 'Crías nacidas ÷ partos ocurridos',
                'porcentaje_fertilidad' => '(Servicios que llegaron a parto ÷ servicios registrados) × 100',
                'porcentaje_gestacion' => '(Diagnósticos positivos ÷ diagnósticos realizados) × 100',
                'porcentaje_supervivencia_crias' => '(Crías vivas ÷ crías nacidas) × 100',
                'porcentaje_mortalidad' => '(Fallecimientos ÷ rebaño histórico) × 100',
                'ganancia_diaria_promedio' => '(Peso final − peso inicial) ÷ días transcurridos, promediado entre ejemplares',
                'costo_promedio_por_ejemplar' => 'Costos totales ÷ ejemplares activos',
                'porcentaje_utilidad' => '((Ingresos − costos) ÷ costos) × 100',
            ],
        ]);
    }
}
