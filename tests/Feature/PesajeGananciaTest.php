<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\Pesaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PesajeGananciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_first_weighing_is_compared_with_the_registered_initial_weight(): void
    {
        [$user, $animal] = $this->createUserAndAnimal('PESO-001');

        $this->actingAs($user)
            ->post(route('pesajes.store'), [
                'animal_id' => $animal->id,
                'fecha' => '2026-01-02',
                'peso' => 11,
                'notas' => 'Primer pesaje agregado.',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertGainSummary(
            user: $user,
            expectedCurrentWeight: 11,
            expectedTotalGain: 1,
            expectedDailyGain: 1,
            expectedDays: 1,
        );

        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'peso' => 11,
            'peso_inicial' => 10,
        ]);
        $this->assertSame(
            '2026-01-01',
            $animal->fresh()->fecha_peso_inicial->toDateString(),
        );
    }

    public function test_second_weighing_keeps_the_registered_initial_weight_as_total_baseline(): void
    {
        [$user, $animal] = $this->createUserAndAnimal('PESO-002');

        $this->createPesajeThroughEndpoint($user, $animal, '2026-01-02', 11);
        $this->createPesajeThroughEndpoint($user, $animal, '2026-01-05', 14);

        $this->assertGainSummary(
            user: $user,
            expectedCurrentWeight: 14,
            expectedTotalGain: 4,
            expectedDailyGain: 1,
            expectedDays: 4,
        );

        $this->assertSame(10.0, $animal->fresh()->peso_inicial);
    }

    public function test_deleting_the_only_weighing_restores_the_initial_weight(): void
    {
        [$user, $animal] = $this->createUserAndAnimal('PESO-003');

        $this->createPesajeThroughEndpoint($user, $animal, '2026-01-02', 12);
        $pesaje = Pesaje::where('animal_id', $animal->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('pesajes.destroy', $pesaje))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('pesajes', ['id' => $pesaje->id]);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'peso' => 10,
            'peso_inicial' => 10,
        ]);
    }

    public function test_a_live_calf_created_from_a_birth_keeps_its_birth_weight_as_baseline(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $madre = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => 'MADRE-PESO-NATAL',
            'sexo' => 'H',
            'peso' => 480,
            'fecha_peso_inicial' => '2024-01-01',
            'estado_productivo' => 'gestante',
        ]);

        $this->post(route('reproduccion.partos.store'), [
            'hembra_id' => $madre->id,
            'fecha' => '2026-01-01',
            'tipo_parto' => 'normal',
            'asistencia_requerida' => false,
            'complicaciones' => false,
            'salio_leche' => true,
            'crias' => [[
                'sexo' => 'hembra',
                'peso_nacimiento' => 32.5,
                'condicion' => 'vivo',
                'arete' => 'CRIA-PESO-NATAL',
            ]],
        ])
            ->assertRedirect(route('reproduccion.index'))
            ->assertSessionDoesntHaveErrors();

        $cria = Cria::with('animal')->firstOrFail();

        $this->assertSame(32.5, $cria->animal->peso);
        $this->assertSame(32.5, $cria->animal->peso_inicial);
        $this->assertSame(
            '2026-01-01',
            $cria->animal->fecha_peso_inicial->toDateString(),
        );
    }

    /**
     * @return array{User, Animal}
     */
    private function createUserAndAnimal(string $arete): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $animal = Animal::create([
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => $arete,
            'sexo' => 'M',
            'peso' => 10,
            'peso_inicial' => 10,
            'fecha_peso_inicial' => '2026-01-01',
            'estado_productivo' => 'En crecimiento',
        ]);

        return [$user, $animal];
    }

    private function createPesajeThroughEndpoint(
        User $user,
        Animal $animal,
        string $fecha,
        float $peso,
    ): void {
        $this->actingAs($user)
            ->post(route('pesajes.store'), [
                'animal_id' => $animal->id,
                'fecha' => $fecha,
                'peso' => $peso,
                'notas' => 'Pesaje de prueba.',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
    }

    private function assertGainSummary(
        User $user,
        float $expectedCurrentWeight,
        float $expectedTotalGain,
        float $expectedDailyGain,
        int $expectedDays,
    ): void {
        $response = $this
            ->actingAs($user)
            ->get(route('pesajes.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pesajes/Pesajes')
                ->has('animales', 1)
                ->where(
                    'animales.0.peso_actual',
                    fn ($value): bool => (float) $value === $expectedCurrentWeight,
                )
                ->where(
                    'animales.0.ganancia_total',
                    fn ($value): bool => (float) $value === $expectedTotalGain,
                )
                ->where(
                    'animales.0.ganancia_diaria',
                    fn ($value): bool => (float) $value === $expectedDailyGain,
                )
                ->where(
                    'animales.0.dias_seguimiento',
                    fn ($value): bool => (int) $value === $expectedDays,
                ));
    }
}
