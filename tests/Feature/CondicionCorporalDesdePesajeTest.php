<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\CondicionCorporal;
use App\Models\Pesaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La condición corporal capturada durante el pesaje debe reflejarse en el
 * historial de CC una sola vez, ligada al pesaje que la originó.
 */
class CondicionCorporalDesdePesajeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(): Animal
    {
        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-500',
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    public function test_capturing_body_condition_during_weighing_creates_the_record(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => 3.0,
            'metodo' => 'bascula',
            'responsable' => 'Juan Pastor',
        ])->assertSessionHasNoErrors();

        $cc = CondicionCorporal::first();

        $this->assertNotNull($cc, 'Debió crearse el registro de condición corporal.');
        $this->assertSame('3.0', (string) $cc->calificacion);
        $this->assertSame($animal->id, $cc->animal_id);
        $this->assertSame('Juan Pastor', $cc->responsable);
        $this->assertSame(Pesaje::class, $cc->origen_tipo);
        $this->assertSame(Pesaje::first()->id, $cc->origen_id);
    }

    public function test_a_weighing_without_body_condition_creates_no_record(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Pesaje::count());
        $this->assertSame(0, CondicionCorporal::count());
    }

    public function test_updating_the_weighing_updates_the_body_condition(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => 2.0,
        ])->assertSessionHasNoErrors();

        $this->put(route('pesajes.update', Pesaje::first()->id), [
            'fecha' => now()->toDateString(),
            'peso' => 44,
            'condicion_corporal' => 3.5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CondicionCorporal::count());
        $this->assertSame('3.5', (string) CondicionCorporal::first()->calificacion);
    }

    public function test_clearing_the_body_condition_removes_the_record(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => 2.0,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CondicionCorporal::count());

        $this->put(route('pesajes.update', Pesaje::first()->id), [
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, CondicionCorporal::count());
    }

    public function test_deleting_the_weighing_removes_its_body_condition(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => 3.0,
        ])->assertSessionHasNoErrors();

        $this->delete(route('pesajes.destroy', Pesaje::first()->id))->assertSessionHasNoErrors();

        $this->assertSame(0, CondicionCorporal::count(), 'No deben quedar registros huérfanos.');
    }

    public function test_body_condition_outside_the_scale_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'condicion_corporal' => 7,
        ])->assertSessionHasErrors('condicion_corporal');

        $this->assertSame(0, Pesaje::count());
    }

    public function test_an_unknown_weighing_method_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'peso' => 42.5,
            'metodo' => 'adivinanza',
        ])->assertSessionHasErrors('metodo');
    }

    public function test_a_future_weighing_date_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('pesajes.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->addWeek()->toDateString(),
            'peso' => 42.5,
        ])->assertSessionHasErrors('fecha');
    }
}
