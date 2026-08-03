<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalIdentificadorTest extends TestCase
{
    use RefreshDatabase;

    private function crearAnimal(User $user, string $arete, array $overrides = []): Animal
    {
        $this->actingAs($user);

        return Animal::create(array_merge([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => $arete,
            'alias' => 'Pepe',
            'sexo' => 'M',
        ], $overrides));
    }

    public function test_a_microchip_can_be_registered_on_an_animal(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user, 'OV-001');

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'microchip',
            'microchip_codigo' => '985 141 000 123 456',
            'estado_microchip' => 'activo',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $animal->refresh();
        $this->assertSame('985141000123456', $animal->microchip_codigo);
        $this->assertSame('microchip', $animal->tipo_identificador);
    }

    public function test_a_duplicate_microchip_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $primero = $this->crearAnimal($user, 'OV-001');
        $segundo = $this->crearAnimal($user, 'OV-002');

        $this->post(route('animales.identificador.store', $primero->id), [
            'tipo_identificador' => 'microchip',
            'microchip_codigo' => 'CHIP-0001',
        ])->assertSessionHasNoErrors();

        $this->post(route('animales.identificador.store', $segundo->id), [
            'tipo_identificador' => 'microchip',
            'microchip_codigo' => 'chip-0001', // mismo código, distinto formato
        ])->assertSessionHasErrors('microchip_codigo');

        $segundo->refresh();
        $this->assertNull($segundo->microchip_codigo);
    }

    public function test_search_finds_an_animal_by_any_identifier_type(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user, 'OV-777', ['alias' => 'Lucero']);
        $animal->update(['microchip_codigo' => 'CHIP-777']);

        foreach (['OV-777', 'Lucero', (string) $animal->id, 'CHIP-777'] as $codigo) {
            $response = $this->getJson(route('animales.buscar-identificador', ['codigo' => $codigo]));
            $response->assertOk()->assertJson(['encontrado' => true]);
            $this->assertSame($animal->id, $response->json('animal.id'));
        }
    }

    public function test_search_reports_not_found_for_unknown_identifier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson(route('animales.buscar-identificador', ['codigo' => 'NO-EXISTE']))
            ->assertOk()
            ->assertJson(['encontrado' => false]);
    }

    public function test_search_rejects_an_empty_identifier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson(route('animales.buscar-identificador', ['codigo' => '   ']))
            ->assertStatus(422);
    }

    public function test_qr_token_is_generated_lazily_and_scanning_it_redirects_to_the_animal(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user, 'OV-321');

        $this->assertNull($animal->qr_token);

        $response = $this->getJson(route('animales.qr', $animal->id));
        $response->assertOk();
        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // No se usa route('animales.show', ...) aquí a propósito: routes/api.php
        // (capa móvil sin comitear, fuera de alcance) registra otra ruta con el
        // mismo nombre, así que la comparación se hace contra la ruta real y estable.
        $this->get(route('animales.escanear', $token))
            ->assertRedirect('/animales/' . $animal->id);
    }

    public function test_scanning_an_unknown_qr_token_returns_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('animales.escanear', 'token-inexistente'))
            ->assertNotFound();
    }

    public function test_search_and_qr_scan_are_isolated_per_account(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $animal = $this->crearAnimal($owner, 'OV-999');
        $animal->update(['microchip_codigo' => 'CHIP-999']);
        $token = $animal->asegurarQrToken();

        $this->actingAs($intruder);

        $this->getJson(route('animales.buscar-identificador', ['codigo' => 'CHIP-999']))
            ->assertOk()
            ->assertJson(['encontrado' => false]);

        $this->get(route('animales.escanear', $token))->assertNotFound();
    }
}
