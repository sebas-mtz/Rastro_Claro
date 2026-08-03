<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Baja;
use App\Models\CondicionCorporal;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Pesaje;
use App\Models\User;
use App\Services\BajaService;
use App\Services\DesarrolloCorporalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2: ciclo de vida del ejemplar — bajas del rebaño, movimientos entre
 * lotes, desarrollo corporal y condición corporal.
 */
class CicloVidaOvinoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-' . fake()->unique()->numberBetween(1000, 9999),
            'sexo' => 'F',
            'fecha_nac' => now()->subYears(2)->toDateString(),
        ], $overrides));
    }

    private function lote(User $user, string $nombre = 'Lote Norte', ?string $tipo = null): Lote
    {
        return Lote::create([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'corral_potrero' => 'Norte',
            'responsable_id' => $user->id,
        ]);
    }

    // ─── Bajas del rebaño ─────────────────────────────────────────────────

    public function test_registering_an_exit_deactivates_the_animal_but_keeps_its_history(): void
    {
        $this->usuario();
        $animal = $this->animal();

        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now()->subMonth(), 'peso' => 45]);

        $this->post(route('bajas.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'tipo_salida' => Baja::FALLECIMIENTO,
            'causa' => 'Neumonía',
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        $this->assertFalse($animal->activo);
        $this->assertNotNull($animal->fecha_baja);

        // El historial se conserva: el pesaje sigue ahí.
        $this->assertSame(1, Pesaje::where('animal_id', $animal->id)->count());
        $this->assertSame('Neumonía', $animal->baja->causa);
    }

    public function test_an_animal_given_exit_no_longer_counts_as_active(): void
    {
        $this->usuario();
        $activo = $this->animal();
        $salido = $this->animal();

        $this->post(route('bajas.store'), [
            'animal_id' => $salido->id,
            'fecha' => now()->toDateString(),
            'tipo_salida' => Baja::VENTA,
            'precio_salida' => 4200,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Animal::count());
        $this->assertSame(1, Animal::activo()->count());
        $this->assertSame([$activo->id], Animal::activo()->pluck('id')->all());
    }

    public function test_an_animal_cannot_be_given_exit_twice(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $datos = [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'tipo_salida' => Baja::VENTA,
        ];

        $this->post(route('bajas.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('bajas.store'), $datos)->assertSessionHasErrors('animal_id');

        $this->assertSame(1, Baja::count());
    }

    public function test_the_exit_date_cannot_precede_birth(): void
    {
        $this->usuario();
        $animal = $this->animal(['fecha_nac' => now()->subYear()->toDateString()]);

        $this->post(route('bajas.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->subYears(3)->toDateString(),
            'tipo_salida' => Baja::FALLECIMIENTO,
        ])->assertSessionHasErrors('fecha');
    }

    public function test_an_unknown_exit_type_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('bajas.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'tipo_salida' => 'jubilacion',
        ])->assertSessionHasErrors('tipo_salida');
    }

    public function test_reverting_an_exit_returns_the_animal_to_the_flock(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('bajas.store'), [
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'tipo_salida' => Baja::EXTRAVIO,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($animal->fresh()->activo);

        $this->delete(route('bajas.destroy', Baja::first()->id))->assertSessionHasNoErrors();

        $this->assertTrue($animal->fresh()->activo);
        $this->assertNull($animal->fresh()->fecha_baja);
        $this->assertSame(0, Baja::count());
    }

    public function test_mortality_rate_is_null_without_inventory(): void
    {
        $this->usuario();

        $this->assertNull(app(BajaService::class)->indicadores()['porcentaje_mortalidad']);
    }

    public function test_mortality_rate_is_calculated_over_the_historical_flock(): void
    {
        $this->usuario();
        $this->animal();
        $this->animal();
        $muerto = $this->animal();

        app(BajaService::class)->registrar($muerto, [
            'fecha' => now()->toDateString(),
            'tipo_salida' => Baja::FALLECIMIENTO,
        ]);

        $indicadores = app(BajaService::class)->indicadores();

        // 1 fallecimiento sobre 3 ejemplares históricos = 33.33 %
        $this->assertSame(2, $indicadores['activos']);
        $this->assertSame(1, $indicadores['fallecimientos']);
        $this->assertSame(33.33, $indicadores['porcentaje_mortalidad']);
    }

    // ─── Movimientos entre lotes ──────────────────────────────────────────

    public function test_changing_lot_records_a_movement_without_losing_the_previous_one(): void
    {
        $user = $this->usuario();
        $origen = $this->lote($user, 'Lote Origen');
        $destino = $this->lote($user, 'Lote Destino', 'gestantes');

        $animal = $this->animal(['lote_id' => $origen->id]);

        $animal->update(['lote_id' => $destino->id]);

        $movimiento = MovimientoLote::first();

        $this->assertNotNull($movimiento, 'El cambio de lote debe quedar registrado.');
        $this->assertSame($origen->id, $movimiento->lote_anterior_id);
        $this->assertSame($destino->id, $movimiento->lote_nuevo_id);
        $this->assertSame($destino->id, $animal->fresh()->lote_id);
        $this->assertSame('De Lote Origen a Lote Destino', $movimiento->descripcion);
    }

    public function test_saving_without_changing_the_lot_records_no_movement(): void
    {
        $user = $this->usuario();
        $lote = $this->lote($user);
        $animal = $this->animal(['lote_id' => $lote->id]);

        $animal->update(['peso' => 52]);

        $this->assertSame(0, MovimientoLote::count());
    }

    public function test_successive_movements_keep_the_full_trail(): void
    {
        $user = $this->usuario();
        $a = $this->lote($user, 'Cuarentena', 'cuarentena');
        $b = $this->lote($user, 'Desarrollo', 'borregas_desarrollo');
        $c = $this->lote($user, 'Reproductoras', 'reproductoras');

        $animal = $this->animal(['lote_id' => $a->id]);
        $animal->update(['lote_id' => $b->id]);
        $animal->fresh()->update(['lote_id' => $c->id]);

        $this->assertSame(2, MovimientoLote::count());

        $recorrido = MovimientoLote::orderBy('id')->get();
        $this->assertSame($a->id, $recorrido[0]->lote_anterior_id);
        $this->assertSame($b->id, $recorrido[0]->lote_nuevo_id);
        $this->assertSame($b->id, $recorrido[1]->lote_anterior_id);
        $this->assertSame($c->id, $recorrido[1]->lote_nuevo_id);
    }

    public function test_the_reason_for_a_lot_move_is_recorded(): void
    {
        $user = $this->usuario();
        $origen = $this->lote($user, 'Desarrollo');
        $destino = $this->lote($user, 'Reproductoras', 'reproductoras');

        $animal = $this->animal(['lote_id' => $origen->id]);

        $this->put(route('animales.update', $animal->id), [
            'arete' => $animal->arete,
            'sexo' => 'F',
            'lote_id' => $destino->id,
            'motivo_movimiento_lote' => 'Alcanzó edad reproductiva',
        ])->assertSessionHasNoErrors();

        $movimiento = MovimientoLote::first();

        $this->assertNotNull($movimiento);
        $this->assertSame('Alcanzó edad reproductiva', $movimiento->motivo);
        $this->assertSame($user->id, $movimiento->responsable_id);

        // El motivo no se guarda como columna del ejemplar: solo viaja al historial.
        $this->assertArrayNotHasKey('motivo_movimiento_lote', $animal->fresh()->getAttributes());
    }

    public function test_a_lot_can_be_typed_for_ovine_management(): void
    {
        $user = $this->usuario();
        $lote = $this->lote($user, 'Maternidad', 'lactantes');

        $this->assertSame('Borregas lactantes', $lote->tipo_legible);
        $this->assertArrayHasKey('sementales', Lote::TIPOS);
    }

    // ─── Desarrollo corporal ──────────────────────────────────────────────

    public function test_daily_gain_is_calculated_between_weighings(): void
    {
        $this->usuario();
        $animal = $this->animal();

        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now()->subDays(30), 'peso' => 30]);
        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now(), 'peso' => 39]);

        $resumen = app(DesarrolloCorporalService::class)->resumen($animal);

        $this->assertSame(9.0, $resumen['ganancia_acumulada']);
        $this->assertSame(0.3, $resumen['ganancia_diaria_promedio']);   // 9 kg / 30 días
        $this->assertSame(30, $resumen['dias_periodo']);
    }

    public function test_two_weighings_on_the_same_day_do_not_divide_by_zero(): void
    {
        $this->usuario();
        $animal = $this->animal();

        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now(), 'peso' => 30]);
        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now(), 'peso' => 31]);

        $resumen = app(DesarrolloCorporalService::class)->resumen($animal);

        $this->assertSame(1.0, $resumen['ganancia_acumulada']);
        $this->assertNull($resumen['ganancia_diaria_promedio']);
        $this->assertStringContainsString('misma fecha', $resumen['aviso']);
    }

    public function test_a_single_weighing_cannot_produce_a_gain(): void
    {
        $this->usuario();
        $animal = $this->animal();

        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now(), 'peso' => 40]);

        $resumen = app(DesarrolloCorporalService::class)->resumen($animal);

        $this->assertNull($resumen['ganancia_acumulada']);
        $this->assertStringContainsString('segundo pesaje', $resumen['aviso']);
    }

    public function test_an_animal_without_weighings_is_reported_as_such(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $resumen = app(DesarrolloCorporalService::class)->resumen($animal);

        $this->assertSame(0, $resumen['total_pesajes']);
        $this->assertNull($resumen['peso_actual']);
        $this->assertStringContainsString('todavía no tiene pesajes', $resumen['aviso']);
    }

    public function test_the_history_includes_age_and_gain_per_weighing(): void
    {
        $this->usuario();
        $animal = $this->animal(['fecha_nac' => now()->subDays(100)->toDateString()]);

        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now()->subDays(20), 'peso' => 20, 'metodo' => 'bascula']);
        Pesaje::create(['animal_id' => $animal->id, 'fecha' => now(), 'peso' => 26]);

        $historial = app(DesarrolloCorporalService::class)->historial($animal);

        $this->assertCount(2, $historial);
        $this->assertNull($historial[0]['ganancia_total'], 'El primer pesaje no tiene referencia previa.');
        $this->assertSame('Báscula', $historial[0]['metodo']);
        $this->assertSame(80, $historial[0]['edad_dias']);
        $this->assertSame(6.0, $historial[1]['ganancia_total']);
        $this->assertSame(0.3, $historial[1]['ganancia_diaria']);
    }

    // ─── Condición corporal ───────────────────────────────────────────────

    public function test_body_condition_uses_the_documented_scale(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $cc = CondicionCorporal::create([
            'animal_id' => $animal->id,
            'fecha' => now(),
            'calificacion' => 3.0,
        ]);

        $this->assertStringContainsString('Óptima', $cc->descripcion_escala);
        $this->assertStringContainsString('rango óptimo', $cc->sugerencia);
    }

    public function test_a_low_body_condition_suggests_reviewing_feeding(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $cc = CondicionCorporal::create([
            'animal_id' => $animal->id,
            'fecha' => now(),
            'calificacion' => 1.5,
        ]);

        $this->assertStringContainsString('Por debajo del rango óptimo', $cc->sugerencia);
    }

    public function test_body_condition_history_is_kept_per_animal(): void
    {
        $this->usuario();
        $animal = $this->animal();

        CondicionCorporal::create(['animal_id' => $animal->id, 'fecha' => now()->subMonths(2), 'calificacion' => 2.0]);
        CondicionCorporal::create(['animal_id' => $animal->id, 'fecha' => now(), 'calificacion' => 3.0]);

        $this->assertSame(2, $animal->condicionesCorporales()->count());
        // El accessor ordena de la más reciente a la más antigua.
        $this->assertSame('3.0', (string) $animal->condicionesCorporales()->first()->calificacion);
    }
}
