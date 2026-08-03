<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Baja;
use App\Models\Costo;
use App\Models\Cria;
use App\Models\DiagnosticoGestacion;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\User;
use App\Services\AlertaOperativaService;
use App\Services\IndicadoresOvinosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3: indicadores del rebaño y alertas operativas.
 *
 * Verifica sobre todo que las fórmulas devuelvan null (no cero) cuando falta
 * el denominador, para no mostrar datos falsos.
 */
class IndicadoresOvinosTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-' . fake()->unique()->numberBetween(1000, 9999),
            'sexo' => 'F',
            'fecha_nac' => now()->subYears(2)->toDateString(),
        ], $overrides));
    }

    private function indicadores(): IndicadoresOvinosService
    {
        return app(IndicadoresOvinosService::class);
    }

    /** Crea un parto con sus crías, opcionalmente ligado a un servicio. */
    private function parto(Animal $madre, int $crias, int $vivas, ?int $servicioId = null): Parto
    {
        $evento = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'tipo_evento' => 'parto',
            'fecha' => now()->subMonth()->toDateString(),
        ]);

        $parto = Parto::create([
            'evento_id' => $evento->id,
            'servicio_evento_id' => $servicioId,
            'tipo_parto' => 'normal',
            'numero_crias' => $crias,
            'crias_vivas' => $vivas,
            'crias_muertas' => $crias - $vivas,
        ]);

        for ($i = 0; $i < $crias; $i++) {
            Cria::create([
                'parto_id' => $parto->id,
                'sexo' => $i % 2 === 0 ? 'hembra' : 'macho',
                'condicion' => $i < $vivas ? 'vivo' : 'nacido_muerto',
            ]);
        }

        return $parto;
    }

    // ─── Inventario ───────────────────────────────────────────────────────

    public function test_inventory_counts_only_active_animals(): void
    {
        $this->usuario();
        $this->animal(['sexo' => 'F']);
        $this->animal(['sexo' => 'M']);
        $salido = $this->animal(['sexo' => 'F']);

        $salido->update(['activo' => false]);

        $inv = $this->indicadores()->inventario();

        $this->assertSame(2, $inv['total_activos']);
        $this->assertSame(1, $inv['hembras']);
        $this->assertSame(1, $inv['machos']);
    }

    public function test_young_animals_do_not_count_as_breeding_stock(): void
    {
        $this->usuario();
        $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subMonths(3)->toDateString()]);
        $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subYears(2)->toDateString()]);

        $this->assertSame(1, $this->indicadores()->inventario()['borregas_reproductoras']);
    }

    public function test_animals_without_birth_date_are_reported_not_assumed(): void
    {
        $this->usuario();
        $this->animal(['fecha_nac' => null]);

        $inv = $this->indicadores()->inventario();

        $this->assertSame(1, $inv['sin_fecha_nacimiento']);
        // Sin fecha no se asume que sea reproductora.
        $this->assertSame(0, $inv['borregas_reproductoras']);
    }

    // ─── Prolificidad y fertilidad ────────────────────────────────────────

    public function test_prolificity_is_crias_over_births(): void
    {
        $this->usuario();
        $madre = $this->animal();

        $this->parto($madre, crias: 2, vivas: 2);
        $this->parto($madre, crias: 1, vivas: 1);

        // 3 crías / 2 partos = 1.5
        $this->assertSame(1.5, $this->indicadores()->reproduccion()['prolificidad']);
    }

    public function test_prolificity_is_null_without_births(): void
    {
        $this->usuario();
        $this->animal();

        $this->assertNull($this->indicadores()->reproduccion()['prolificidad']);
    }

    public function test_fertility_counts_services_that_reached_birth(): void
    {
        $this->usuario();
        $madre = $this->animal();

        // Dos servicios, solo uno terminó en parto → 50 %
        $servicio1 = EventoReproductivo::create([
            'hembra_id' => $madre->id, 'tipo_evento' => 'servicio',
            'fecha' => now()->subMonths(6)->toDateString(),
        ]);
        EventoReproductivo::create([
            'hembra_id' => $madre->id, 'tipo_evento' => 'servicio',
            'fecha' => now()->subMonths(5)->toDateString(),
        ]);

        $this->parto($madre, crias: 1, vivas: 1, servicioId: $servicio1->id);

        $this->assertSame(50.0, $this->indicadores()->reproduccion()['porcentaje_fertilidad']);
    }

    public function test_fertility_is_null_without_services(): void
    {
        $this->usuario();

        $this->assertNull($this->indicadores()->reproduccion()['porcentaje_fertilidad']);
    }

    public function test_gestation_rate_is_null_without_diagnoses(): void
    {
        $this->usuario();
        $madre = $this->animal();
        $this->parto($madre, crias: 1, vivas: 1);

        // Hay partos pero ningún diagnóstico: no se inventa un 0 %.
        $this->assertNull($this->indicadores()->reproduccion()['porcentaje_gestacion']);
    }

    public function test_gestation_rate_uses_positive_diagnoses(): void
    {
        $this->usuario();
        $madre = $this->animal();

        foreach ([['positivo'], ['positivo'], ['negativo'], ['negativo']] as [$resultado]) {
            $evento = EventoReproductivo::create([
                'hembra_id' => $madre->id, 'tipo_evento' => 'diagnostico',
                'fecha' => now()->subMonths(3)->toDateString(),
            ]);

            DiagnosticoGestacion::create([
                'evento_id' => $evento->id,
                'metodo' => 'ultrasonido',
                'resultado' => $resultado,
            ]);
        }

        // 2 positivos de 4 diagnósticos = 50 %
        $this->assertSame(50.0, $this->indicadores()->reproduccion()['porcentaje_gestacion']);
    }

    public function test_cria_survival_rate_is_calculated(): void
    {
        $this->usuario();
        $madre = $this->animal();

        $this->parto($madre, crias: 4, vivas: 3);

        $rep = $this->indicadores()->reproduccion();

        $this->assertSame(4, $rep['crias_nacidas']);
        $this->assertSame(3, $rep['crias_vivas']);
        $this->assertSame(1, $rep['crias_muertas']);
        $this->assertSame(75.0, $rep['porcentaje_supervivencia_crias']);
    }

    // ─── Desarrollo ───────────────────────────────────────────────────────

    public function test_average_daily_gain_across_the_flock(): void
    {
        $this->usuario();
        $a = $this->animal();
        $b = $this->animal();

        Pesaje::create(['animal_id' => $a->id, 'fecha' => now()->subDays(100), 'peso' => 20]);
        Pesaje::create(['animal_id' => $a->id, 'fecha' => now(), 'peso' => 40]);   // 0.2 kg/día

        Pesaje::create(['animal_id' => $b->id, 'fecha' => now()->subDays(100), 'peso' => 20]);
        Pesaje::create(['animal_id' => $b->id, 'fecha' => now(), 'peso' => 60]);   // 0.4 kg/día

        $des = $this->indicadores()->desarrollo();

        $this->assertSame(0.3, $des['ganancia_diaria_promedio']);   // promedio de 0.2 y 0.4
        $this->assertSame(50.0, $des['peso_promedio']);
        $this->assertSame(2, $des['ejemplares_con_pesaje']);
    }

    public function test_daily_gain_is_null_without_enough_weighings(): void
    {
        $this->usuario();
        $a = $this->animal();

        Pesaje::create(['animal_id' => $a->id, 'fecha' => now(), 'peso' => 30]);

        $this->assertNull($this->indicadores()->desarrollo()['ganancia_diaria_promedio']);
    }

    // ─── Económico ────────────────────────────────────────────────────────

    public function test_cost_per_animal_and_profit_percentage(): void
    {
        $user = $this->usuario();
        $this->animal();
        $this->animal();

        Costo::create([
            'concepto' => 'Alimento', 'categoria' => 'alimentacion', 'tipo_costo' => 'directo',
            'monto' => 1000, 'fecha' => now()->toDateString(), 'user_id' => $user->id,
        ]);

        $eco = $this->indicadores()->economico();

        $this->assertSame(1000.0, $eco['costos_totales']);
        $this->assertSame(500.0, $eco['costo_promedio_por_ejemplar']);   // 1000 / 2 activos
        $this->assertSame(-1000.0, $eco['utilidad']);                     // sin ingresos
        $this->assertSame(-100.0, $eco['porcentaje_utilidad']);
    }

    public function test_cost_per_animal_is_null_without_animals(): void
    {
        $this->usuario();

        $this->assertNull($this->indicadores()->economico()['costo_promedio_por_ejemplar']);
    }

    public function test_profit_percentage_is_null_without_costs(): void
    {
        $this->usuario();
        $this->animal();

        $this->assertNull($this->indicadores()->economico()['porcentaje_utilidad']);
    }

    // ─── Alertas operativas ───────────────────────────────────────────────

    public function test_overdue_health_activities_raise_a_critical_alert(): void
    {
        $this->usuario();
        $animal = $this->animal();

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subWeek()->toDateString(),
            'diagnostico' => 'Vacuna pendiente',
            'estado' => EventoSalud::ESTADO_PENDIENTE,
        ]);

        $alertas = collect(app(AlertaOperativaService::class)->todas());
        $vencidas = $alertas->firstWhere('titulo', 'Actividades sanitarias vencidas');

        $this->assertNotNull($vencidas);
        $this->assertSame(AlertaOperativaService::CRITICA, $vencidas['nivel']);
        $this->assertSame(1, $vencidas['cantidad']);
    }

    public function test_a_sold_animal_still_active_raises_an_alert(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();

        $animal->ventas()->create([
            'tipo_venta' => 'animal',
            'estado_venta' => 'completada',
            'producto' => 'Borrega',
            'cantidad' => 1,
            'unidad' => 'cabeza',
            'precio_unitario' => 4000,
            'precio_total' => 4000,
            'fecha_venta' => now()->toDateString(),
            'metodo_pago' => 'efectivo',
            'vendedor_id' => $user->id,
        ]);

        $alertas = collect(app(AlertaOperativaService::class)->todas());
        $alerta = $alertas->firstWhere('titulo', 'Ejemplares vendidos que siguen activos');

        $this->assertNotNull($alerta);
        $this->assertSame(1, $alerta['cantidad']);
        $this->assertSame('/bajas', $alerta['ruta']);
    }

    public function test_animals_without_identification_raise_an_alert(): void
    {
        $this->usuario();
        $this->animal(['arete' => '']);

        $alertas = collect(app(AlertaOperativaService::class)->todas());

        $this->assertNotNull($alertas->firstWhere('titulo', 'Ejemplares sin identificación'));
    }

    public function test_a_healthy_flock_produces_no_critical_alerts(): void
    {
        $this->usuario();

        $animal = $this->animal();
        $animal->update(['microchip_codigo' => 'CHIP-1', 'madre_id' => null]);

        $criticas = collect(app(AlertaOperativaService::class)->todas())
            ->where('nivel', AlertaOperativaService::CRITICA);

        $this->assertCount(0, $criticas);
    }

    public function test_alerts_are_ordered_by_urgency(): void
    {
        $this->usuario();
        $animal = $this->animal();

        // Una informativa (sin genealogía) y una crítica (sanitaria vencida)
        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subWeek()->toDateString(),
            'diagnostico' => 'Vencida',
            'estado' => EventoSalud::ESTADO_PENDIENTE,
        ]);

        $alertas = app(AlertaOperativaService::class)->todas();

        $this->assertSame(AlertaOperativaService::CRITICA, $alertas[0]['nivel']);
    }

    // ─── Páginas ──────────────────────────────────────────────────────────

    public function test_the_reports_page_loads(): void
    {
        $this->usuario();
        $this->animal();

        $this->get(route('reportes.ovinos'))->assertOk();
    }

    public function test_the_health_calendar_loads(): void
    {
        $this->usuario();

        $this->get(route('calendario.index'))->assertOk();
    }

    public function test_indicators_are_isolated_per_account(): void
    {
        $this->usuario();
        $this->animal();
        $this->animal();

        $this->assertSame(2, $this->indicadores()->inventario()['total_activos']);

        $this->usuario();   // otra cuenta
        $this->assertSame(0, $this->indicadores()->inventario()['total_activos']);
    }
}
