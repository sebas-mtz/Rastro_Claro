<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\AnimalValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mismo contrato que CostoDesdeSaludTest, aplicado a Tratamientos: el costo se
 * captura una sola vez y se refleja en el módulo de Costos y en la valuación,
 * sin contarse dos veces.
 */
class CostoDesdeTratamientoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-001'): Animal
    {
        return Animal::create([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    private function datosBase(Animal $animal, array $overrides = []): array
    {
        return array_merge([
            'animal_id' => $animal->id,
            'nombre' => 'Antibiótico para mastitis',
            'fecha_inicio' => now()->toDateString(),
            'responsable' => 'MVZ. García',
        ], $overrides);
    }

    public function test_registering_a_treatment_with_a_cost_creates_the_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $costo = Costo::first();

        $this->assertNotNull($costo, 'Debió crearse el costo automáticamente.');
        $this->assertSame('Antibiótico para mastitis', $costo->concepto);
        $this->assertSame('medicamentos', $costo->categoria);
        $this->assertSame(320.0, (float) $costo->monto);
        $this->assertSame($animal->id, $costo->animal_id);
        $this->assertSame('MVZ. García', $costo->proveedor);
        $this->assertSame(Tratamiento::class, $costo->origen_tipo);
        $this->assertSame(Tratamiento::first()->id, $costo->origen_id);
    }

    public function test_the_cost_is_counted_only_once_in_the_valuation(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        $this->assertSame(1, Costo::count());
        $this->assertSame(320.0, (float) Tratamiento::first()->costo);
        $this->assertSame(320.0, $calculo['buckets']['costo_sanitario']);
        $this->assertSame(320.0, $calculo['costo_total_produccion']);

        $sanitarios = collect($calculo['detalles'])->where('categoria', 'sanitario');
        $this->assertCount(1, $sanitarios, 'El gasto no debe aparecer duplicado en el desglose.');
    }

    public function test_a_treatment_without_a_cost_does_not_create_a_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Tratamiento::count());
        $this->assertSame(0, Costo::count());
    }

    /**
     * A diferencia de los eventos de salud, un tratamiento siempre exige animal:
     * no existe el caso de tratamiento aplicado solo a un lote.
     */
    public function test_a_treatment_always_requires_an_animal(): void
    {
        $user = $this->usuario();

        $lote = Lote::create([
            'nombre' => 'Lote Norte',
            'corral_potrero' => 'Norte',
            'responsable_id' => $user->id,
        ]);

        $this->post(route('tratamientos.store'), [
            'lote_id' => $lote->id,
            'nombre' => 'Desparasitación masiva',
            'fecha_inicio' => now()->toDateString(),
            'costo' => 900,
        ])->assertSessionHasErrors('animal_id');

        $this->assertSame(0, Tratamiento::count());
        $this->assertSame(0, Costo::count());
    }

    public function test_updating_the_treatment_cost_updates_the_linked_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $this->put(route('tratamientos.update', Tratamiento::first()->id), [
            'costo' => 415,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Costo::count());
        $this->assertSame(415.0, (float) Costo::first()->monto);
    }

    public function test_clearing_the_cost_removes_the_linked_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Costo::count());

        $this->put(route('tratamientos.update', Tratamiento::first()->id), [
            'costo' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count());
    }

    public function test_deleting_the_treatment_removes_its_cost(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $this->delete(route('tratamientos.destroy', Tratamiento::first()->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count(), 'No deben quedar costos huérfanos.');
    }

    public function test_a_negative_cost_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => -50]))
            ->assertSessionHasErrors('costo');

        $this->assertSame(0, Costo::count());
    }

    public function test_health_event_and_treatment_costs_add_up_without_colliding(): void
    {
        $this->usuario();
        $animal = $this->animal();

        \App\Models\EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonth()->toDateString(),
            'diagnostico' => 'Vacuna clostridiasis',
            'costo' => 95,
        ]);

        $this->post(route('tratamientos.store'), $this->datosBase($animal, ['costo' => 320]))
            ->assertSessionHasNoErrors();

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        // Cada origen aporta su propio monto, sin pisarse entre sí.
        $this->assertSame(415.0, $calculo['buckets']['costo_sanitario']);
        $this->assertCount(2, collect($calculo['detalles'])->where('categoria', 'sanitario'));
    }
}
