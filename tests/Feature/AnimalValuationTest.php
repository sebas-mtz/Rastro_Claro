<?php

namespace Tests\Feature;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationHistorial;
use App\Models\Costo;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\ServicioReproductivo;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnimalValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnimalValuationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->superAdmin()->create());
    }

    public function test_show_uses_current_animal_fields_and_preserves_saved_overrides(): void
    {
        $animal = Animal::factory()->create([
            'sexo' => 'H', 'numero_registro' => 'REG-001',
            'grado_pureza' => 'Puro', 'lectura_microchip' => 'CHIP-001',
        ]);
        $this->costo($animal, 1000);
        app(AnimalValuationService::class)->guardar($animal, [
            'porcentaje_margen_genetico' => 50,
            'estado_reproductivo_valuacion' => 'abierta',
            'plus_reproductivo' => 100,
            'ajuste_manual' => 50,
            'motivo_ajuste' => 'Ajuste documentado',
        ]);

        $this->get(route('valuaciones.show', $animal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Valuaciones/Show')
                ->where('animal.numero_registro', 'REG-001')
                ->where('animal.grado_pureza', 'Puro')
                ->where('animal.lectura_microchip', 'CHIP-001')
                ->where('calculo.precio_estimado', fn ($value) => (float) $value === 1650.0)
                ->where('calculo.ajuste_manual', fn ($value) => (float) $value === 50.0));
    }

    public function test_simulator_accepts_zero_adjustment_but_requires_a_reason_for_nonzero_adjustment(): void
    {
        $animal = Animal::factory()->create();
        $this->costo($animal, 1000);

        $payload = ['porcentaje_margen_genetico' => 50, 'plus_reproductivo' => 0,
            'ajuste_manual' => 0, 'motivo_ajuste' => ''];
        $this->postJson(route('valuaciones.simular', $animal), $payload)
            ->assertOk()->assertJsonPath('calculo.precio_estimado', 1500);
        $this->assertDatabaseCount('animal_valuations', 0);

        $this->postJson(route('valuaciones.simular', $animal), array_merge($payload, ['ajuste_manual' => 25]))
            ->assertUnprocessable()->assertJsonValidationErrors('motivo_ajuste');
        $this->post(route('valuaciones.guardar', $animal), array_merge($payload, ['estado' => null]))
            ->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->assertDatabaseHas('animal_valuations', ['animal_id' => $animal->id, 'estado' => 'activa']);
    }

    public function test_female_and_registered_sire_are_resolved_using_current_models(): void
    {
        $animal = Animal::factory()->create(['sexo' => 'H']);
        $macho = Animal::factory()->create(['sexo' => 'M', 'numero_registro' => 'REG-SEMENTAL']);
        $evento = EventoReproductivo::factory()->create(['hembra_id' => $animal->id]);
        ServicioReproductivo::create([
            'evento_id' => $evento->id, 'tipo_servicio' => 'monta_natural', 'macho_id' => $macho->id,
        ]);

        $this->assertSame('cargada_semental_registro', app(AnimalValuationService::class)->sugerirEstadoReproductivo($animal));
        $this->get(route('valuaciones.show', $animal))->assertOk();
    }

    public function test_cost_sources_are_deduplicated_and_observer_recalculates(): void
    {
        $animal = Animal::factory()->create(['sexo' => 'M']);
        $evento = EventoSalud::factory()->create(['animal_id' => $animal->id, 'costo' => 100]);
        Costo::create([
            'user_id' => auth()->id(),
            'animal_id' => $animal->id, 'concepto' => 'Consulta', 'categoria' => 'consultas_veterinarias',
            'tipo_costo' => 'directo', 'fecha' => today(), 'monto' => 100,
            'origen_tipo' => EventoSalud::class, 'origen_id' => $evento->id,
        ]);
        $valuacion = app(AnimalValuationService::class)->guardar($animal);
        $this->assertEquals(100, $valuacion->costo_sanitario);

        Alimentacion::factory()->create(['animal_id' => $animal->id, 'cantidad' => 2]);
        $this->assertEquals(110, $valuacion->fresh()->costo_total_produccion);
        $this->assertDatabaseHas('animal_valuation_historial', [
            'valuation_id' => $valuacion->id, 'tipo_movimiento' => 'nuevo_gasto', 'valor_movimiento' => 10,
        ]);
    }

    public function test_recalculation_and_sale_confirmation_write_current_columns(): void
    {
        $animal = Animal::factory()->create(['sexo' => 'M']);
        $this->costo($animal, 1000);
        $this->post(route('valuaciones.recalcular', $animal))->assertRedirect()->assertSessionDoesntHaveErrors();
        $venta = Venta::factory()->create(['vendible_id' => $animal->id]);
        $this->post(route('valuaciones.confirmar-venta', $animal), [
            'precio_real_venta' => 1800, 'fecha_venta' => today()->toDateString(), 'venta_id' => $venta->id,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('animal_valuations', [
            'animal_id' => $animal->id, 'estado' => 'confirmada', 'precio_real_venta' => 1800, 'venta_id' => $venta->id,
        ]);
        $historial = AnimalValuationHistorial::where('tipo_movimiento', 'confirmacion_venta')->firstOrFail();
        $this->assertSame(today()->toDateString(), $historial->datos_nuevos['fecha_venta']);
    }

    public function test_pdf_route_renders_the_existing_animal_schema(): void
    {
        $animal = Animal::factory()->create();
        $this->costo($animal, 1000);
        $response = $this->get(route('valuaciones.pdf', $animal))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_configuration_and_valuation_routes_keep_tenant_isolation(): void
    {
        $animal = Animal::factory()->create();
        $this->put(route('valuaciones.configuracion'), ['valores' => [
            ['clave' => 'plus_abierta', 'valor' => 200],
        ]])->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('configuracion_valuaciones', [
            'owner_id' => auth()->user()->cuentaId(), 'clave' => 'plus_abierta', 'valor' => 200,
        ]);

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get(route('valuaciones.show', $animal))->assertNotFound();
        $this->postJson(route('valuaciones.simular', $animal))->assertNotFound();
    }

    private function costo(Animal $animal, float $monto): void
    {
        Costo::create([
            'user_id' => auth()->id(),
            'animal_id' => $animal->id, 'concepto' => 'Compra', 'categoria' => 'compra_animales',
            'tipo_costo' => 'directo', 'fecha' => today(), 'monto' => $monto,
        ]);
    }
}
