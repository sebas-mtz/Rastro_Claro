<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\Raza;
use App\Models\Trabajador;
use App\Models\User;
use App\Support\ModuloSistema as M;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Varias personas trabajando sobre un mismo rancho.
 *
 * Estas pruebas vigilan el cimiento del sistema: el filtro que decide qué
 * datos ve cada quien. Si algo aquí se rompe, dos ranchos se ven entre sí.
 *
 * El caso peligroso no es el listado —ahí el scope global salta solo— sino el
 * acceso por id directo, la validación `exists` y el sello de `owner_id` al
 * crear. Los tres tienen prueba propia.
 */
class CuentaCompartidaTest extends TestCase
{
    use RefreshDatabase;

    /** Dueño de un rancho: se apunta a sí mismo. */
    private function dueno(string $nombre = 'Dueño'): User
    {
        return User::factory()->create(['name' => $nombre, 'role' => User::ROLE_ADMIN]);
    }

    /**
     * Empleado dentro del rancho de otro.
     *
     * Estas pruebas vigilan el AISLAMIENTO por rancho, no los permisos por
     * puesto —eso es asunto de PermisosModuloTest—, así que los módulos que
     * cada caso necesita se conceden aquí de forma explícita.
     *
     * @param array<string, array<string>> $permisos ['costos' => ['ver','registrar']]
     */
    private function empleado(User $dueno, string $nombre = 'Empleado', array $permisos = []): User
    {
        return User::factory()->create([
            'name' => $nombre,
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $dueno->id,
            'permisos_extra' => $permisos ? ['conceder' => $permisos] : null,
        ]);
    }

    /** Empleado con permiso para capturar en los módulos indicados. */
    private function empleadoQueCaptura(User $dueno, array $modulos, string $nombre = 'Empleado'): User
    {
        return $this->empleado(
            $dueno,
            $nombre,
            array_fill_keys($modulos, [M::VER, M::REGISTRAR, M::EDITAR])
        );
    }

    private function animalDe(User $usuario, string $arete): Animal
    {
        $this->actingAs($usuario);

        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    // ─── La cuenta como concepto ──────────────────────────────────────────

    public function test_a_new_account_owns_itself(): void
    {
        $usuario = User::factory()->create();

        $this->assertSame($usuario->id, $usuario->fresh()->cuenta_id);
        $this->assertTrue($usuario->fresh()->esDuenoDeCuenta());
    }

    public function test_an_employee_belongs_to_their_employers_ranch(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleado($dueno);

        $this->assertSame($dueno->id, $empleado->cuentaId());
        $this->assertFalse($empleado->esDuenoDeCuenta());
        $this->assertTrue($empleado->comparteCuentaCon($dueno));
    }

    public function test_two_owners_do_not_share_a_ranch(): void
    {
        $this->assertFalse($this->dueno('A')->comparteCuentaCon($this->dueno('B')));
    }

    // ─── Lo que se ve ─────────────────────────────────────────────────────

    public function test_an_employee_sees_the_flock_of_their_ranch(): void
    {
        $dueno = $this->dueno();
        $this->animalDe($dueno, 'OV-100');
        $this->animalDe($dueno, 'OV-101');

        $this->actingAs($this->empleado($dueno));

        $this->assertSame(2, Animal::count());
        $this->assertNotNull(Animal::where('arete', 'OV-100')->first());
    }

    public function test_an_employee_never_sees_another_ranch(): void
    {
        $mio = $this->dueno('Mío');
        $ajeno = $this->dueno('Ajeno');

        $this->animalDe($mio, 'OV-MIO');
        $this->animalDe($ajeno, 'OV-AJENO');

        $this->actingAs($this->empleado($mio));

        $this->assertSame(1, Animal::count());
        $this->assertNull(Animal::where('arete', 'OV-AJENO')->first());
    }

    /**
     * El caso peligroso de verdad: pedir un registro ajeno por su id.
     *
     * El listado se filtra solo; el acceso directo es donde se cuela la fuga
     * si el scope no está bien puesto.
     */
    public function test_asking_for_another_ranchs_animal_by_id_returns_not_found(): void
    {
        $ajeno = $this->dueno('Ajeno');
        $intruso = $this->animalDe($ajeno, 'OV-AJENO');

        $empleado = $this->empleado($this->dueno('Mío'));
        $this->actingAs($empleado);

        $this->assertNull(Animal::find($intruso->id));
        $this->get(route('animales.show', $intruso->id))->assertNotFound();
        $this->get(route('valuaciones.show', $intruso->id))->assertNotFound();
    }

    /**
     * Las reglas `exists` no pasan por el scope de Eloquent: las filtra
     * TenantPresenceVerifier. Sin él, se puede colgar un registro propio de un
     * animal ajeno mandando su id en el formulario.
     */
    public function test_a_form_cannot_reference_another_ranchs_animal(): void
    {
        $ajeno = $this->dueno('Ajeno');
        $intruso = $this->animalDe($ajeno, 'OV-AJENO');

        $this->actingAs($this->empleadoQueCaptura($this->dueno('Mío'), [M::COSTOS]));

        $this->post(route('costos.store'), [
            'concepto' => 'Intento de fuga',
            'categoria' => 'medicamentos',
            'tipo_costo' => 'directo',
            'monto' => 100,
            'fecha' => now()->toDateString(),
            'animal_id' => $intruso->id,
        ])->assertSessionHasErrors('animal_id');

        $this->assertSame(0, Costo::withoutGlobalScope('owner')->count());
    }

    // ─── Lo que se escribe ────────────────────────────────────────────────

    /**
     * Al capturar, el registro queda a nombre del RANCHO y la persona queda
     * anotada aparte. Es la distinción central de todo este cambio.
     */
    public function test_what_an_employee_records_belongs_to_the_ranch(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleadoQueCaptura($dueno, [M::COSTOS]);

        $animal = $this->animalDe($dueno, 'OV-200');

        $this->actingAs($empleado);

        $this->post(route('costos.store'), [
            'concepto' => 'Vacuna clostridiasis',
            'categoria' => 'vacunas',
            'tipo_costo' => 'directo',
            'monto' => 95,
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
        ])->assertSessionHasNoErrors();

        $costo = Costo::withoutGlobalScope('owner')->first();

        // El rancho es el dueño; quien lo capturó fue el empleado.
        $this->assertSame($dueno->id, (int) $costo->owner_id);
        $this->assertSame($empleado->id, (int) $costo->user_id);
    }

    public function test_the_owner_sees_what_the_employee_recorded(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleadoQueCaptura($dueno, [M::COSTOS]);

        $animal = $this->animalDe($dueno, 'OV-300');

        $this->actingAs($empleado);
        $this->post(route('costos.store'), [
            'concepto' => 'Desparasitación',
            'categoria' => 'medicamentos',
            'tipo_costo' => 'directo',
            'monto' => 60,
            'fecha' => now()->toDateString(),
            'animal_id' => $animal->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($dueno);

        $this->assertSame(1, Costo::count());
        $this->assertSame('Desparasitación', Costo::first()->concepto);
    }

    public function test_an_animal_created_by_an_employee_belongs_to_the_ranch(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleado($dueno);

        $animal = $this->animalDe($empleado, 'OV-400');

        $this->assertSame($dueno->id, (int) $animal->owner_id);

        // Y el dueño lo ve como suyo.
        $this->actingAs($dueno);
        $this->assertNotNull(Animal::where('arete', 'OV-400')->first());
    }

    // ─── Catálogos y registros compartidos ────────────────────────────────

    public function test_the_breed_catalog_is_shared_within_the_ranch(): void
    {
        $dueno = $this->dueno();

        $this->actingAs($dueno);
        $dorper = Raza::create(['nombre' => 'Dorper', 'activo' => true]);

        $this->actingAs($this->empleado($dueno));

        $this->assertNotNull(Raza::find($dorper->id));
        $this->assertSame(1, Raza::count());
    }

    public function test_ranch_workers_are_visible_to_every_member(): void
    {
        $dueno = $this->dueno();

        $this->actingAs($dueno);
        $trabajador = Trabajador::create(['nombre' => 'Juan', 'apellido_paterno' => 'Pérez']);

        $this->assertSame($dueno->id, (int) $trabajador->owner_id);

        $this->actingAs($this->empleado($dueno));
        $this->assertNotNull(Trabajador::find($trabajador->id));
    }

    /**
     * El desplegable de responsables listaba únicamente al propio usuario,
     * porque cada cuenta era una sola persona.
     */
    public function test_the_lot_manager_list_covers_the_whole_ranch(): void
    {
        $dueno = $this->dueno('Patrón');
        $empleado = $this->empleado($dueno, 'Pastor');
        $ajeno = $this->dueno('De otro rancho');

        $this->actingAs($dueno);

        $props = $this->get(route('lotes.index'))
            ->getOriginalContent()->getData()['page']['props'];

        $ids = collect($props['usuarios'])->pluck('id')->all();

        $this->assertContains($dueno->id, $ids);
        $this->assertContains($empleado->id, $ids);
        $this->assertNotContains($ajeno->id, $ids);
    }

    // ─── Indicadores y consultas directas ─────────────────────────────────

    /**
     * El panel usa consultas SQL directas, donde el scope de Eloquent no
     * interviene y el filtro va escrito a mano. Es fácil que se olvide.
     */
    public function test_the_indicators_only_count_the_ranchs_own_money(): void
    {
        $mio = $this->dueno('Mío');
        $ajeno = $this->dueno('Ajeno');

        $animalMio = $this->animalDe($mio, 'OV-M1');
        $this->animalDe($mio, 'OV-M2');
        $animalAjeno = $this->animalDe($ajeno, 'OV-A1');

        // Un gasto en cada rancho, de montos distintos para poder distinguirlos.
        $this->actingAs($mio);
        Costo::create([
            'concepto' => 'Alimento propio', 'categoria' => 'alimentacion',
            'tipo_costo' => 'directo', 'monto' => 500,
            'fecha' => now()->toDateString(), 'animal_id' => $animalMio->id,
            'user_id' => $mio->id,
        ]);

        $this->actingAs($ajeno);
        Costo::create([
            'concepto' => 'Alimento ajeno', 'categoria' => 'alimentacion',
            'tipo_costo' => 'directo', 'monto' => 9999,
            'fecha' => now()->toDateString(), 'animal_id' => $animalAjeno->id,
            'user_id' => $ajeno->id,
        ]);

        $this->actingAs($this->empleado($mio));

        $economico = app(\App\Services\IndicadoresOvinosService::class)->economico();

        $this->assertSame(2, Animal::activo()->count());
        // Solo el gasto del rancho propio: si el filtro fallara aparecerían 10499.
        $this->assertSame(500.0, (float) $economico['costos_totales']);
    }

    public function test_a_lot_created_by_an_employee_stays_in_the_ranch(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleadoQueCaptura($dueno, [M::LOTES, M::ANIMALES]);

        $this->actingAs($empleado);

        $this->post(route('lotes.store'), [
            'nombre' => 'Lote del pastor',
            'corral_potrero' => 'Norte',
            'animal' => [
                'arete_inicio' => 500,
                'arete_fin' => 501,
                'sexo' => 'F',
            ],
        ])->assertSessionHasNoErrors();

        $lote = Lote::withoutGlobalScope('owner')->first();

        $this->assertSame($dueno->id, (int) $lote->owner_id);

        $this->actingAs($dueno);
        $this->assertSame(1, Lote::count());
    }
}
