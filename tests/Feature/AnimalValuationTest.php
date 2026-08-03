<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalGenetica;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationHistorial;
use App\Models\ConfiguracionValuacion;
use App\Models\Costo;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Parto;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\AnimalValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalValuationTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $atributos = []): User
    {
        $user = User::factory()->create($atributos);
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-001', array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subMonths(14)->toDateString(),
        ], $overrides));
    }

    private function costo(Animal $animal, string $categoria, float $monto, array $overrides = []): Costo
    {
        return Costo::create(array_merge([
            'concepto' => 'Gasto de prueba',
            'categoria' => $categoria,
            'tipo_costo' => 'directo',
            'monto' => $monto,
            'fecha' => now()->subMonth()->toDateString(),
            'animal_id' => $animal->id,
            'user_id' => auth()->id(),
        ], $overrides));
    }

    private function servicio(): AnimalValuationService
    {
        return app(AnimalValuationService::class);
    }

    // ─── Cálculo base ─────────────────────────────────────────────────────

    public function test_an_animal_without_expenses_is_quoted_at_zero(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $calculo = $this->servicio()->calcular($animal);

        $this->assertSame(0.0, $calculo['costo_total_produccion']);
        $this->assertSame(0.0, $calculo['precio_estimado']);
        $this->assertContains(
            'No existen costos de gestación registrados para este animal.',
            $calculo['avisos']
        );
    }

    public function test_health_costs_are_summed_from_events_and_treatments(): void
    {
        $this->usuario();
        $animal = $this->animal();

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonths(3)->toDateString(),
            'diagnostico' => 'Vacuna clostridiasis',
            'costo' => 95.00,
        ]);

        Tratamiento::create([
            'animal_id' => $animal->id,
            'nombre' => 'Desparasitación',
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'costo' => 100.00,
        ]);

        $this->costo($animal, 'medicamentos', 55.00);

        $calculo = $this->servicio()->calcular($animal);

        $this->assertSame(250.0, $calculo['buckets']['costo_sanitario']);
        $this->assertSame(250.0, $calculo['costo_total_produccion']);
    }

    public function test_a_cost_linked_to_a_health_event_is_not_counted_twice(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $evento = EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonths(3)->toDateString(),
            'diagnostico' => 'Vacuna clostridiasis',
            'costo' => 95.00,
        ]);

        // El mismo gasto capturado también en el módulo de costos, declarando su origen
        $this->costo($animal, 'vacunas', 95.00, [
            'origen_tipo' => EventoSalud::class,
            'origen_id' => $evento->id,
        ]);

        $calculo = $this->servicio()->calcular($animal);

        $this->assertSame(95.0, $calculo['buckets']['costo_sanitario']);
    }

    public function test_costs_are_split_into_their_own_buckets(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->costo($animal, 'compra_animales', 3000);
        $this->costo($animal, 'vacunas', 450);
        $this->costo($animal, 'alimentacion', 4200);
        $this->costo($animal, 'registro_genetico', 350);
        $this->costo($animal, 'mano_obra', 300);
        $this->costo($animal, 'transporte', 180);

        $calculo = $this->servicio()->calcular($animal);
        $b = $calculo['buckets'];

        $this->assertSame(3000.0, $b['costo_inicial']);
        $this->assertSame(450.0, $b['costo_sanitario']);
        $this->assertSame(4200.0, $b['costo_alimentacion']);
        $this->assertSame(350.0, $b['costo_registro']);
        $this->assertSame(300.0, $b['costo_mano_obra']);
        $this->assertSame(180.0, $b['costo_transporte']);
        $this->assertSame(8480.0, $calculo['costo_total_produccion']);
    }

    // ─── Gestación ────────────────────────────────────────────────────────

    /** Crea una cría ligada a un parto de la madre con el costo dado. */
    private function criaConGestacion(Animal $madre, Animal $cria, float $costoGestacion, int $numeroCrias = 1): void
    {
        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'tipo_evento' => 'parto',
            'fecha' => now()->subMonths(14)->toDateString(),
            'costo' => $costoGestacion,
        ]);

        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'tipo_parto' => 'normal',
            'numero_crias' => $numeroCrias,
        ]);

        Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $cria->id,
            'sexo' => 'hembra',
            'condicion' => 'vivo',
        ]);
    }

    public function test_gestation_cost_is_assigned_from_the_mother(): void
    {
        $this->usuario();
        $madre = $this->animal('MADRE-1');
        $cria = $this->animal('CRIA-1');

        $this->criaConGestacion($madre, $cria, 800.00);

        $calculo = $this->servicio()->calcular($cria);

        $this->assertSame(800.0, $calculo['buckets']['costo_gestacion']);
        $this->assertNotContains(
            'No existen costos de gestación registrados para este animal.',
            $calculo['avisos']
        );
    }

    public function test_gestation_cost_is_divided_among_multiple_offspring(): void
    {
        $this->usuario();
        $madre = $this->animal('MADRE-2');
        $cria = $this->animal('CRIA-2');

        // $1,200 de gestación repartidos entre 3 crías → $400 a esta
        $this->criaConGestacion($madre, $cria, 1200.00, numeroCrias: 3);

        $calculo = $this->servicio()->calcular($cria);

        $this->assertSame(400.0, $calculo['buckets']['costo_gestacion']);

        $detalle = collect($calculo['detalles'])->firstWhere('categoria', 'gestacion');
        $this->assertStringContainsString('3 crías', $detalle['metodo_distribucion']);
    }

    // ─── Margen y plus ────────────────────────────────────────────────────

    public function test_genetic_margin_is_applied_as_a_percentage(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 10000);

        $calculo = $this->servicio()->calcular($animal, ['porcentaje_margen_genetico' => 50]);

        $this->assertSame(5000.0, $calculo['valor_margen_genetico']);
        $this->assertSame(15000.0, $calculo['precio_estimado']);
    }

    public function test_margin_falls_back_to_the_genetic_record(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 1000);

        AnimalGenetica::create([
            'owner_id' => $user->id,
            'animal_id' => $animal->id,
            'porcentaje_margen_genetico' => 25,
        ]);

        $calculo = $this->servicio()->calcular($animal->fresh());

        $this->assertSame(25.0, $calculo['porcentaje_margen_genetico']);
        $this->assertSame(250.0, $calculo['valor_margen_genetico']);
    }

    public function test_reproductive_plus_comes_from_configuration(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();

        ConfiguracionValuacion::create([
            'owner_id' => $user->id,
            'clave' => 'plus_cargada_semental_registro',
            'valor' => 6000,
        ]);

        $calculo = $this->servicio()->calcular($animal, [
            'estado_reproductivo_valuacion' => 'cargada_semental_registro',
        ]);

        $this->assertSame(6000.0, $calculo['plus_reproductivo']);
        $this->assertSame(6000.0, $calculo['precio_estimado']);
    }

    // ─── El ejemplo que debe funcionar ────────────────────────────────────

    public function test_the_dorper_example_produces_seventeen_thousand(): void
    {
        $this->usuario();
        $animal = $this->animal('DORPER-1');

        $this->costo($animal, 'compra_animales', 3000);      // nacimiento
        $this->costo($animal, 'vacunas', 450);               // vacunas y desparasitantes
        $this->costo($animal, 'alimentacion', 4200);         // alimentación
        $this->costo($animal, 'registro_genetico', 350);     // registro de pureza

        $calculo = $this->servicio()->calcular($animal, [
            'porcentaje_margen_genetico' => 50,
            'estado_reproductivo_valuacion' => 'cargada_semental_registro',
            'plus_reproductivo' => 5000,
        ]);

        $this->assertSame(8000.0, $calculo['costo_total_produccion']);
        $this->assertSame(4000.0, $calculo['valor_margen_genetico']);
        $this->assertSame(17000.0, $calculo['precio_estimado']);
    }

    // ─── Persistencia e historial ─────────────────────────────────────────

    public function test_saving_creates_the_valuation_its_details_and_a_history_entry(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'vacunas', 450);

        $valuacion = $this->servicio()->guardar($animal);

        $this->assertSame(450.0, (float) $valuacion->costo_total_produccion);
        $this->assertSame(1, $valuacion->detalles()->count());

        $movimiento = AnimalValuationHistorial::where('animal_id', $animal->id)->first();
        $this->assertSame(AnimalValuationHistorial::TIPO_CREACION, $movimiento->tipo_movimiento);
        $this->assertNull($movimiento->precio_anterior);
        $this->assertSame(450.0, (float) $movimiento->precio_nuevo);
    }

    public function test_a_new_expense_recalculates_and_records_a_history_movement(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'vacunas', 450);

        $this->servicio()->guardar($animal);
        $this->assertSame(1, AnimalValuationHistorial::where('animal_id', $animal->id)->count());

        // El observer recalcula al registrar un gasto nuevo
        $this->costo($animal, 'medicamentos', 350, ['concepto' => 'Vacuna y desparasitación']);

        $valuacion = $animal->valuaciones()->first();
        $this->assertSame(800.0, (float) $valuacion->costo_total_produccion);

        $ultimo = AnimalValuationHistorial::where('animal_id', $animal->id)
            ->orderByDesc('id')->first();

        $this->assertSame(AnimalValuationHistorial::TIPO_NUEVO_GASTO, $ultimo->tipo_movimiento);
        $this->assertSame(450.0, (float) $ultimo->precio_anterior);
        $this->assertSame(800.0, (float) $ultimo->precio_nuevo);
        $this->assertSame(350.0, (float) $ultimo->diferencia);
    }

    public function test_changing_the_margin_is_recorded_in_the_history(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 1000);

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => 0,
        ])->assertSessionHasNoErrors();

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => 50,
        ])->assertSessionHasNoErrors();

        $ultimo = AnimalValuationHistorial::where('animal_id', $animal->id)
            ->orderByDesc('id')->first();

        $this->assertSame(AnimalValuationHistorial::TIPO_CAMBIO_MARGEN, $ultimo->tipo_movimiento);
        $this->assertSame(1000.0, (float) $ultimo->precio_anterior);
        $this->assertSame(1500.0, (float) $ultimo->precio_nuevo);
    }

    public function test_the_previous_price_is_never_overwritten(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'vacunas', 100);

        $this->servicio()->guardar($animal);
        $this->costo($animal, 'vacunas', 200, ['concepto' => 'Segunda vacuna']);
        $this->costo($animal, 'vacunas', 300, ['concepto' => 'Tercera vacuna']);

        $movimientos = AnimalValuationHistorial::where('animal_id', $animal->id)
            ->orderBy('id')->get();

        $this->assertCount(3, $movimientos);
        $this->assertSame([100.0, 300.0, 600.0], $movimientos->pluck('precio_nuevo')->map(fn ($p) => (float) $p)->all());
    }

    // ─── Ajuste manual y permisos ─────────────────────────────────────────

    public function test_a_manual_adjustment_requires_a_written_reason(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('valuaciones.guardar', $animal->id), [
            'ajuste_manual' => 500,
        ])->assertSessionHasErrors('motivo_ajuste');

        $this->assertSame(0, AnimalValuation::count());
    }

    public function test_a_manual_adjustment_with_a_reason_is_accepted(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 1000);

        $this->post(route('valuaciones.guardar', $animal->id), [
            'ajuste_manual' => 500,
            'motivo_ajuste' => 'Ejemplar premiado en exposición regional.',
        ])->assertSessionHasNoErrors();

        $valuacion = AnimalValuation::first();
        $this->assertSame(500.0, (float) $valuacion->ajuste_manual);
        $this->assertSame(1500.0, (float) $valuacion->precio_estimado);
    }

    public function test_a_worker_cannot_apply_a_margin_over_one_hundred_percent(): void
    {
        $this->usuario(['role' => User::ROLE_TRABAJADOR]);
        $animal = $this->animal();

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => 150,
            'motivo_ajuste' => 'Línea genética excepcional.',
        ])->assertForbidden();

        $this->assertSame(0, AnimalValuation::count());
    }

    /**
     * Un margen por encima del 100 % altera de forma sustancial el precio, así
     * que dejó de bastar el rol de administrador: es una modificación crítica
     * y la autoriza el superadministrador.
     */
    public function test_an_admin_cannot_apply_a_margin_over_one_hundred_percent(): void
    {
        $this->usuario(['role' => User::ROLE_ADMIN]);
        $animal = $this->animal();

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => 150,
            'motivo_ajuste' => 'Línea genética excepcional.',
        ])->assertForbidden();

        $this->assertSame(0, AnimalValuation::count());
    }

    public function test_a_super_admin_can_apply_a_margin_over_one_hundred_percent_with_a_reason(): void
    {
        $this->usuario(['role' => User::ROLE_SUPER_ADMIN]);
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 1000);

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => 150,
            'motivo_ajuste' => 'Línea genética excepcional certificada.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2500.0, (float) AnimalValuation::first()->precio_estimado);
    }

    // ─── Venta, utilidad y bordes ─────────────────────────────────────────

    public function test_confirming_the_sale_records_profit(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 8000);

        $this->servicio()->guardar($animal);

        $this->post(route('valuaciones.confirmar-venta', $animal->id), [
            'precio_real_venta' => 17000,
            'fecha_venta' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $valuacion = AnimalValuation::first();

        $this->assertSame(17000.0, (float) $valuacion->precio_real_venta);
        $this->assertSame(9000.0, $valuacion->utilidad);
        $this->assertSame(112.5, $valuacion->porcentaje_utilidad);
        $this->assertSame(AnimalValuation::ESTADO_CONFIRMADA, $valuacion->estado);
    }

    public function test_a_sale_below_cost_is_reported_as_a_loss(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'compra_animales', 10000);

        $this->servicio()->guardar($animal);

        $this->post(route('valuaciones.confirmar-venta', $animal->id), [
            'precio_real_venta' => 7500,
            'fecha_venta' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(-2500.0, AnimalValuation::first()->utilidad);
    }

    public function test_profit_percentage_is_null_when_production_cost_is_zero(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->servicio()->guardar($animal);

        $this->post(route('valuaciones.confirmar-venta', $animal->id), [
            'precio_real_venta' => 5000,
            'fecha_venta' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $valuacion = AnimalValuation::first();

        $this->assertSame(5000.0, $valuacion->utilidad);
        $this->assertNull($valuacion->porcentaje_utilidad);   // no divide entre cero
    }

    public function test_the_sale_date_cannot_precede_the_birth_date(): void
    {
        $this->usuario();
        $animal = $this->animal('OV-FECHA', ['fecha_nac' => now()->subYear()->toDateString()]);
        $this->servicio()->guardar($animal);

        $this->post(route('valuaciones.confirmar-venta', $animal->id), [
            'precio_real_venta' => 5000,
            'fecha_venta' => now()->subYears(3)->toDateString(),
        ])->assertSessionHasErrors('fecha_venta');
    }

    public function test_confirming_a_sale_without_a_saved_valuation_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('valuaciones.confirmar-venta', $animal->id), [
            'precio_real_venta' => 5000,
            'fecha_venta' => now()->toDateString(),
        ])->assertSessionHasErrors('precio_real_venta');
    }

    public function test_negative_plus_and_out_of_range_margin_are_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('valuaciones.guardar', $animal->id), [
            'plus_reproductivo' => -100,
        ])->assertSessionHasErrors('plus_reproductivo');

        $this->post(route('valuaciones.guardar', $animal->id), [
            'porcentaje_margen_genetico' => -5,
        ])->assertSessionHasErrors('porcentaje_margen_genetico');
    }

    public function test_valuations_are_isolated_per_account(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $this->costo($animal, 'vacunas', 500);
        $this->servicio()->guardar($animal);

        $this->assertSame(1, AnimalValuation::count());

        // Otra cuenta no ve ni la cotización ni el animal
        $this->usuario();
        $this->assertSame(0, AnimalValuation::count());
        $this->get(route('valuaciones.show', $animal->id))->assertNotFound();
    }

    public function test_the_reproductive_state_is_suggested_for_a_young_female(): void
    {
        $this->usuario();
        $animal = $this->animal('OV-JOVEN', ['fecha_nac' => now()->subMonths(4)->toDateString()]);

        $this->assertSame(
            'joven_sin_edad_reproductiva',
            $this->servicio()->sugerirEstadoReproductivo($animal)
        );
    }

    public function test_null_values_do_not_break_the_calculation(): void
    {
        $this->usuario();
        $animal = $this->animal('OV-NULL', ['fecha_nac' => null, 'raza' => null]);

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'consulta',
            'fecha_programada' => now()->subMonth()->toDateString(),
            'diagnostico' => 'Revisión general',
            'costo' => null,   // sin costo capturado
        ]);

        $calculo = $this->servicio()->calcular($animal);

        $this->assertSame(0.0, $calculo['costo_total_produccion']);
        $this->assertSame(0.0, $calculo['precio_estimado']);
    }
}
