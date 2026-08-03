<?php

namespace Tests\Feature;

use App\Models\ActividadTrabajador;
use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\PuestoTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\AnimalValuationService;
use App\Services\ManoObraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo de trabajadores: alta, estado, actividades, cálculo de mano de obra,
 * reparto entre ejemplares, integración con costos y valuación, y permisos.
 */
class TrabajadorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($user);

        return $user;
    }

    private function operador(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        return $user;
    }

    private function puesto(string $nombre = 'Ganadero'): PuestoTrabajador
    {
        // firstOrCreate: el catálogo es único por cuenta y varias pruebas
        // registran a más de una persona en el mismo puesto.
        return PuestoTrabajador::firstOrCreate(
            ['clave' => PuestoTrabajador::claveDesdeNombre($nombre)],
            ['nombre' => $nombre, 'area' => 'Manejo del rebaño', 'activo' => true]
        );
    }

    private function trabajador(array $overrides = []): Trabajador
    {
        return Trabajador::create(array_merge([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'puesto_id' => $this->puesto()->id,
            'costo_hora' => 90.00,
            'activo' => true,
        ], $overrides));
    }

    private function animal(string $arete = 'OV-001', array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'especie' => Animal::ESPECIE,
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subMonths(14)->toDateString(),
        ], $overrides));
    }

    private function manoObra(): ManoObraService
    {
        return app(ManoObraService::class);
    }

    // ─── Alta y edición ───────────────────────────────────────────────────

    public function test_an_admin_registers_a_worker(): void
    {
        $this->admin();
        $puesto = $this->puesto('Veterinario');

        $this->post(route('trabajadores.store'), [
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'puesto_id' => $puesto->id,
            'telefono' => '4771234567',
            'fecha_contratacion' => now()->subYear()->toDateString(),
            'costo_hora' => 120.50,
        ])->assertSessionHasNoErrors();

        $trabajador = Trabajador::first();

        $this->assertNotNull($trabajador);
        $this->assertSame('María López', $trabajador->nombre_completo);
        $this->assertSame('120.50', $trabajador->costo_hora);
        // El área se hereda del puesto cuando no se captura.
        $this->assertSame('Manejo del rebaño', $trabajador->area);
        $this->assertTrue($trabajador->activo);
    }

    public function test_the_name_and_position_are_required(): void
    {
        $this->admin();

        $this->post(route('trabajadores.store'), ['telefono' => '123'])
            ->assertSessionHasErrors(['nombre', 'puesto_id']);
    }

    public function test_negative_pay_rates_are_rejected(): void
    {
        $this->admin();

        $this->post(route('trabajadores.store'), [
            'nombre' => 'Pedro',
            'puesto_id' => $this->puesto()->id,
            'costo_hora' => -50,
            'costo_jornada' => -400,
            'sueldo' => -1,
        ])->assertSessionHasErrors(['costo_hora', 'costo_jornada', 'sueldo']);
    }

    public function test_a_worker_is_edited(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->put(route('trabajadores.update', $trabajador), [
            'nombre' => 'Juan Carlos',
            'apellido_paterno' => 'Pérez',
            'puesto_id' => $trabajador->puesto_id,
            'costo_hora' => 100,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Juan Carlos', $trabajador->fresh()->nombre);
    }

    public function test_one_account_cannot_belong_to_two_workers(): void
    {
        $admin = $this->admin();
        $this->trabajador(['user_id' => $admin->id]);

        $this->post(route('trabajadores.store'), [
            'nombre' => 'Otro',
            'puesto_id' => $this->puesto()->id,
            'user_id' => $admin->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_a_worker_without_an_account_is_valid(): void
    {
        $this->admin();

        $this->post(route('trabajadores.store'), [
            'nombre' => 'Sin cuenta',
            'puesto_id' => $this->puesto()->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull(Trabajador::first()->user_id);
    }

    // ─── Activación e inactivación ────────────────────────────────────────

    public function test_a_worker_is_deactivated_and_reactivated(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->patch(route('trabajadores.estado', $trabajador), [
            'activo' => false,
            'motivo_baja' => 'Fin de temporada',
        ])->assertSessionHasNoErrors();

        $trabajador->refresh();
        $this->assertFalse($trabajador->activo);
        $this->assertSame('Fin de temporada', $trabajador->motivo_baja);
        $this->assertNotNull($trabajador->fecha_baja);

        $this->patch(route('trabajadores.estado', $trabajador), ['activo' => true]);

        $trabajador->refresh();
        $this->assertTrue($trabajador->activo);
        $this->assertNull($trabajador->fecha_baja);
    }

    public function test_a_worker_with_history_is_never_deleted(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 2,
        ]);

        $this->delete(route('trabajadores.destroy', $trabajador))
            ->assertSessionHasErrors('trabajador');

        // Sigue existiendo: se conserva su historial.
        $this->assertDatabaseHas('trabajadores', ['id' => $trabajador->id]);
    }

    public function test_a_worker_without_history_can_be_removed(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->delete(route('trabajadores.destroy', $trabajador))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('trabajadores', ['id' => $trabajador->id]);
    }

    // ─── Cálculo de mano de obra ──────────────────────────────────────────

    public function test_hours_are_derived_from_the_clock(): void
    {
        $this->admin();

        $this->assertSame(2.5, $this->manoObra()->horasEntre('08:00', '10:30'));
        $this->assertNull($this->manoObra()->horasEntre('10:00', '08:00'));
        $this->assertNull($this->manoObra()->horasEntre('08:00', null));
    }

    public function test_the_cost_is_hours_times_the_hourly_rate(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 90]);

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'vacunacion',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'hora_inicio' => '08:00',
            'hora_fin' => '08:30',
        ]);

        // 0.5 h × $90.00 = $45.00
        $this->assertSame('0.50', $actividad->horas_trabajadas);
        $this->assertSame('45.00', $actividad->costo_total);
    }

    public function test_the_cost_is_days_times_the_daily_rate(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => null, 'costo_jornada' => 400]);

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'jornada',
            'jornadas' => 2.5,
        ]);

        // 2.5 jornadas × $400.00 = $1,000.00
        $this->assertSame('1000.00', $actividad->costo_total);
    }

    public function test_a_missing_rate_is_derived_from_the_other_one(): void
    {
        $this->admin();

        // Solo jornada: la tarifa por hora sale de una jornada de 8 horas.
        $porJornada = $this->trabajador(['costo_hora' => null, 'costo_jornada' => 400]);
        $this->assertSame(50.0, $porJornada->tarifaHora());

        // Solo hora: la jornada se compone de 8 horas.
        $porHora = $this->trabajador(['nombre' => 'Ana', 'costo_hora' => 60, 'costo_jornada' => null]);
        $this->assertSame(480.0, $porHora->tarifaJornada());
    }

    public function test_the_rate_captured_in_the_activity_wins_and_is_frozen(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 90]);

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'pesaje',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
            'costo_hora' => 150,
        ]);

        $this->assertSame('150.00', $actividad->costo_hora);
        $this->assertSame('150.00', $actividad->costo_total);

        // Subir el sueldo no reescribe lo ya trabajado.
        $trabajador->update(['costo_hora' => 200]);
        $this->assertSame('150.00', $actividad->fresh()->costo_total);
    }

    // ─── Reparto entre ejemplares ─────────────────────────────────────────

    public function test_the_cost_is_split_evenly_among_the_animals_in_a_lot(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 100]);

        $lote = Lote::create(['nombre' => 'Lote A', 'corral_potrero' => 'Norte']);

        foreach (['OV-1', 'OV-2', 'OV-3', 'OV-4'] as $arete) {
            $this->animal($arete, ['lote_id' => $lote->id]);
        }

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'desparasitacion',
            'lote_id' => $lote->id,
            'distribuir_entre_animales' => true,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 2,
        ]);

        // 2 h × $100 = $200.00 entre 4 ejemplares = $50.00 cada uno
        $this->assertSame('200.00', $actividad->costo_total);
        $this->assertSame(4, $actividad->animales_atendidos);
        $this->assertSame('50.00', $actividad->costo_por_animal);
        $this->assertStringContainsString('4 ejemplares', $actividad->metodo_distribucion);

        $costos = Costo::where('origen_id', $actividad->id)->get();

        $this->assertCount(4, $costos);
        $this->assertSame(200.0, round($costos->sum(fn ($c) => (float) $c->monto), 2));
    }

    public function test_splitting_never_loses_or_invents_cents(): void
    {
        $this->admin();

        // $100.00 entre 3 no da un importe exacto: 33.33 + 33.33 + 33.34
        $partes = $this->manoObra()->repartir(100.00, 3);

        $this->assertSame([33.33, 33.33, 33.34], $partes);
        $this->assertSame(100.0, round(array_sum($partes), 2));
    }

    public function test_an_empty_lot_says_so_instead_of_pretending_to_split(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();
        $lote = Lote::create(['nombre' => 'Lote vacío', 'corral_potrero' => 'Sur']);

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'limpieza',
            'lote_id' => $lote->id,
            'distribuir_entre_animales' => true,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ]);

        $this->assertSame(0, $actividad->animales_atendidos);
        $this->assertNull($actividad->costo_por_animal);
        $this->assertStringContainsString('sin repartir', $actividad->metodo_distribucion);

        // El costo existe, pero a nivel de lote.
        $costo = Costo::where('origen_id', $actividad->id)->sole();
        $this->assertNull($costo->animal_id);
        $this->assertSame($lote->id, $costo->lote_id);
    }

    // ─── Integración con costos ───────────────────────────────────────────

    public function test_an_activity_generates_its_cost_in_the_costs_module(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 90]);
        $animal = $this->animal();

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'vacunacion',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 0.5,
        ]);

        $costo = Costo::where('origen_tipo', ActividadTrabajador::class)
            ->where('origen_id', $actividad->id)
            ->sole();

        $this->assertSame('mano_obra', $costo->categoria);
        $this->assertSame('45.00', $costo->monto);
        $this->assertSame($animal->id, $costo->animal_id);
        $this->assertSame($trabajador->id, $costo->trabajador_id);
        $this->assertStringContainsString('Juan Pérez', $costo->concepto);
        // El desglose explica de dónde salió el importe.
        $this->assertStringContainsString('0.5 hora(s)', $costo->descripcion);
    }

    public function test_correcting_an_activity_does_not_leave_the_old_cost_behind(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 100]);
        $animal = $this->animal();

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'revision',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 3,
        ]);

        $this->assertSame(300.0, (float) Costo::where('animal_id', $animal->id)->sum('monto'));

        // Se corrigen las horas: eran 2, no 3.
        $this->manoObra()->actualizar($actividad, [
            'tipo_actividad' => 'revision',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 2,
        ]);

        // Un solo costo y con el importe corregido, no dos sumando.
        $this->assertSame(1, Costo::where('animal_id', $animal->id)->count());
        $this->assertSame(200.0, (float) Costo::where('animal_id', $animal->id)->sum('monto'));
    }

    public function test_the_same_activity_is_not_saved_twice_by_a_double_click(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();
        $animal = $this->animal();

        $datos = [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'pesaje',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ];

        $this->post(route('actividades-trabajador.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('actividades-trabajador.store'), $datos)->assertSessionHasErrors('tipo_actividad');

        $this->assertSame(1, ActividadTrabajador::count());
    }

    public function test_deleting_an_activity_also_removes_its_cost(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();
        $animal = $this->animal();

        $actividad = $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'pesaje',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ]);

        $this->delete(route('actividades-trabajador.destroy', $actividad))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Costo::where('origen_id', $actividad->id)->count());
    }

    // ─── Integración con la valuación ─────────────────────────────────────

    public function test_labour_reaches_the_valuation_of_the_ewe_exactly_once(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 90]);
        $animal = $this->animal();

        $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'vacunacion',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 0.5,
        ]);

        $calculo = app(AnimalValuationService::class)->calcular($animal->fresh());

        $this->assertSame(45.0, $calculo['buckets']['costo_mano_obra']);
        $this->assertSame(45.0, $calculo['costo_total_produccion']);

        // Una sola línea de desglose, y nombra al trabajador.
        $lineas = collect($calculo['detalles'])->where('categoria', 'mano_obra');

        $this->assertCount(1, $lineas);
        $this->assertStringContainsString('Juan Pérez', $lineas->first()['observaciones']);
    }

    public function test_the_split_share_is_what_reaches_each_animal(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 100]);
        $lote = Lote::create(['nombre' => 'Lote B', 'corral_potrero' => 'Este']);

        $primero = $this->animal('OV-A', ['lote_id' => $lote->id]);
        $this->animal('OV-B', ['lote_id' => $lote->id]);

        $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'desparasitacion',
            'lote_id' => $lote->id,
            'distribuir_entre_animales' => true,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ]);

        $calculo = app(AnimalValuationService::class)->calcular($primero->fresh());

        // $100.00 entre 2 = $50.00 para este ejemplar, no los $100 completos.
        $this->assertSame(50.0, $calculo['buckets']['costo_mano_obra']);

        $linea = collect($calculo['detalles'])->firstWhere('categoria', 'mano_obra');
        $this->assertStringContainsString('repartido', strtolower($linea['observaciones']));
    }

    public function test_a_new_labour_cost_updates_the_active_quotation(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['costo_hora' => 100]);
        $animal = $this->animal();

        $valuacion = app(AnimalValuationService::class)->guardar(
            $animal,
            ['porcentaje_margen_genetico' => 0],
            'creacion',
            'Cotización inicial'
        );

        $valuacion->update(['estado' => AnimalValuation::ESTADO_ACTIVA]);

        $this->manoObra()->registrar($trabajador, [
            'tipo_actividad' => 'atencion_parto',
            'animal_id' => $animal->id,
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 2,
        ]);

        $this->assertSame(
            200.0,
            (float) $animal->valuaciones()->latest('id')->first()->costo_mano_obra
        );
    }

    // ─── Reglas de la actividad ───────────────────────────────────────────

    public function test_an_inactive_worker_receives_no_new_activities(): void
    {
        $this->admin();
        $trabajador = $this->trabajador(['activo' => false]);

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ])->assertSessionHasErrors('trabajador_id');

        $this->assertSame(0, ActividadTrabajador::count());
    }

    public function test_an_activity_cannot_belong_to_a_worker_that_does_not_exist(): void
    {
        $this->admin();

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => 9999,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ])->assertSessionHasErrors('trabajador_id');
    }

    public function test_the_end_time_must_come_after_the_start_time(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'hora_inicio' => '10:00',
            'hora_fin' => '08:00',
        ])->assertSessionHasErrors('hora_fin');
    }

    public function test_negative_hours_are_rejected(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => -3,
        ])->assertSessionHasErrors('horas_trabajadas');
    }

    public function test_time_is_required_when_paying_by_the_hour(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
        ])->assertSessionHasErrors('horas_trabajadas');
    }

    public function test_a_future_activity_is_rejected(): void
    {
        $this->admin();
        $trabajador = $this->trabajador();

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'limpieza',
            'fecha' => now()->addWeek()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ])->assertSessionHasErrors('fecha');
    }

    // ─── Permisos sobre los datos reservados ──────────────────────────────

    public function test_the_owner_of_the_account_does_receive_the_reserved_data(): void
    {
        $this->operador();   // cuenta sin rol admin: es dueña de su rancho
        $this->trabajador(['sueldo' => 9000, 'curp' => 'PEPJ800101HDFRRN01']);

        $props = $this->get(route('trabajadores.index'))
            ->assertOk()
            ->getOriginalContent()->getData()['page']['props'];

        // Su propia gente la ve completa: es información de su rancho.
        $this->assertArrayHasKey('sueldo', $props['trabajadores']['data'][0]);
        $this->assertArrayHasKey('curp', $props['trabajadores']['data'][0]);
        $this->assertTrue($props['permisos']['verSensibles']);
    }

    public function test_the_reserved_fields_are_withheld_from_someone_who_is_not_the_owner(): void
    {
        $duena = $this->operador();
        $trabajador = $this->trabajador([
            'curp' => 'PEPJ800101HDFRRN01',
            'sueldo' => 9000,
            'direccion' => 'Calle Falsa 123',
            'contacto_emergencia' => 'Ana Pérez',
        ]);

        $ajena = $this->operador();   // otra cuenta

        // La política deniega los datos reservados a quien no es dueño.
        $this->assertFalse($ajena->can('verDatosSensibles', $trabajador));
        $this->assertFalse($ajena->can('update', $trabajador));
        $this->assertTrue($duena->can('verDatosSensibles', $trabajador));

        // Y lo que se envía al navegador se recorta, no se enmascara.
        $recortado = $this->get(route('trabajadores.index'))
            ->getOriginalContent()->getData()['page']['props'];

        $this->assertSame([], $recortado['trabajadores']['data']);
    }

    // ─── Aislamiento entre cuentas ────────────────────────────────────────

    public function test_workers_are_private_to_each_account(): void
    {
        $this->operador();
        $this->trabajador(['nombre' => 'De la cuenta A']);

        $this->operador();   // otra cuenta

        $this->assertSame(0, Trabajador::count());
    }

    public function test_a_worker_from_another_account_cannot_be_reached(): void
    {
        $this->operador();
        $trabajador = $this->trabajador();

        $this->operador();   // otra cuenta

        $this->get(route('trabajadores.show', $trabajador))->assertNotFound();
        $this->put(route('trabajadores.update', $trabajador), [
            'nombre' => 'Intruso',
            'puesto_id' => $trabajador->puesto_id,
        ])->assertNotFound();
        $this->patch(route('trabajadores.estado', $trabajador), ['activo' => false])
            ->assertNotFound();

        $this->assertSame('Juan', $trabajador->fresh()->nombre);
        $this->assertTrue($trabajador->fresh()->activo);
    }

    public function test_an_activity_cannot_be_assigned_to_another_accounts_worker(): void
    {
        $this->operador();
        $trabajador = $this->trabajador();

        $this->operador();   // otra cuenta

        $this->post(route('actividades-trabajador.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_actividad' => 'alimentacion',
            'fecha' => now()->toDateString(),
            'modalidad_pago' => 'hora',
            'horas_trabajadas' => 1,
        ])->assertSessionHasErrors('trabajador_id');

        $this->assertSame(0, ActividadTrabajador::count());
    }
}
