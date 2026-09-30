<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\DonadorExterno;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_are_assigned_to_and_visible_only_by_the_authenticated_account(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser);
        $firstAnimal = Animal::create($this->animalData('A-001'));

        $this->assertSame($firstUser->id, $firstAnimal->owner_id);
        $this->assertTrue(Animal::whereKey($firstAnimal->id)->exists());

        $this->actingAs($secondUser);
        $secondAnimal = Animal::create($this->animalData('B-001'));

        $this->assertSame($secondUser->id, $secondAnimal->owner_id);
        $this->assertFalse(Animal::whereKey($firstAnimal->id)->exists());
        $this->assertSame([$secondAnimal->id], Animal::pluck('id')->all());
    }

    public function test_route_model_binding_cannot_open_another_accounts_record(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner);
        $animal = Animal::create($this->animalData('PRIVATE-1'));

        $this->actingAs($intruder)
            ->get(route('animales.show', $animal->id))
            ->assertNotFound();
    }

    public function test_exists_validation_rejects_a_foreign_accounts_related_record(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner);
        $lote = Lote::create([
            'nombre' => 'Privado',
            'corral_potrero' => 'Norte',
            'responsable_id' => $owner->id,
        ]);

        $this->actingAs($intruder)
            ->post(route('animales.store'), [
                ...$this->animalData('ATTACK-1'),
                'lote_id' => $lote->id,
            ])
            ->assertSessionHasErrors('lote_id');
    }

    public function test_reports_and_animal_images_require_authentication(): void
    {
        $this->get(route('reportes.index'))->assertRedirect(route('login'));
        $this->post(route('animales.imagen', 999))->assertRedirect(route('login'));
    }

    public function test_legacy_unowned_rows_are_fail_closed_until_claimed(): void
    {
        $user = User::factory()->create();
        $legacyAnimal = Animal::create($this->animalData('LEGACY-1'));

        $this->assertNull($legacyAnimal->owner_id);

        $this->actingAs($user);
        $this->assertFalse(Animal::whereKey($legacyAnimal->id)->exists());

        auth()->logout();
        $this->artisan('tenancy:claim-legacy', [
            'email' => $user->email,
            '--force' => true,
        ])->assertSuccessful();

        $this->actingAs($user);
        $this->assertTrue(Animal::whereKey($legacyAnimal->id)->exists());
    }

    public function test_business_codes_can_repeat_between_accounts_but_not_within_one_account(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser);
        DonadorExterno::create([
            'codigo' => 'DON-001',
            'nombre' => 'Donador de la primera cuenta',
        ]);

        $this->actingAs($secondUser)
            ->post(route('donadores-externos.store'), [
                'codigo' => 'DON-001',
                'nombre' => 'Donador de la segunda cuenta',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $this->assertSame(1, DonadorExterno::where('codigo', 'DON-001')->count());

        $this->post(route('donadores-externos.store'), [
            'codigo' => 'DON-001',
            'nombre' => 'Duplicado en la segunda cuenta',
        ])->assertSessionHasErrors('codigo');
    }

    private function animalData(string $arete): array
    {
        return [
            'especie' => 'Bovino',
            'raza' => 'Angus',
            'arete' => $arete,
            'sexo' => 'M',
        ];
    }
}
