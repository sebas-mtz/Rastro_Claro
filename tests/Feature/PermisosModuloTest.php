<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\PuestoTrabajador;
use App\Models\User;
use App\Support\ModuloSistema as M;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Permisos por puesto dentro de un rancho.
 *
 * Lo que se vigila aquí es que el límite viva en el backend: cada caso entra
 * por la ruta real, escribiendo la dirección, no mirando banderas de la
 * interfaz.
 */
class PermisosModuloTest extends TestCase
{
    use RefreshDatabase;

    private function dueno(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Crea el puesto en el rancho indicado con sus permisos por omisión. */
    private function puesto(User $dueno, string $clave): PuestoTrabajador
    {
        return PuestoTrabajador::withoutGlobalScope('owner')->create([
            'owner_id' => $dueno->id,
            'clave' => $clave,
            'nombre' => ucfirst(str_replace('_', ' ', $clave)),
            'permisos' => PuestoTrabajador::permisosPorDefecto($clave),
            'activo' => true,
        ]);
    }

    private function empleado(User $dueno, string $clave, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $dueno->id,
            'puesto_id' => $this->puesto($dueno, $clave)->id,
        ], $extra));
    }

    private function animalDe(User $usuario): Animal
    {
        $this->actingAs($usuario);

        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-' . fake()->unique()->numberBetween(1000, 9999),
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    // ─── Quien manda no se limita ─────────────────────────────────────────

    public function test_the_ranch_owner_has_every_module(): void
    {
        $dueno = $this->dueno();

        $this->assertSame(count(M::claves()), count($dueno->modulosVisibles()));
        $this->assertTrue($dueno->puede(M::COSTOS, M::ELIMINAR));

        $this->actingAs($dueno);
        $this->get(route('costos.index'))->assertOk();
    }

    public function test_a_super_admin_has_every_module(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->assertTrue($super->puede(M::VALUACION, M::EDITAR));
    }

    // ─── El dinero viene apagado ──────────────────────────────────────────

    public function test_no_default_job_title_grants_the_money_modules(): void
    {
        $economicosPorPuesto = [];

        foreach (array_merge(PuestoTrabajador::BASE, PuestoTrabajador::HEREDADOS) as $puesto) {
            $permisos = PuestoTrabajador::permisosPorDefecto($puesto['clave']);

            foreach (M::ECONOMICOS as $modulo) {
                if (! empty($permisos[$modulo])) {
                    $economicosPorPuesto[] = "{$puesto['clave']} → {$modulo}";
                }
            }
        }

        // Las dos únicas excepciones son deliberadas y están documentadas:
        // el gerente dirige, y vender es el oficio del responsable de ventas.
        $this->assertSame(
            ['gerente → costos', 'gerente → valuacion', 'gerente → ventas', 'responsable_ventas → ventas'],
            $economicosPorPuesto
        );
    }

    public function test_a_vet_cannot_reach_costs_or_valuations(): void
    {
        $dueno = $this->dueno();
        $animal = $this->animalDe($dueno);
        $veterinario = $this->empleado($dueno, 'veterinario');

        $this->actingAs($veterinario);

        $this->assertFalse($veterinario->puede(M::COSTOS));

        $this->get(route('costos.index'))->assertForbidden();
        $this->get(route('valuaciones.show', $animal->id))->assertForbidden();
    }

    public function test_the_vet_does_reach_their_own_module(): void
    {
        $dueno = $this->dueno();
        $veterinario = $this->empleado($dueno, 'veterinario');

        $this->actingAs($veterinario);

        $this->get(route('salud.index'))->assertOk();
        $this->assertTrue($veterinario->puede(M::SALUD, M::ELIMINAR));
    }

    public function test_a_feeder_reaches_feeding_but_not_health(): void
    {
        $dueno = $this->dueno();
        $alimentador = $this->empleado($dueno, 'alimentador');

        $this->actingAs($alimentador);

        $this->get(route('alimentacion.index'))->assertOk();
        $this->get(route('salud.index'))->assertForbidden();
        $this->get(route('costos.index'))->assertForbidden();
    }

    // ─── Consultar no es escribir ─────────────────────────────────────────

    public function test_read_only_access_does_not_allow_writing(): void
    {
        $dueno = $this->dueno();
        // El transportista ve los ejemplares, pero no los da de alta.
        $transportista = $this->empleado($dueno, 'transportista');

        $this->actingAs($transportista);

        $this->assertTrue($transportista->puede(M::ANIMALES, M::VER));
        $this->assertFalse($transportista->puede(M::ANIMALES, M::REGISTRAR));

        $this->get(route('animales.index'))->assertOk();
        $this->post(route('animales.store'), ['arete' => 'OV-999', 'sexo' => 'F'])
            ->assertForbidden();

        $this->assertSame(0, Animal::withoutGlobalScope('owner')->count());
    }

    /** El mensaje distingue "no entras" de "entras pero no puedes hacer eso". */
    public function test_the_denial_explains_which_of_the_two_limits_applies(): void
    {
        $dueno = $this->dueno();
        $transportista = $this->empleado($dueno, 'transportista');

        $this->actingAs($transportista);

        // Ve los ejemplares pero no los registra: el aviso lo dice así.
        $sinRegistrar = $this->post(route('animales.store'), []);
        $sinRegistrar->assertForbidden();
        $this->assertStringContainsString('no registrar', $this->mensajeDeError($sinRegistrar));

        // De Costos no pasa ni a la puerta.
        $sinModulo = $this->get(route('costos.index'));
        $sinModulo->assertForbidden();
        $this->assertStringContainsString(
            'no tiene acceso al módulo Costos',
            $this->mensajeDeError($sinModulo)
        );
    }

    /**
     * Texto que la pantalla de error recibe.
     *
     * Se lee de las props de Inertia y no del HTML: el marcado lleva el JSON
     * escapado, así que los acentos no aparecen tal cual en la respuesta.
     */
    private function mensajeDeError($respuesta): string
    {
        return (string) ($respuesta->getOriginalContent()->getData()['page']['props']['mensaje'] ?? '');
    }

    // ─── Excepciones por persona ──────────────────────────────────────────

    public function test_an_individual_grant_opens_a_module(): void
    {
        $dueno = $this->dueno();

        $veterinario = $this->empleado($dueno, 'veterinario', [
            'permisos_extra' => ['conceder' => [M::COSTOS => [M::VER, M::REGISTRAR]]],
        ]);

        $this->actingAs($veterinario);

        $this->assertTrue($veterinario->puede(M::COSTOS, M::REGISTRAR));
        $this->assertFalse($veterinario->puede(M::COSTOS, M::ELIMINAR));

        $this->get(route('costos.index'))->assertOk();
    }

    public function test_an_individual_revocation_closes_a_module(): void
    {
        $dueno = $this->dueno();

        $veterinario = $this->empleado($dueno, 'veterinario', [
            'permisos_extra' => ['revocar' => [M::SALUD => M::TODAS]],
        ]);

        $this->actingAs($veterinario);

        $this->assertFalse($veterinario->puede(M::SALUD));
        $this->get(route('salud.index'))->assertForbidden();
    }

    public function test_a_partial_revocation_keeps_the_rest(): void
    {
        $dueno = $this->dueno();

        $veterinario = $this->empleado($dueno, 'veterinario', [
            'permisos_extra' => ['revocar' => [M::SALUD => [M::ELIMINAR]]],
        ]);

        $this->assertTrue($veterinario->puede(M::SALUD, M::EDITAR));
        $this->assertFalse($veterinario->puede(M::SALUD, M::ELIMINAR));
    }

    public function test_an_invented_module_grants_nothing(): void
    {
        $dueno = $this->dueno();

        $empleado = $this->empleado($dueno, 'transportista', [
            'permisos_extra' => ['conceder' => ['tesoreria_secreta' => M::TODAS]],
        ]);

        $this->assertArrayNotHasKey('tesoreria_secreta', $empleado->permisos());
    }

    // ─── Casos límite ─────────────────────────────────────────────────────

    public function test_an_employee_without_a_job_title_has_no_modules(): void
    {
        $dueno = $this->dueno();

        $empleado = User::factory()->create([
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $dueno->id,
        ]);

        $this->assertSame([], $empleado->permisos());

        $this->actingAs($empleado);

        // El panel sigue abierto: no se le deja sin entrada al sistema.
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('animales.index'))->assertForbidden();
    }

    public function test_a_deactivated_account_keeps_no_permissions(): void
    {
        $dueno = $this->dueno();
        $empleado = $this->empleado($dueno, 'veterinario', ['activo' => false]);

        $this->assertSame([], $empleado->permisos());
    }

    public function test_an_admin_inside_someone_elses_ranch_runs_the_operation(): void
    {
        $dueno = $this->dueno();

        $encargado = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'cuenta_id' => $dueno->id,
        ]);

        $this->assertTrue($encargado->puede(M::SALUD, M::ELIMINAR));
        $this->assertFalse($encargado->esDuenoDeCuenta());

        // Pero sigue sin poder administrar cuentas ni tocar las fórmulas.
        $this->actingAs($encargado);
        $this->get(route('admin.usuarios.index'))->assertForbidden();
        $this->put(route('valuaciones.configuracion'), ['valores' => []])->assertForbidden();
    }

    // ─── Cobertura del mapa ───────────────────────────────────────────────

    /**
     * Toda ruta debe pertenecer a un módulo o estar declarada como libre.
     *
     * El middleware deja pasar lo que no sabe clasificar, que es lo correcto
     * para el panel o el perfil pero sería un agujero silencioso si mañana
     * alguien agrega un módulo y olvida registrarlo. Esta prueba convierte
     * ese olvido en un fallo visible.
     */
    public function test_every_route_is_either_mapped_to_a_module_or_declared_free(): void
    {
        $sinClasificar = [];

        foreach (Route::getRoutes() as $ruta) {
            $nombre = $ruta->getName();
            $uri = $ruta->uri();

            if (! $nombre || str_starts_with($uri, 'api/') || str_starts_with($uri, '_')) {
                continue;
            }

            if (M::desdeRuta($nombre) !== null) {
                continue;
            }

            // Sin módulo: solo vale si está en la lista de rutas libres.
            $libre = false;

            foreach (M::SIEMPRE_DISPONIBLES as $prefijo) {
                if ($nombre === $prefijo || str_starts_with($nombre, $prefijo . '.')) {
                    $libre = true;
                    break;
                }
            }

            if (! $libre) {
                $sinClasificar[] = $nombre . '  (/' . $uri . ')';
            }
        }

        $this->assertSame(
            [],
            $sinClasificar,
            "Estas rutas no pertenecen a ningún módulo ni están declaradas como libres.\n"
            . "Agrégalas a ModuloSistema::RUTAS o a SIEMPRE_DISPONIBLES:\n"
            . implode("\n", $sinClasificar)
        );
    }

    // ─── Administración de permisos ───────────────────────────────────────

    public function test_only_a_super_admin_manages_permissions(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]));
        $this->get(route('admin.permisos.index'))->assertOk();

        $this->actingAs($this->dueno());
        $this->get(route('admin.permisos.index'))->assertForbidden();
    }

    public function test_changing_a_job_titles_permissions_takes_effect_at_once(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $puesto = $this->puesto($super, 'veterinario');

        $empleado = User::factory()->create([
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $super->id,
            'puesto_id' => $puesto->id,
        ]);

        $this->assertFalse($empleado->puede(M::COSTOS));

        $this->actingAs($super);
        $this->put(route('admin.permisos.puesto', $puesto->id), [
            'permisos' => [M::SALUD => [M::VER], M::COSTOS => [M::VER]],
        ])->assertSessionHasNoErrors();

        // Se relee: los permisos se resuelven una vez por instancia.
        $this->assertTrue(User::find($empleado->id)->puede(M::COSTOS));
    }

    public function test_permission_changes_are_recorded_in_the_audit_log(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $puesto = $this->puesto($super, 'alimentador');

        $this->actingAs($super);
        $this->put(route('admin.permisos.puesto', $puesto->id), [
            'permisos' => [M::ALIMENTACION => M::TODAS],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('auditorias', [
            'accion' => \App\Models\Auditoria::PERMISOS_CAMBIADOS,
            'entidad_id' => $puesto->id,
        ]);
    }

    /**
     * Un puesto de otro rancho ni siquiera se encuentra.
     *
     * PuestoTrabajador participa del aislamiento por cuenta, así que el enlace
     * de la ruta no lo resuelve y la respuesta es 404, no 403. Es la respuesta
     * correcta: un 403 confirmaría que ese registro existe.
     */
    public function test_a_job_title_from_another_ranch_is_not_even_found(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $puestoAjeno = $this->puesto($this->dueno(), 'veterinario');

        $this->actingAs($super);

        $this->put(route('admin.permisos.puesto', $puestoAjeno->id), [
            'permisos' => [M::COSTOS => M::TODAS],
        ])->assertNotFound();

        $this->assertSame(
            PuestoTrabajador::permisosPorDefecto('veterinario'),
            PuestoTrabajador::withoutGlobalScope('owner')->find($puestoAjeno->id)->permisos
        );
    }

    /**
     * Con las personas hace falta la comprobación explícita: la tabla `users`
     * no participa del aislamiento por cuenta —si lo hiciera nadie podría
     * iniciar sesión—, así que el enlace de la ruta sí resuelve una cuenta
     * ajena y hay que rechazarla a mano.
     */
    public function test_a_person_from_another_ranch_cannot_be_reconfigured(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $otroRancho = $this->dueno();
        $ajeno = User::factory()->create([
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $otroRancho->id,
        ]);

        $this->actingAs($super);

        $this->put(route('admin.permisos.persona', $ajeno->id), [
            'conceder' => [M::COSTOS => M::TODAS],
            'revocar' => [],
        ])->assertForbidden();

        $this->assertNull($ajeno->fresh()->permisos_extra);
    }

    public function test_an_unknown_action_is_rejected(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $puesto = $this->puesto($super, 'veterinario');

        $this->actingAs($super);

        $this->put(route('admin.permisos.puesto', $puesto->id), [
            'permisos' => [M::SALUD => ['borrar_todo']],
        ])->assertSessionHasErrors();
    }

    public function test_the_interface_receives_the_resolved_permissions(): void
    {
        $dueno = $this->dueno();
        $veterinario = $this->empleado($dueno, 'veterinario');

        $this->actingAs($veterinario);

        $props = $this->get(route('dashboard'))->getOriginalContent()->getData()['page']['props'];

        $this->assertContains(M::SALUD, $props['auth']['user']['modulos']);
        $this->assertNotContains(M::COSTOS, $props['auth']['user']['modulos']);
        $this->assertFalse($props['auth']['user']['es_dueno']);
    }
}
