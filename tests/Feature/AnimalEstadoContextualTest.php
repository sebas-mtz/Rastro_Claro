<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Destete;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Muerte;
use App\Models\Parto;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Vacuna;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnimalEstadoContextualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-07-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_terminal_state_is_the_only_current_status_shown(): void
    {
        [$user, $animal] = $this->createUserAndAnimal(
            arete: 'ESTADO-MUERTO',
            sexo: 'H',
            estado: 'muerto',
        );

        Muerte::create([
            'animal_id' => $animal->id,
            'fecha' => today()->subDay()->toDateString(),
            'causa' => 'Enfermedad',
            'observaciones' => 'Baja registrada.',
        ]);

        $this->actingAs($user)
            ->get(route('animales.show', $animal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Animals/ShowAnimal')
                ->has('estadoContextual', 1)
                ->where('estadoContextual.0.tipo', 'terminal')
                ->where('estadoContextual.0.titulo', 'Fallecido')
                ->where('estadoContextual.0.fecha', today()->subDay()->toDateString()));
    }

    public function test_active_treatment_and_recent_direct_vaccination_are_shown(): void
    {
        [$user, $animal] = $this->createUserAndAnimal(
            arete: 'ESTADO-SALUD',
            sexo: 'M',
            estado: 'En crecimiento',
        );

        Tratamiento::create([
            'animal_id' => $animal->id,
            'nombre' => 'Antibiótico respiratorio',
            'fecha_inicio' => today()->subDays(2)->toDateString(),
            'fecha_fin' => today()->addDays(5)->toDateString(),
            'estado' => Tratamiento::ESTADO_ACTIVO,
            'user_id' => $user->id,
        ]);

        $vacuna = Vacuna::create([
            'nombre' => 'Clostridial',
            'especie_objetivo' => 'Bovino',
        ]);
        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'fecha_programada' => today()->subDays(3)->toDateString(),
            'fecha_aplicacion' => today()->subDays(3)->toDateString(),
            'diagnostico' => 'Vacunación preventiva',
            'vacuna_id' => $vacuna->id,
            'estado' => EventoSalud::ESTADO_APLICADA,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('animales.show', $animal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Animals/ShowAnimal')
                ->has('estadoContextual', 2)
                ->where('estadoContextual.0.tipo', 'tratamiento')
                ->where('estadoContextual.0.titulo', 'En tratamiento')
                ->where('estadoContextual.1.tipo', 'vacunacion')
                ->where('estadoContextual.1.titulo', 'Vacunado recientemente')
                ->where('estadoContextual.1.detalle', 'Clostridial'));
    }

    public function test_a_female_with_a_recent_birth_is_shown_as_lactating(): void
    {
        [$user, $madre] = $this->createUserAndAnimal(
            arete: 'ESTADO-LACTANCIA',
            sexo: 'H',
            estado: 'lactancia',
        );

        $evento = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(10)->toDateString(),
        ]);
        Parto::create([
            'evento_id' => $evento->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 1,
            'salio_leche' => true,
        ]);

        $this->actingAs($user)
            ->get(route('animales.show', $madre))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('estadoContextual', 1)
                ->where('estadoContextual.0.tipo', 'reproduccion')
                ->where('estadoContextual.0.titulo', 'En lactancia')
                ->where(
                    'estadoContextual.0.fecha',
                    today()->subDays(10)->toDateString(),
                ));
    }

    public function test_a_mother_with_a_recent_weaning_is_shown_in_post_weaning_rest(): void
    {
        [$user, $madre] = $this->createUserAndAnimal(
            arete: 'ESTADO-DESCANSO',
            sexo: 'H',
            estado: 'mantenimiento',
        );

        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(100)->toDateString(),
        ]);
        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 1,
            'salio_leche' => true,
        ]);
        $eventoDestete = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $user->id,
            'tipo_evento' => 'destete',
            'fecha' => today()->subDays(5)->toDateString(),
        ]);
        Destete::create([
            'evento_id' => $eventoDestete->id,
            'parto_id' => $parto->id,
            'estado_madre' => 'bueno',
            'estado_productivo_madre' => 'mantenimiento',
            'tipo_nacimiento' => 'simple',
        ]);

        $this->actingAs($user)
            ->get(route('animales.show', $madre))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('estadoContextual', 1)
                ->where('estadoContextual.0.tipo', 'destete')
                ->where(
                    'estadoContextual.0.titulo',
                    'En descanso después del destete',
                )
                ->where(
                    'estadoContextual.0.fecha',
                    today()->subDays(5)->toDateString(),
                ));
    }

    public function test_productive_state_is_used_as_fallback_when_there_is_no_recent_context(): void
    {
        [$user, $animal] = $this->createUserAndAnimal(
            arete: 'ESTADO-FALLBACK',
            sexo: 'M',
            estado: 'Reproductor',
        );

        $this->actingAs($user)
            ->get(route('animales.show', $animal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('estadoContextual', 1)
                ->where('estadoContextual.0.tipo', 'productivo')
                ->where('estadoContextual.0.titulo', 'Estado productivo actual')
                ->where('estadoContextual.0.detalle', 'Reproductor'));
    }

    /**
     * @return array{User, Animal}
     */
    private function createUserAndAnimal(
        string $arete,
        string $sexo,
        string $estado,
    ): array {
        $user = User::factory()->create();
        $this->actingAs($user);

        $animal = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => $arete,
            'sexo' => $sexo,
            'peso' => 100,
            'estado_productivo' => $estado,
        ]);

        return [$user, $animal];
    }
}
