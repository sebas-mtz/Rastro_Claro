<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\User;
use App\Services\CostoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CostoTest extends TestCase
{
    use RefreshDatabase;

    private function costoData(array $overrides = []): array
    {
        return array_merge([
            'concepto' => 'Compra de alimento balanceado',
            'categoria' => 'alimentacion',
            'tipo_costo' => 'directo',
            'monto' => 150.50,
            'fecha' => '2026-07-01',
        ], $overrides);
    }

    public function test_a_cost_can_be_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(1, Costo::count());

        $costo = Costo::first();
        $this->assertSame('Compra de alimento balanceado', $costo->concepto);
        $this->assertSame(150.5, (float) $costo->monto);
        $this->assertSame($user->id, $costo->user_id);
        $this->assertSame($user->id, $costo->owner_id);
    }

    public function test_amount_must_be_a_positive_number(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData(['monto' => 0]))
            ->assertSessionHasErrors('monto');

        $this->post(route('costos.store'), $this->costoData(['monto' => -25]))
            ->assertSessionHasErrors('monto');

        $this->post(route('costos.store'), $this->costoData(['monto' => '']))
            ->assertSessionHasErrors('monto');

        $this->assertSame(0, Costo::count());
    }

    public function test_concept_is_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData(['concepto' => '']))
            ->assertSessionHasErrors('concepto');
    }

    public function test_category_must_be_one_of_the_known_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData(['categoria' => 'inexistente']))
            ->assertSessionHasErrors('categoria');
    }

    public function test_an_accidental_duplicate_submitted_right_after_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData())->assertSessionHasNoErrors();
        $this->assertSame(1, Costo::count());

        // Mismo usuario, mismos datos, segundos después: doble clic accidental.
        $this->post(route('costos.store'), $this->costoData())->assertSessionHasErrors('concepto');
        $this->assertSame(1, Costo::count());
    }

    public function test_a_cost_can_be_updated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData());
        $costo = Costo::first();

        $this->put(route('costos.update', $costo->id), $this->costoData([
            'concepto' => 'Alimento actualizado',
            'monto' => 200,
        ]))->assertSessionHasNoErrors();

        $costo->refresh();
        $this->assertSame('Alimento actualizado', $costo->concepto);
        $this->assertSame(200.0, (float) $costo->monto);
    }

    public function test_a_cost_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('costos.store'), $this->costoData());
        $costo = Costo::first();

        $this->delete(route('costos.destroy', $costo->id))
            ->assertSessionHas('success');

        $this->assertSame(0, Costo::count());
    }

    public function test_costs_can_be_filtered_by_category_animal_lot_and_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $animal = Animal::create([
            'especie' => 'Ovino', 'raza' => 'Dorper', 'arete' => 'OV-001', 'sexo' => 'F',
        ]);
        $lote = Lote::create([
            'nombre' => 'Lote Norte', 'corral_potrero' => 'Norte', 'responsable_id' => $user->id,
        ]);

        Costo::create(array_merge($this->costoData([
            'concepto' => 'Vacuna clostridial',
            'categoria' => 'vacunas',
            'fecha' => '2026-06-15',
            'animal_id' => $animal->id,
        ]), ['user_id' => $user->id]));

        Costo::create(array_merge($this->costoData([
            'concepto' => 'Transporte de insumos',
            'categoria' => 'transporte',
            'fecha' => '2026-07-10',
            'lote_id' => $lote->id,
        ]), ['user_id' => $user->id]));

        $this->assertSame(1, Costo::query()->categoria('vacunas')->count());
        $this->assertSame(1, Costo::query()->deAnimal($animal->id)->count());
        $this->assertSame(1, Costo::query()->deLote($lote->id)->count());
        $this->assertSame(1, Costo::query()->entreFechas('2026-07-01', '2026-07-31')->count());
        $this->assertSame(2, Costo::query()->entreFechas('2026-06-01', '2026-07-31')->count());
    }

    public function test_totals_are_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Costo::create(array_merge($this->costoData(['categoria' => 'alimentacion', 'monto' => 100]), ['user_id' => $user->id]));
        Costo::create(array_merge($this->costoData(['categoria' => 'transporte', 'monto' => 50, 'concepto' => 'Flete']), ['user_id' => $user->id]));

        $this->get(route('costos.index'))->assertOk();

        $totales = app(CostoService::class)->totales(new Request());

        $this->assertSame(150.0, $totales['total_general']);
        $this->assertSame(2, $totales['cantidad_registros']);
        $this->assertSame(100.0, (float) $totales['total_por_categoria']['alimentacion']);
        $this->assertSame(50.0, (float) $totales['total_por_categoria']['transporte']);
    }

    public function test_costs_are_isolated_per_account(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser);
        Costo::create(array_merge($this->costoData(), ['user_id' => $firstUser->id]));

        $this->actingAs($secondUser);
        $this->assertSame(0, Costo::count());

        $this->put(route('costos.update', 1), $this->costoData())->assertNotFound();
    }
}
