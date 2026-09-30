<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Lote;
use App\Models\Raza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta de lotes con ejemplares ovinos.
 *
 * Cubre el flujo que se rompió al retirar los catálogos de otras especies:
 * la especie ya no se envía y la raza viene del catálogo configurable.
 */
class LoteOvinoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_the_lots_page_loads_with_the_breed_catalog(): void
    {
        $this->usuario();
        Raza::create(['nombre' => 'Dorper', 'activo' => true]);

        $respuesta = $this->get(route('lotes.index'));
        $respuesta->assertOk();

        $props = $respuesta->getOriginalContent()->getData()['page']['props'];

        // La página ya no recibe el mapa de razas por especie, sino el catálogo.
        $this->assertArrayHasKey('razas', $props);
        $this->assertArrayHasKey('tiposLote', $props);
        $this->assertArrayNotHasKey('razasPorEspecie', $props);
        $this->assertSame(['Ovino'], $props['especies']);
    }

    public function test_a_lot_is_created_with_its_ovine_animals(): void
    {
        $this->usuario();
        $dorper = Raza::create(['nombre' => 'Dorper', 'activo' => true]);

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote Reproductoras',
            'corral_potrero' => 'Norte',
            'tipo' => 'reproductoras',
            'capacidad' => 40,
            'animal' => [
                'raza_id' => $dorper->id,
                'arete_inicio' => 100,
                'arete_fin' => 103,
                'sexo' => 'F',
            ],
        ])->assertSessionHasNoErrors();

        $lote = Lote::first();

        $this->assertNotNull($lote);
        $this->assertSame('reproductoras', $lote->tipo);
        $this->assertSame('Borregas reproductoras', $lote->tipo_legible);
        $this->assertSame(40, $lote->capacidad);

        // 4 ejemplares: aretes 100 a 103
        $this->assertSame(4, Animal::count());

        $animal = Animal::first();
        $this->assertSame(Animal::ESPECIE, $animal->especie);
        $this->assertSame($dorper->id, $animal->raza_id);
        $this->assertSame($lote->id, $animal->lote_id);
    }

    public function test_the_species_is_never_taken_from_the_request(): void
    {
        $this->usuario();

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote Intruso',
            'corral_potrero' => 'Sur',
            'animal' => [
                'especie' => 'Bovino',   // se ignora
                'arete_inicio' => 200,
                'arete_fin' => 200,
                'sexo' => 'M',
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(Animal::ESPECIE, Animal::first()->especie);
    }

    public function test_an_unknown_lot_type_is_rejected(): void
    {
        $this->usuario();

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote Raro',
            'corral_potrero' => 'Sur',
            'tipo' => 'establo_de_caballos',
            'animal' => [
                'arete_inicio' => 300,
                'arete_fin' => 300,
                'sexo' => 'F',
            ],
        ])->assertSessionHasErrors('tipo');
    }

    public function test_a_breed_outside_the_catalog_is_rejected(): void
    {
        $this->usuario();

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote Sur',
            'corral_potrero' => 'Sur',
            'animal' => [
                'raza_id' => 9999,
                'arete_inicio' => 400,
                'arete_fin' => 400,
                'sexo' => 'F',
            ],
        ])->assertSessionHasErrors('animal.raza_id');
    }

    public function test_the_tag_range_must_be_coherent(): void
    {
        $this->usuario();

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote Sur',
            'corral_potrero' => 'Sur',
            'animal' => [
                'arete_inicio' => 500,
                'arete_fin' => 490,   // menor que el inicio
                'sexo' => 'F',
            ],
        ])->assertSessionHasErrors('animal.arete_fin');
    }
}
