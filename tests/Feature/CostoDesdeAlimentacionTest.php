<?php

namespace Tests\Feature;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Costo;
use App\Models\InventarioInsumo;
use App\Models\Lote;
use App\Models\Racion;
use App\Models\User;
use App\Services\AnimalValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En alimentación el monto no se captura a mano: se deriva del precio por kg
 * de la ración. Estas pruebas verifican que ese costo derivado llegue al módulo
 * de Costos y a la valuación una sola vez.
 */
class CostoDesdeAlimentacionTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-001', ?int $loteId = null): Animal
    {
        return Animal::create([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
            'lote_id' => $loteId,
        ]);
    }

    /** Ración con precio conocido y un insumo con existencias suficientes. */
    private function racion(float $precioKg = 10.0): Racion
    {
        $racion = Racion::create([
            'nombre' => 'Ración de crecimiento',
            'precio_kg' => $precioKg,
            'activo' => true,
        ]);

        $insumo = InventarioInsumo::create([
            'nombre' => 'Maíz molido',
            'tipo' => 'grano',
            'existencias' => 10000,
            'unidad' => 'kg',
            'costo_promedio' => $precioKg,
            'activo' => true,
        ]);

        $racion->insumos()->attach($insumo->id, ['cantidad' => 1]);

        return $racion->fresh('insumos');
    }

    private function lote(User $user): Lote
    {
        return Lote::create([
            'nombre' => 'Lote Norte',
            'corral_potrero' => 'Norte',
            'responsable_id' => $user->id,
        ]);
    }

    public function test_registering_a_feeding_creates_the_derived_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $racion = $this->racion(10.0);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        $costo = Costo::first();

        $this->assertNotNull($costo, 'Debió crearse el costo automáticamente.');
        $this->assertSame('alimentacion', $costo->categoria);
        $this->assertSame(250.0, (float) $costo->monto, '25 kg × $10.00 = $250.00');
        $this->assertSame($animal->id, $costo->animal_id);
        $this->assertSame(Alimentacion::class, $costo->origen_tipo);
        $this->assertSame(Alimentacion::first()->id, $costo->origen_id);
    }

    public function test_the_feeding_cost_is_counted_only_once_in_the_valuation(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $racion = $this->racion(10.0);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        // El consumo existe en `alimentacions` y en `costos`, pero suma una vez.
        $this->assertSame(1, Alimentacion::count());
        $this->assertSame(1, Costo::count());
        $this->assertSame(250.0, $calculo['buckets']['costo_alimentacion']);
        $this->assertSame(250.0, $calculo['costo_total_produccion']);

        $lineas = collect($calculo['detalles'])->where('categoria', 'alimentacion');
        $this->assertCount(1, $lineas, 'El consumo no debe aparecer duplicado en el desglose.');
    }

    public function test_a_lot_feeding_is_prorated_and_not_double_counted(): void
    {
        $user = $this->usuario();
        $lote = $this->lote($user);
        $racion = $this->racion(10.0);

        $primero = $this->animal('OV-001', $lote->id);
        $this->animal('OV-002', $lote->id);   // el lote tiene 2 animales

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'lote_id' => $lote->id,
            'racion_id' => $racion->id,
            'cantidad' => 40,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        // El costo del lote se registra sin animal concreto.
        $costo = Costo::first();
        $this->assertNotNull($costo);
        $this->assertNull($costo->animal_id);
        $this->assertSame($lote->id, $costo->lote_id);
        $this->assertSame(400.0, (float) $costo->monto);

        // A cada animal le toca la mitad, una sola vez.
        $calculo = app(AnimalValuationService::class)->calcular($primero);

        $this->assertSame(200.0, $calculo['buckets']['costo_alimentacion']);

        $lineas = collect($calculo['detalles'])->where('categoria', 'alimentacion');
        $this->assertCount(1, $lineas);
        $this->assertStringContainsString('2 animales', $lineas->first()['metodo_distribucion']);
    }

    public function test_no_cost_row_when_the_ration_price_is_unknown(): void
    {
        $this->usuario();
        $animal = $this->animal();

        // Ración sin precio y sin insumos: no hay forma de saber cuánto costó.
        $racion = Racion::create(['nombre' => 'Ración sin precio', 'activo' => true]);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Alimentacion::count());
        $this->assertSame(0, Costo::count(), 'Sin precio conocido no se inventa un monto.');

        // La valuación lo dice explícitamente en vez de sumar cero en silencio.
        $calculo = app(AnimalValuationService::class)->calcular($animal);

        $this->assertSame(0.0, $calculo['buckets']['costo_alimentacion']);
        $this->assertTrue(
            collect($calculo['avisos'])->contains(fn ($a) => str_contains($a, 'no tienen precio conocido'))
        );
    }

    public function test_deleting_the_feeding_removes_its_cost(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $racion = $this->racion(10.0);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Costo::count());

        $this->delete(route('alimentaciones.destroy', Alimentacion::first()->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count(), 'No deben quedar costos huérfanos.');
    }

    public function test_feeding_health_and_treatment_costs_add_up_in_their_own_buckets(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $racion = $this->racion(10.0);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        \App\Models\EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Vacuna clostridiasis',
            'costo' => 95,
        ]);

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        $this->assertSame(250.0, $calculo['buckets']['costo_alimentacion']);
        $this->assertSame(95.0, $calculo['buckets']['costo_sanitario']);
        $this->assertSame(345.0, $calculo['costo_total_produccion']);
    }

    /**
     * Un costo capturado a mano en el módulo de Costos, sin origen, es un gasto
     * independiente y debe sumarse aparte de los consumos derivados.
     */
    public function test_a_manual_feeding_cost_adds_to_the_derived_ones(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();
        $racion = $this->racion(10.0);

        $this->post(route('alimentacion.store'), [
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'racion_id' => $racion->id,
            'cantidad' => 25,
            'unidad' => 'kg',
        ])->assertSessionHasNoErrors();

        Costo::create([
            'concepto' => 'Compra de forraje suelto',
            'categoria' => 'alimentacion',
            'tipo_costo' => 'directo',
            'monto' => 500,
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
            'user_id' => $user->id,
        ]);

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        $this->assertSame(750.0, $calculo['buckets']['costo_alimentacion']);
        $this->assertCount(2, collect($calculo['detalles'])->where('categoria', 'alimentacion'));
    }
}
