<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Raza;
use App\Models\User;
use App\Services\EtapaVidaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1 de la especialización ovina: catálogo de razas, restricción de
 * especie, etapas de vida y validaciones de genealogía.
 */
class OvinoEspecializacionTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function raza(string $nombre = 'Dorper'): Raza
    {
        return Raza::create(['nombre' => $nombre, 'activo' => true]);
    }

    private function animal(array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-' . fake()->unique()->numberBetween(1000, 9999),
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ], $overrides));
    }

    // ─── Especie ──────────────────────────────────────────────────────────

    public function test_a_new_animal_is_always_registered_as_ovine(): void
    {
        $this->usuario();

        // Aunque se intente enviar otra especie, el servidor la ignora.
        $this->post(route('animales.store'), [
            'especie' => 'Bovino',
            'arete' => 'OV-100',
            'sexo' => 'F',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Animal::ESPECIE, Animal::first()->especie);
    }

    // ─── Catálogo de razas ────────────────────────────────────────────────

    public function test_an_animal_can_record_a_main_and_a_second_breed(): void
    {
        $this->usuario();
        $dorper = $this->raza('Dorper');
        $pelibuey = $this->raza('Pelibuey');

        $this->post(route('animales.store'), [
            'arete' => 'OV-200',
            'sexo' => 'F',
            'raza_id' => $dorper->id,
            'raza_secundaria_id' => $pelibuey->id,
        ])->assertSessionHasNoErrors();

        $animal = Animal::first();

        $this->assertSame($dorper->id, $animal->raza_id);
        $this->assertSame($pelibuey->id, $animal->raza_secundaria_id);
        $this->assertTrue($animal->es_cruza);
        $this->assertSame('Dorper x Pelibuey', $animal->raza_descriptiva);
    }

    public function test_a_pure_animal_is_not_marked_as_a_cross(): void
    {
        $this->usuario();
        $dorper = $this->raza('Dorper');

        $this->post(route('animales.store'), [
            'arete' => 'OV-201',
            'sexo' => 'F',
            'raza_id' => $dorper->id,
        ])->assertSessionHasNoErrors();

        $animal = Animal::first();

        $this->assertFalse($animal->es_cruza);
        $this->assertSame('Dorper', $animal->raza_descriptiva);
    }

    public function test_the_second_breed_cannot_repeat_the_main_one(): void
    {
        $this->usuario();
        $dorper = $this->raza('Dorper');

        $this->post(route('animales.store'), [
            'arete' => 'OV-202',
            'sexo' => 'F',
            'raza_id' => $dorper->id,
            'raza_secundaria_id' => $dorper->id,
        ])->assertSessionHasErrors('raza_secundaria_id');
    }

    public function test_breed_names_are_normalized_for_comparison(): void
    {
        $this->assertSame('katahdin', Raza::normalizarNombre('  Katahdin  '));
        $this->assertSame('katahdin', Raza::normalizarNombre('KATAHDIN'));
        $this->assertSame(
            'Katahdin',
            Raza::EQUIVALENCIAS[Raza::normalizarNombre('Kathadin')] ?? null,
            'El error de captura "Kathadin" debe reconocerse como "Katahdin".'
        );
    }

    // ─── Genealogía ───────────────────────────────────────────────────────

    public function test_the_mother_must_be_female(): void
    {
        $this->usuario();
        $macho = $this->animal(['sexo' => 'M', 'fecha_nac' => now()->subYears(3)->toDateString()]);

        $this->post(route('animales.store'), [
            'arete' => 'OV-300',
            'sexo' => 'F',
            'madre_id' => $macho->id,
        ])->assertSessionHasErrors('madre_id');
    }

    public function test_the_father_must_be_male(): void
    {
        $this->usuario();
        $hembra = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subYears(3)->toDateString()]);

        $this->post(route('animales.store'), [
            'arete' => 'OV-301',
            'sexo' => 'F',
            'padre_id' => $hembra->id,
        ])->assertSessionHasErrors('padre_id');
    }

    public function test_parents_must_be_born_before_the_offspring(): void
    {
        $this->usuario();
        $madre = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subMonths(2)->toDateString()]);

        $this->post(route('animales.store'), [
            'arete' => 'OV-302',
            'sexo' => 'F',
            'fecha_nac' => now()->subYears(2)->toDateString(),
            'madre_id' => $madre->id,
        ])->assertSessionHasErrors('madre_id');
    }

    public function test_an_animal_cannot_be_its_own_parent(): void
    {
        $this->usuario();
        $animal = $this->animal(['sexo' => 'M']);

        $this->put(route('animales.update', $animal->id), [
            'arete' => $animal->arete,
            'sexo' => 'M',
            'padre_id' => $animal->id,
        ])->assertSessionHasErrors('padre_id');
    }

    public function test_a_genealogical_cycle_is_rejected(): void
    {
        $this->usuario();

        $abuela = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subYears(4)->toDateString()]);
        $madre = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subYears(2)->toDateString(), 'madre_id' => $abuela->id]);

        // Intentar que la abuela sea hija de su propia nieta cierra un ciclo.
        $this->put(route('animales.update', $abuela->id), [
            'arete' => $abuela->arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYears(4)->toDateString(),
            'madre_id' => $madre->id,
        ])->assertSessionHasErrors('madre_id');
    }

    public function test_a_valid_parentage_is_accepted(): void
    {
        $this->usuario();
        $madre = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subYears(3)->toDateString()]);
        $padre = $this->animal(['sexo' => 'M', 'fecha_nac' => now()->subYears(4)->toDateString()]);

        $this->post(route('animales.store'), [
            'arete' => 'OV-400',
            'sexo' => 'F',
            'fecha_nac' => now()->subMonths(6)->toDateString(),
            'madre_id' => $madre->id,
            'padre_id' => $padre->id,
        ])->assertSessionHasNoErrors();

        $cria = Animal::where('arete', 'OV-400')->first();

        $this->assertSame($madre->id, $cria->madre_id);
        $this->assertSame($padre->id, $cria->padre_id);
    }

    // ─── Etapas de vida ───────────────────────────────────────────────────

    public function test_life_stage_is_suggested_but_never_saved_on_its_own(): void
    {
        $this->usuario();
        $cordera = $this->animal(['sexo' => 'F', 'fecha_nac' => now()->subMonth()->toDateString()]);

        $sugerencia = app(EtapaVidaService::class)->sugerir($cordera);

        $this->assertSame(EtapaVidaService::CORDERA_LACTANTE, $sugerencia['etapa']);
        $this->assertNotEmpty($sugerencia['motivo']);

        // La sugerencia no toca la base de datos: sigue sin etapa confirmada.
        $this->assertNull($cordera->fresh()->etapa_vida);
        $this->assertNull($cordera->fresh()->etapa_vida_confirmada_at);
    }

    public function test_without_a_birth_date_no_life_stage_is_invented(): void
    {
        $this->usuario();
        $animal = $this->animal(['fecha_nac' => null]);

        $sugerencia = app(EtapaVidaService::class)->sugerir($animal);

        $this->assertNull($sugerencia['etapa']);
        $this->assertStringContainsString('Sin fecha de nacimiento', $sugerencia['motivo']);
    }

    public function test_an_adult_male_is_suggested_as_a_stud(): void
    {
        $this->usuario();
        $semental = $this->animal(['sexo' => 'M', 'fecha_nac' => now()->subYears(3)->toDateString()]);

        $this->assertSame(
            EtapaVidaService::SEMENTAL_ADULTO,
            app(EtapaVidaService::class)->sugerir($semental)['etapa']
        );
    }

    public function test_confirming_a_life_stage_records_when_it_was_accepted(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->put(route('animales.update', $animal->id), [
            'arete' => $animal->arete,
            'sexo' => 'F',
            'etapa_vida' => EtapaVidaService::BORREGA_EDAD_REPRODUCTIVA,
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        $this->assertSame(EtapaVidaService::BORREGA_EDAD_REPRODUCTIVA, $animal->etapa_vida);
        $this->assertNotNull($animal->etapa_vida_confirmada_at);
    }

    public function test_an_unknown_life_stage_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->put(route('animales.update', $animal->id), [
            'arete' => $animal->arete,
            'sexo' => 'F',
            'etapa_vida' => 'vaca_lechera',
        ])->assertSessionHasErrors('etapa_vida');
    }
}
