<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\EventoSalud;
use App\Models\User;
use App\Models\Vacuna;
use App\Services\AnimalValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El costo capturado en el módulo de Salud debe reflejarse una sola vez:
 * aparece en el módulo de Costos y suma a la valuación del animal, sin que
 * el usuario tenga que registrarlo dos veces ni contarse doble.
 */
class CostoDesdeSaludTest extends TestCase
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

    public function test_registering_a_vaccination_with_a_cost_creates_the_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $vacuna = Vacuna::create(['nombre' => 'Clostridiasis']);

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => $vacuna->id,
            'dosis' => '1 dosis',
            'costo' => 95,
        ])->assertSessionHasNoErrors();

        $costo = Costo::first();

        $this->assertNotNull($costo, 'Debió crearse el costo automáticamente.');
        $this->assertSame('Clostridiasis', $costo->concepto);
        $this->assertSame('vacunas', $costo->categoria);
        $this->assertSame(95.0, (float) $costo->monto);
        $this->assertSame($animal->id, $costo->animal_id);
        $this->assertSame(EventoSalud::class, $costo->origen_tipo);
        $this->assertSame(EventoSalud::first()->id, $costo->origen_id);
    }

    public function test_the_cost_is_counted_only_once_in_the_valuation(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $vacuna = Vacuna::create(['nombre' => 'Clostridiasis']);

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => $vacuna->id,
            'costo' => 95,
        ])->assertSessionHasNoErrors();

        $calculo = app(AnimalValuationService::class)->calcular($animal);

        // Existe en las dos tablas, pero suma una sola vez.
        $this->assertSame(1, Costo::count());
        $this->assertSame(95.0, (float) EventoSalud::first()->costo);
        $this->assertSame(95.0, $calculo['buckets']['costo_sanitario']);
        $this->assertSame(95.0, $calculo['costo_total_produccion']);

        $sanitarios = collect($calculo['detalles'])->where('categoria', 'sanitario');
        $this->assertCount(1, $sanitarios, 'El gasto no debe aparecer duplicado en el desglose.');
    }

    public function test_a_consultation_is_filed_under_veterinary_visits(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'consulta',
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Revisión general',
            'costo' => 250,
        ])->assertSessionHasNoErrors();

        $this->assertSame('consultas_veterinarias', Costo::first()->categoria);
    }

    public function test_an_event_without_a_cost_does_not_create_a_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'revision',
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Revisión de rutina',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count());
    }

    public function test_a_lot_wide_event_does_not_create_an_individual_cost(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();

        $lote = \App\Models\Lote::create([
            'nombre' => 'Lote Norte',
            'corral_potrero' => 'Norte',
            'responsable_id' => $user->id,
        ]);

        $this->post(route('eventos-salud.store'), [
            'lote_id' => $lote->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => Vacuna::create(['nombre' => 'Clostridiasis'])->id,
            'costo' => 500,
        ])->assertSessionHasNoErrors();

        // No se prorratea automáticamente: eso se captura desde el módulo de Costos.
        $this->assertSame(0, Costo::count());
    }

    public function test_updating_the_event_cost_updates_the_linked_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();
        $vacuna = Vacuna::create(['nombre' => 'Clostridiasis']);

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => $vacuna->id,
            'costo' => 95,
        ])->assertSessionHasNoErrors();

        $evento = EventoSalud::first();

        $this->put(route('eventos-salud.update', $evento->id), [
            'costo' => 130,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Costo::count());
        $this->assertSame(130.0, (float) Costo::first()->monto);
    }

    public function test_clearing_the_cost_removes_the_linked_cost_row(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => Vacuna::create(['nombre' => 'Clostridiasis'])->id,
            'costo' => 95,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Costo::count());

        $this->put(route('eventos-salud.update', EventoSalud::first()->id), [
            'costo' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count());
    }

    public function test_deleting_the_event_removes_its_cost(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->toDateString(),
            'vacuna_id' => Vacuna::create(['nombre' => 'Clostridiasis'])->id,
            'costo' => 95,
        ])->assertSessionHasNoErrors();

        $this->delete(route('eventos-salud.destroy', EventoSalud::first()->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::count(), 'No deben quedar costos huérfanos.');
    }
}
