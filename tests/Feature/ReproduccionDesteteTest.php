<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\Destete;
use App\Models\DonadorExterno;
use App\Models\EventoReproductivo;
use App\Models\Lote;
use App\Models\Muerte;
use App\Models\Parto;
use App\Models\User;
use Database\Seeders\ControlPartosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReproduccionDesteteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_control_births_seeder_reconciles_unowned_duplicate_business_codes(): void
    {
        $user = User::factory()->create([
            'email' => 'cuenta-seeder@example.com',
        ]);

        DB::table('donadores_externos')->insert([
            [
                'owner_id' => $user->id,
                'codigo' => 'DON-001',
                'nombre' => 'Pegaso existente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_id' => null,
                'codigo' => 'DON-001',
                'nombre' => 'Pegaso duplicado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $valorAnterior = getenv('CONTROL_PARTOS_SEED_EMAIL');
        putenv("CONTROL_PARTOS_SEED_EMAIL={$user->email}");

        try {
            $this->seed(ControlPartosSeeder::class);
        } finally {
            $valorAnterior === false
                ? putenv('CONTROL_PARTOS_SEED_EMAIL')
                : putenv("CONTROL_PARTOS_SEED_EMAIL={$valorAnterior}");
        }

        $this->assertDatabaseCount('donadores_externos', 1);
        $this->assertDatabaseHas('donadores_externos', [
            'owner_id' => $user->id,
            'codigo' => 'DON-001',
            'nombre' => 'Pegaso existente',
        ]);
    }

    public function test_an_old_birth_can_be_weaned_and_is_reflected_with_weight_state_and_lot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $loteNacimiento = $this->createLote('Lote de maternidad', $user);
        $loteDestete = $this->createLote('Lote de destete', $user);

        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-001',
            'alias' => 'Luna',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $loteNacimiento->id,
        ]);

        $criaAnimal = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'CRIA-001',
            'alias' => 'Lucero',
            'sexo' => 'M',
            'fecha_nac' => today()->subYears(2)->toDateString(),
            'peso' => 34,
            'estado_productivo' => 'reemplazo',
            'lote_id' => $loteNacimiento->id,
            'madre_id' => $madre->id,
        ]);

        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $loteNacimiento->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subYears(2)->toDateString(),
            'observaciones' => 'Parto histórico que todavía no se había destetado.',
        ]);

        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'tipo_parto' => 'normal',
            'asistencia_requerida' => false,
            'complicaciones' => false,
            'numero_crias' => 1,
            'salio_leche' => true,
        ]);

        $cria = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $criaAnimal->id,
            'sexo' => 'macho',
            'peso_nacimiento' => 34,
            'condicion' => 'vivo',
            'arete_temporal' => 'TEMP-001',
        ]);

        $response = $this->post(route('reproduccion.destetes.store'), [
            'parto_id' => $parto->id,
            'fecha' => today()->toDateString(),
            'estado_madre' => 'bueno',
            'estado_productivo_madre' => 'mantenimiento',
            'observaciones' => 'Destete registrado aunque el parto ocurrió hace dos años.',
            'crias' => [
                [
                    'cria_id' => $cria->id,
                    'peso_destete' => 185.5,
                    'estado_destino' => 'En crecimiento',
                    'lote_id' => $loteDestete->id,
                ],
            ],
        ]);

        $response
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $destete = Destete::where('parto_id', $parto->id)->firstOrFail();

        $this->assertTrue($parto->fresh()->destete()->exists());
        $this->assertDatabaseHas('evento_reproductivos', [
            'id' => $destete->evento_id,
            'owner_id' => $user->id,
            'hembra_id' => $madre->id,
            'tipo_evento' => 'destete',
        ]);
        $this->assertSame(today()->toDateString(), $destete->evento->fecha->toDateString());
        $this->assertDatabaseHas('destetes', [
            'id' => $destete->id,
            'owner_id' => $user->id,
            'parto_id' => $parto->id,
            'estado_madre' => 'bueno',
            'estado_productivo_madre' => 'mantenimiento',
            'tipo_nacimiento' => 'simple',
        ]);
        $this->assertDatabaseHas('destete_crias', [
            'owner_id' => $user->id,
            'destete_id' => $destete->id,
            'cria_id' => $cria->id,
            'peso_destete' => 185.5,
            'estado_destino' => 'En crecimiento',
        ]);
        $this->assertDatabaseHas('pesajes', [
            'owner_id' => $user->id,
            'animal_id' => $criaAnimal->id,
            'peso' => 185.5,
            'notas' => 'Pesaje registrado durante el destete.',
        ]);
        $this->assertSame(
            today()->toDateString(),
            $criaAnimal->pesajes()->firstOrFail()->fecha->toDateString(),
        );
        $this->assertDatabaseHas('animals', [
            'id' => $criaAnimal->id,
            'owner_id' => $user->id,
            'estado_productivo' => 'En crecimiento',
            'lote_id' => $loteDestete->id,
            'peso' => 185.5,
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $madre->id,
            'estado_productivo' => 'mantenimiento',
        ]);

        $this->get(route('reproduccion.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reproduccion/Index')
                ->where('eventos', function ($eventos) use ($eventoParto, $cria, $loteDestete): bool {
                    $partoReflejado = collect($eventos)->firstWhere('id', $eventoParto->id);

                    return data_get($partoReflejado, 'parto.destetado') === true
                        && data_get($partoReflejado, 'parto.destete.tipo_nacimiento') === 'simple'
                        && (float) data_get($partoReflejado, 'parto.destete.detalles.0.peso_destete') === 185.5
                        && (int) data_get($partoReflejado, 'parto.destete.detalles.0.cria_id') === $cria->id
                        && (int) data_get($partoReflejado, 'parto.crias.0.animal.lote_id') === $loteDestete->id;
                }));
    }

    public function test_reproduction_can_create_a_lightweight_lot_as_json(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('reproduccion.lotes.store'), [
                'nombre' => 'Destetados julio',
                'corral_potrero' => 'Corral 7',
                'descripcion' => 'Lote creado directamente desde el modal de destete.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('lote.nombre', 'Destetados julio')
            ->assertJsonPath('lote.corral_potrero', 'Corral 7')
            ->assertJsonStructure([
                'lote' => ['id', 'nombre', 'corral_potrero'],
            ]);

        $loteId = $response->json('lote.id');

        $this->assertDatabaseHas('lotes', [
            'id' => $loteId,
            'owner_id' => $user->id,
            'responsable_id' => $user->id,
            'nombre' => 'Destetados julio',
            'corral_potrero' => 'Corral 7',
            'descripcion' => 'Lote creado directamente desde el modal de destete.',
        ]);
        $this->assertDatabaseCount('animals', 0);
    }

    public function test_a_birth_with_one_dead_calf_can_wean_only_the_active_calf_and_is_reflected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $loteNacimiento = $this->createLote('Maternidad gemelos', $user);
        $loteDestete = $this->createLote('Destete activos', $user);

        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-GEM-01',
            'alias' => 'Canela',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $loteNacimiento->id,
        ]);

        $animalActivo = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'GEM-ACTIVO',
            'alias' => 'Sol',
            'sexo' => 'M',
            'fecha_nac' => today()->subDays(180)->toDateString(),
            'peso' => 32,
            'estado_productivo' => 'reemplazo',
            'lote_id' => $loteNacimiento->id,
            'madre_id' => $madre->id,
        ]);

        $animalMuerto = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'GEM-MUERTO',
            'alias' => 'Nube',
            'sexo' => 'H',
            'fecha_nac' => today()->subDays(180)->toDateString(),
            'peso' => 31,
            'estado_productivo' => 'muerto',
            'lote_id' => $loteNacimiento->id,
            'madre_id' => $madre->id,
        ]);

        Muerte::create([
            'animal_id' => $animalMuerto->id,
            'fecha' => today()->subDays(20)->toDateString(),
            'causa' => 'Neumonia',
            'observaciones' => 'Fallecio antes de la fecha de destete.',
        ]);

        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $loteNacimiento->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(180)->toDateString(),
        ]);

        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'tipo_parto' => 'normal',
            'asistencia_requerida' => false,
            'complicaciones' => false,
            'numero_crias' => 2,
            'salio_leche' => true,
        ]);

        $criaActiva = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalActivo->id,
            'sexo' => 'macho',
            'peso_nacimiento' => 32,
            'condicion' => 'vivo',
        ]);

        $criaMuerta = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalMuerto->id,
            'sexo' => 'hembra',
            'peso_nacimiento' => 31,
            'condicion' => 'vivo',
        ]);

        $this->post(route('reproduccion.destetes.store'), [
            'parto_id' => $parto->id,
            'fecha' => today()->toDateString(),
            'estado_madre' => 'regular',
            'estado_productivo_madre' => 'mantenimiento',
            'observaciones' => 'Solo se desteta la cria que continua activa.',
            'crias' => [
                [
                    'cria_id' => $criaActiva->id,
                    'peso_destete' => 172.4,
                    'estado_destino' => 'En crecimiento',
                    'lote_id' => $loteDestete->id,
                ],
            ],
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $destete = Destete::where('parto_id', $parto->id)->firstOrFail();

        $this->assertDatabaseHas('destetes', [
            'id' => $destete->id,
            'parto_id' => $parto->id,
            'estado_productivo_madre' => 'mantenimiento',
            'tipo_nacimiento' => 'simple',
        ]);
        $this->assertDatabaseHas('destete_crias', [
            'destete_id' => $destete->id,
            'cria_id' => $criaActiva->id,
            'peso_destete' => 172.4,
            'estado_destino' => 'En crecimiento',
        ]);
        $this->assertDatabaseMissing('destete_crias', [
            'destete_id' => $destete->id,
            'cria_id' => $criaMuerta->id,
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animalActivo->id,
            'estado_productivo' => 'En crecimiento',
            'lote_id' => $loteDestete->id,
            'peso' => 172.4,
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animalMuerto->id,
            'estado_productivo' => 'muerto',
            'lote_id' => $loteNacimiento->id,
        ]);

        $this->get(route('reproduccion.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reproduccion/Index')
                ->where('eventos', function ($eventos) use (
                    $eventoParto,
                    $criaActiva,
                    $criaMuerta,
                    $loteDestete,
                ): bool {
                    $partoReflejado = collect($eventos)->firstWhere('id', $eventoParto->id);
                    $detalles = collect(data_get($partoReflejado, 'parto.destete.detalles', []));
                    $crias = collect(data_get($partoReflejado, 'parto.crias', []));
                    $activaReflejada = $crias->firstWhere('id', $criaActiva->id);
                    $muertaReflejada = $crias->firstWhere('id', $criaMuerta->id);

                    return data_get($partoReflejado, 'parto.destetado') === true
                        && data_get($partoReflejado, 'parto.destete.tipo_nacimiento') === 'simple'
                        && $detalles->count() === 1
                        && (int) data_get($detalles->first(), 'cria_id') === $criaActiva->id
                        && (int) data_get($activaReflejada, 'animal.lote_id') === $loteDestete->id
                        && data_get($muertaReflejada, 'disponible_destete') === false
                        && data_get($muertaReflejada, 'situacion') === 'muerto'
                        && data_get($muertaReflejada, 'causa_baja') === 'Neumonia'
                        && data_get($muertaReflejada, 'animal.estado_productivo') === 'muerto'
                        && data_get($muertaReflejada, 'animal.muerte.causa') === 'Neumonia';
                }));
    }

    public function test_a_birth_without_available_calves_cannot_be_weaned_and_exposes_the_reasons(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lote = $this->createLote('Maternidad sin crias disponibles', $user);
        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-SIN-DESTETE',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $lote->id,
        ]);
        $animalVendido = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'CRIA-VENDIDA',
            'sexo' => 'M',
            'estado_productivo' => 'vendido',
            'lote_id' => $lote->id,
            'madre_id' => $madre->id,
        ]);
        $evento = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $lote->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(120)->toDateString(),
        ]);
        $parto = Parto::create([
            'evento_id' => $evento->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 2,
            'salio_leche' => true,
        ]);
        $nacidaMuerta = Cria::create([
            'parto_id' => $parto->id,
            'sexo' => 'hembra',
            'peso_nacimiento' => 28,
            'condicion' => 'nacido_muerto',
            'observaciones' => 'No presentó signos vitales.',
        ]);
        $vendida = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalVendido->id,
            'sexo' => 'macho',
            'peso_nacimiento' => 31,
            'condicion' => 'vivo',
            'observaciones' => 'La cría salió del hato antes del destete.',
        ]);

        $this->from(route('reproduccion.index'))
            ->post(route('reproduccion.destetes.store'), [
                'parto_id' => $parto->id,
                'fecha' => today()->toDateString(),
                'estado_madre' => 'regular',
                'estado_productivo_madre' => 'mantenimiento',
                'crias' => [
                    [
                        'cria_id' => $nacidaMuerta->id,
                        'peso_destete' => 28,
                        'estado_destino' => 'En crecimiento',
                    ],
                    [
                        'cria_id' => $vendida->id,
                        'peso_destete' => 120,
                        'estado_destino' => 'En crecimiento',
                    ],
                ],
            ])
            ->assertRedirect(route('reproduccion.index'))
            ->assertSessionHasErrors('crias');

        $this->assertDatabaseMissing('destetes', ['parto_id' => $parto->id]);

        $this->get(route('reproduccion.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reproduccion/Index')
                ->where('eventos', function ($eventos) use (
                    $evento,
                    $nacidaMuerta,
                    $vendida,
                ): bool {
                    $partoReflejado = collect($eventos)->firstWhere('id', $evento->id);
                    $crias = collect(data_get($partoReflejado, 'parto.crias', []));
                    $muertaReflejada = $crias->firstWhere('id', $nacidaMuerta->id);
                    $vendidaReflejada = $crias->firstWhere('id', $vendida->id);

                    return data_get($partoReflejado, 'parto.destetado') === false
                        && $crias->every(
                            fn ($cria): bool => data_get($cria, 'disponible_destete') === false,
                        )
                        && data_get($muertaReflejada, 'situacion') === 'nacido_muerto'
                        && data_get($muertaReflejada, 'causa_baja') === 'Nació muerta'
                        && data_get($vendidaReflejada, 'situacion') === 'vendido'
                        && data_get($vendidaReflejada, 'causa_baja') === 'Animal vendido';
                }));
    }

    public function test_a_missing_note_can_be_added_to_an_unavailable_calf_and_is_serialized(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lote = $this->createLote('Notas de bajas', $user);
        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-NOTA-BAJA',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $lote->id,
        ]);
        $evento = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $lote->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(30)->toDateString(),
        ]);
        $parto = Parto::create([
            'evento_id' => $evento->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 1,
            'salio_leche' => false,
        ]);
        $cria = Cria::create([
            'parto_id' => $parto->id,
            'sexo' => 'hembra',
            'peso_nacimiento' => 27,
            'condicion' => 'nacido_muerto',
        ]);

        $this->patch(route('reproduccion.crias.observaciones', $cria), [
            'observaciones' => 'La madre tuvo una complicación durante el parto.',
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('crias', [
            'id' => $cria->id,
            'observaciones' => 'La madre tuvo una complicación durante el parto.',
        ]);

        $this->get(route('reproduccion.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('eventos', function ($eventos) use ($evento, $cria): bool {
                    $partoReflejado = collect($eventos)->firstWhere('id', $evento->id);
                    $criaReflejada = collect(data_get($partoReflejado, 'parto.crias', []))
                        ->firstWhere('id', $cria->id);

                    return data_get($criaReflejada, 'disponible_destete') === false
                        && data_get($criaReflejada, 'observacion_baja')
                            === 'La madre tuvo una complicación durante el parto.';
                }));
    }

    public function test_destete_rejects_death_and_sale_as_destination_states(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lote = $this->createLote('Destete sin bajas', $user);
        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-SIN-BAJAS',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $lote->id,
        ]);
        $animalCria = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'CRIA-SIN-BAJAS',
            'sexo' => 'M',
            'peso' => 95,
            'estado_productivo' => 'reemplazo',
            'lote_id' => $lote->id,
            'madre_id' => $madre->id,
        ]);
        $evento = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $lote->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(100)->toDateString(),
        ]);
        $parto = Parto::create([
            'evento_id' => $evento->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 1,
            'salio_leche' => true,
        ]);
        $cria = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalCria->id,
            'sexo' => 'macho',
            'peso_nacimiento' => 30,
            'condicion' => 'vivo',
        ]);

        foreach (['muerto', 'vendido'] as $estadoTerminal) {
            $this->from(route('reproduccion.index'))
                ->post(route('reproduccion.destetes.store'), [
                    'parto_id' => $parto->id,
                    'fecha' => today()->toDateString(),
                    'estado_madre' => 'bueno',
                    'estado_productivo_madre' => 'mantenimiento',
                    'crias' => [[
                        'cria_id' => $cria->id,
                        'peso_destete' => 100,
                        'estado_destino' => $estadoTerminal,
                    ]],
                ])
                ->assertRedirect(route('reproduccion.index'))
                ->assertSessionHasErrors('crias.0.estado_destino');
        }

        $this->assertDatabaseMissing('destetes', ['parto_id' => $parto->id]);
        $this->assertDatabaseCount('muertes', 0);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_reproduction_serializes_mother_and_internal_or_external_father_labels(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lote = $this->createLote('Genealogia', $user);

        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-ETIQUETA',
            'alias' => 'Estrella',
            'sexo' => 'H',
            'estado_productivo' => 'lactancia',
            'lote_id' => $lote->id,
        ]);

        $padreInterno = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'PADRE-INTERNO',
            'alias' => 'Trueno',
            'sexo' => 'M',
            'estado_productivo' => 'Reproductor',
            'lote_id' => $lote->id,
        ]);

        $padreExterno = DonadorExterno::create([
            'codigo' => 'EXT-GEN-001',
            'nombre' => 'Titan Genomico',
            'raza' => 'Angus',
        ]);

        $animalPadreInterno = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'CRIA-PADRE-INT',
            'alias' => 'Rayo',
            'sexo' => 'M',
            'estado_productivo' => 'reemplazo',
            'madre_id' => $madre->id,
            'padre_id' => $padreInterno->id,
        ]);

        $animalPadreExterno = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'CRIA-PADRE-EXT',
            'alias' => 'Aurora',
            'sexo' => 'H',
            'estado_productivo' => 'reemplazo',
            'madre_id' => $madre->id,
            'padre_externo_id' => $padreExterno->id,
        ]);

        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $lote->id,
            'user_id' => $user->id,
            'tipo_evento' => 'parto',
            'fecha' => today()->subDays(15)->toDateString(),
        ]);

        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'tipo_parto' => 'normal',
            'numero_crias' => 2,
            'salio_leche' => true,
        ]);

        $criaInterna = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalPadreInterno->id,
            'sexo' => 'macho',
            'peso_nacimiento' => 33,
            'condicion' => 'vivo',
        ]);

        $criaExterna = Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animalPadreExterno->id,
            'sexo' => 'hembra',
            'peso_nacimiento' => 32,
            'condicion' => 'vivo',
        ]);

        $this->get(route('reproduccion.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reproduccion/Index')
                ->where('eventos', function ($eventos) use (
                    $eventoParto,
                    $criaInterna,
                    $criaExterna,
                ): bool {
                    $partoReflejado = collect($eventos)->firstWhere('id', $eventoParto->id);
                    $crias = collect(data_get($partoReflejado, 'parto.crias', []));
                    $interna = $crias->firstWhere('id', $criaInterna->id);
                    $externa = $crias->firstWhere('id', $criaExterna->id);

                    return data_get($partoReflejado, 'hembra.alias') === 'Estrella'
                        && data_get($partoReflejado, 'hembra.arete') === 'MADRE-ETIQUETA'
                        && data_get($interna, 'animal.padre.alias') === 'Trueno'
                        && data_get($interna, 'animal.padre.arete') === 'PADRE-INTERNO'
                        && data_get($externa, 'animal.padre_externo.nombre') === 'Titan Genomico'
                        && data_get($externa, 'animal.padre_externo.codigo') === 'EXT-GEN-001';
                }));
    }

    private function createLote(string $nombre, User $responsable): Lote
    {
        return Lote::create([
            'nombre' => $nombre,
            'corral_potrero' => 'Corral de prueba',
            'responsable_id' => $responsable->id,
        ]);
    }
}
