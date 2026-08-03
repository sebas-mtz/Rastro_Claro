<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\PuestoTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Jerarquía de roles y administración de cuentas.
 *
 * El punto central de estas pruebas es que la protección viva en el backend:
 * cada caso entra por la ruta real, como lo haría alguien escribiendo la
 * dirección a mano, no comprobando banderas de la interfaz.
 */
class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_SUPER_ADMIN,
            'activo' => true,
        ], $extra));
    }

    private function admin(array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_ADMIN], $extra));
    }

    private function trabajador(array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_TRABAJADOR], $extra));
    }

    // ─── Acceso al módulo ─────────────────────────────────────────────────

    public function test_a_super_admin_can_list_users(): void
    {
        $this->actingAs($this->superAdmin());
        $this->trabajador(['name' => 'Juan Pérez']);

        $this->get(route('admin.usuarios.index'))->assertOk();
    }

    public function test_an_admin_cannot_list_users(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.usuarios.index'))->assertForbidden();
    }

    public function test_a_worker_cannot_enter_the_users_module(): void
    {
        $this->actingAs($this->trabajador());

        $this->get(route('admin.usuarios.index'))->assertForbidden();
    }

    /**
     * Escribir la dirección a mano no cambia nada: la protección está en la
     * ruta, no en el enlace del menú.
     */
    public function test_typing_the_url_directly_still_returns_forbidden(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/auditoria')->assertForbidden();
    }

    public function test_every_user_route_is_closed_to_an_admin(): void
    {
        $admin = $this->admin();
        $otro = $this->trabajador();
        $this->actingAs($admin);

        $this->post(route('admin.usuarios.store'), [])->assertForbidden();
        $this->put(route('admin.usuarios.update', $otro->id), [])->assertForbidden();
        $this->patch(route('admin.usuarios.estado', $otro->id), ['activo' => false])->assertForbidden();
        $this->patch(route('admin.usuarios.password', $otro->id), ['password' => 'Secreta#2026'])->assertForbidden();
        $this->delete(route('admin.usuarios.destroy', $otro->id))->assertForbidden();

        // Ninguna de las llamadas anteriores debió tocar nada.
        $this->assertTrue($otro->fresh()->activo);
    }

    // ─── Alta y edición ───────────────────────────────────────────────────

    public function test_a_super_admin_can_create_a_user(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('admin.usuarios.store'), [
            'name' => 'María Hernández',
            'email' => 'maria@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
            'puesto' => 'veterinario',
        ])->assertSessionHasNoErrors();

        $creado = User::where('email', 'maria@rancho.test')->first();

        $this->assertNotNull($creado);
        $this->assertSame(User::ROLE_TRABAJADOR, $creado->role);
        $this->assertTrue($creado->activo);
    }

    public function test_the_password_is_stored_hashed_and_never_in_plain_text(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Pedro Ramírez',
            'email' => 'pedro@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
        ])->assertSessionHasNoErrors();

        $creado = User::where('email', 'pedro@rancho.test')->first();

        $this->assertNotSame('Secreta#2026', $creado->password);
        $this->assertTrue(Hash::check('Secreta#2026', $creado->password));

        // La contraseña no viaja al navegador ni siquiera cifrada.
        $this->assertArrayNotHasKey('password', $creado->toArray());
    }

    public function test_a_super_admin_can_link_a_user_to_a_ranch_worker(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super);

        $trabajador = Trabajador::create([
            'owner_id' => $super->id,
            'nombre' => 'Luis',
            'apellido_paterno' => 'Morales',
        ]);

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Luis Morales',
            'email' => 'luis@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
            'trabajador_id' => $trabajador->id,
        ])->assertSessionHasNoErrors();

        $cuenta = User::where('email', 'luis@rancho.test')->first();

        $this->assertSame($cuenta->id, $trabajador->fresh()->user_id);
    }

    // ─── Asignación a un rancho ───────────────────────────────────────────

    public function test_a_new_account_can_be_created_inside_an_existing_ranch(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super);

        $puesto = PuestoTrabajador::withoutGlobalScope('owner')->create([
            'owner_id' => $super->id,
            'clave' => 'veterinario',
            'nombre' => 'Veterinario',
            'permisos' => PuestoTrabajador::permisosPorDefecto('veterinario'),
            'activo' => true,
        ]);

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Doctora Ruiz',
            'email' => 'ruiz@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $super->id,
            'puesto_id' => $puesto->id,
        ])->assertSessionHasNoErrors();

        $creada = User::where('email', 'ruiz@rancho.test')->first();

        $this->assertSame($super->id, $creada->cuentaId());
        $this->assertFalse($creada->esDuenoDeCuenta());
        $this->assertSame($puesto->id, $creada->puesto_id);
        // La columna histórica queda coherente con el catálogo.
        $this->assertSame('veterinario', $creada->puesto);
        $this->assertTrue($creada->puede('salud'));
    }

    public function test_an_account_without_a_ranch_owns_its_own(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Rancho Nuevo',
            'email' => 'nuevo@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'nuevo@rancho.test')->first()->esDuenoDeCuenta());
    }

    /** Solo se puede pertenecer al rancho de un dueño, nunca al de un empleado. */
    public function test_an_employee_cannot_be_used_as_a_ranch(): void
    {
        $super = $this->superAdmin();
        $empleado = User::factory()->create(['cuenta_id' => $super->id]);

        $this->actingAs($super);

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Encadenado',
            'email' => 'cadena@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $empleado->id,
        ])->assertSessionHasErrors('cuenta_id');

        $this->assertNull(User::where('email', 'cadena@rancho.test')->first());
    }

    public function test_a_job_title_from_another_ranch_is_rejected(): void
    {
        $super = $this->superAdmin();
        $otroRancho = $this->trabajador();

        $puestoAjeno = PuestoTrabajador::withoutGlobalScope('owner')->create([
            'owner_id' => $otroRancho->id,
            'clave' => 'gerente',
            'nombre' => 'Gerente',
            'permisos' => PuestoTrabajador::permisosPorDefecto('gerente'),
            'activo' => true,
        ]);

        $this->actingAs($super);

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Intruso',
            'email' => 'intruso@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $super->id,
            'puesto_id' => $puestoAjeno->id,
        ])->assertSessionHasErrors('puesto_id');
    }

    /**
     * La protección importante de esta pantalla.
     *
     * Los datos se sellan con el id del rancho. Mover a un dueño con registros
     * al rancho de otro los dejaría fuera del alcance de todos: no se borran,
     * pero desaparecen de la vista, que en la práctica es peor.
     */
    public function test_an_owner_with_records_cannot_be_moved_into_another_ranch(): void
    {
        $super = $this->superAdmin();
        $dueno = $this->trabajador();

        $this->actingAs($dueno);
        Trabajador::create(['nombre' => 'Su', 'apellido_paterno' => 'Gente']);

        $this->actingAs($super);

        $this->put(route('admin.usuarios.update', $dueno->id), [
            'name' => $dueno->name,
            'email' => $dueno->email,
            'role' => $dueno->role,
            'cuenta_id' => $super->id,
        ])->assertSessionHasErrors('cuenta_id');

        $this->assertTrue($dueno->fresh()->esDuenoDeCuenta());
    }

    public function test_an_owner_with_staff_cannot_be_moved_either(): void
    {
        $super = $this->superAdmin();
        $patron = $this->trabajador();
        User::factory()->create(['cuenta_id' => $patron->id]);

        $this->actingAs($super);

        $this->put(route('admin.usuarios.update', $patron->id), [
            'name' => $patron->name,
            'email' => $patron->email,
            'role' => $patron->role,
            'cuenta_id' => $super->id,
        ])->assertSessionHasErrors('cuenta_id');

        $this->assertTrue($patron->fresh()->esDuenoDeCuenta());
    }

    public function test_an_employee_without_records_can_be_moved(): void
    {
        $super = $this->superAdmin();
        $otroRancho = $this->trabajador();
        $empleado = User::factory()->create(['cuenta_id' => $otroRancho->id]);

        $this->actingAs($super);

        $this->put(route('admin.usuarios.update', $empleado->id), [
            'name' => $empleado->name,
            'email' => $empleado->email,
            'role' => $empleado->role,
            'cuenta_id' => $super->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($super->id, $empleado->fresh()->cuentaId());

        // El cambio queda en la bitácora: altera por completo qué datos ve.
        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::PERMISOS_CAMBIADOS,
            'afectado_id' => $empleado->id,
        ]);
    }

    public function test_nobody_can_change_their_own_ranch(): void
    {
        $super = $this->superAdmin();
        $otroRancho = $this->trabajador();
        $this->superAdmin();   // respaldo

        $this->actingAs($super);

        $this->put(route('admin.usuarios.update', $super->id), [
            'name' => $super->name,
            'email' => $super->email,
            'role' => $super->role,
            'cuenta_id' => $otroRancho->id,
        ])->assertSessionHasErrors('cuenta_id');

        $this->assertTrue($super->fresh()->esDuenoDeCuenta());
    }

    // ─── Cambio de rol ────────────────────────────────────────────────────

    public function test_a_super_admin_can_change_a_role(): void
    {
        $this->actingAs($this->superAdmin());
        $usuario = $this->trabajador();

        $this->put(route('admin.usuarios.update', $usuario->id), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::ROLE_ADMIN, $usuario->fresh()->role);
    }

    public function test_an_admin_cannot_promote_themselves_to_super_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->put(route('admin.usuarios.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $super = $this->superAdmin();
        // Un segundo superadministrador, para que la negativa venga de la
        // regla de "no tocarse a sí mismo" y no de la del último activo.
        $this->superAdmin();
        $this->actingAs($super);

        $this->put(route('admin.usuarios.update', $super->id), [
            'name' => $super->name,
            'email' => $super->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_SUPER_ADMIN, $super->fresh()->role);
    }

    // ─── Protección del último superadministrador ─────────────────────────

    /**
     * Cuando queda un solo superadministrador activo, el sistema no puede
     * quedarse sin él por ninguna vía.
     *
     * La única cuenta capaz de administrar es la suya —las demás reciben 403,
     * y una cuenta desactivada también—, y nadie puede modificarse a sí mismo.
     * Entre las dos reglas el camino queda cerrado, y esta prueba recorre las
     * dos puertas por HTTP.
     */
    public function test_the_last_active_super_admin_cannot_lose_their_own_access(): void
    {
        $unico = $this->superAdmin();
        // Un segundo superadministrador desactivado no sirve de respaldo.
        $suplente = $this->superAdmin(['activo' => false]);

        // Puerta 1: el único activo no puede quitarse el rol ni desactivarse.
        $this->actingAs($unico);

        $this->put(route('admin.usuarios.update', $unico->id), [
            'name' => $unico->name,
            'email' => $unico->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasErrors('role');

        $this->patch(route('admin.usuarios.estado', $unico->id), ['activo' => false])
            ->assertForbidden();

        // Puerta 2: la cuenta desactivada no puede actuar sobre él.
        $this->actingAs($suplente);

        $this->put(route('admin.usuarios.update', $unico->id), [
            'name' => $unico->name,
            'email' => $unico->email,
            'role' => User::ROLE_ADMIN,
        ])->assertForbidden();

        $this->patch(route('admin.usuarios.estado', $unico->id), ['activo' => false])
            ->assertForbidden();

        $unico->refresh();
        $this->assertTrue($unico->isSuperAdmin());
        $this->assertTrue($unico->activo);
    }

    /**
     * La comprobación que respalda lo anterior dentro del controlador.
     *
     * Es una segunda capa: hoy no se llega a ella por HTTP porque la regla de
     * no modificarse a sí mismo cierra el paso antes. Se prueba directamente
     * para que siga cubierta si esa regla cambia algún día.
     */
    public function test_the_last_active_super_admin_is_detected(): void
    {
        $primero = $this->superAdmin();

        $this->assertTrue($primero->esUltimoSuperAdminActivo());

        // Un segundo desactivado no cuenta como respaldo.
        $segundo = $this->superAdmin(['activo' => false]);
        $this->assertTrue($primero->fresh()->esUltimoSuperAdminActivo());

        // Al activarlo, ninguno de los dos es ya el último.
        $segundo->update(['activo' => true]);
        $this->assertFalse($primero->fresh()->esUltimoSuperAdminActivo());
        $this->assertFalse($segundo->fresh()->esUltimoSuperAdminActivo());

        // Un administrador nunca es "el último superadministrador".
        $this->assertFalse($this->admin()->esUltimoSuperAdminActivo());
    }

    /**
     * Con dos superadministradores activos sí se puede degradar a uno: la
     * protección es sobre el último que queda, no sobre el rol en general.
     */
    public function test_a_super_admin_can_be_demoted_while_another_one_remains(): void
    {
        $actor = $this->superAdmin();
        $otro = $this->superAdmin();

        $this->actingAs($actor);

        $this->put(route('admin.usuarios.update', $otro->id), [
            'name' => $otro->name,
            'email' => $otro->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::ROLE_ADMIN, $otro->fresh()->role);
        $this->assertTrue(User::haySuperAdminActivo());
    }

    public function test_a_super_admin_cannot_deactivate_or_delete_their_own_account(): void
    {
        $super = $this->superAdmin();
        $this->superAdmin();   // respaldo, para aislar la regla probada
        $this->actingAs($super);

        $this->patch(route('admin.usuarios.estado', $super->id), ['activo' => false])
            ->assertForbidden();
        $this->delete(route('admin.usuarios.destroy', $super->id))->assertForbidden();

        $this->assertTrue($super->fresh()->activo);
        $this->assertNotNull(User::find($super->id));
    }

    // ─── Estado y contraseñas ─────────────────────────────────────────────

    public function test_a_super_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $this->actingAs($this->superAdmin());
        $usuario = $this->trabajador();

        $this->patch(route('admin.usuarios.estado', $usuario->id), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($usuario->fresh()->activo);

        $this->patch(route('admin.usuarios.estado', $usuario->id), ['activo' => true])
            ->assertSessionHasNoErrors();
        $this->assertTrue($usuario->fresh()->activo);
    }

    public function test_a_super_admin_can_reset_another_users_password(): void
    {
        $this->actingAs($this->superAdmin());
        $usuario = $this->trabajador();
        $anterior = $usuario->password;

        $this->patch(route('admin.usuarios.password', $usuario->id), [
            'password' => 'NuevaClave#2026',
            'password_confirmation' => 'NuevaClave#2026',
        ])->assertSessionHasNoErrors();

        $usuario->refresh();

        $this->assertNotSame($anterior, $usuario->password);
        $this->assertTrue(Hash::check('NuevaClave#2026', $usuario->password));

        // La bitácora deja constancia del hecho, nunca del valor.
        $movimiento = Auditoria::where('accion', Auditoria::PASSWORD_RESTABLECIDA)->first();
        $this->assertNotNull($movimiento);
        $this->assertNull($movimiento->valor_nuevo);
    }

    // ─── Borrado con registros relacionados ───────────────────────────────

    public function test_a_user_with_related_records_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin());

        $usuario = $this->trabajador();

        Trabajador::create([
            'owner_id' => $usuario->id,
            'nombre' => 'Registro',
            'apellido_paterno' => 'Asociado',
        ]);

        $this->delete(route('admin.usuarios.destroy', $usuario->id))
            ->assertSessionHasErrors('usuario');

        $this->assertNotNull(User::find($usuario->id));
    }

    // ─── Auditoría ────────────────────────────────────────────────────────

    public function test_a_role_change_is_recorded_in_the_audit_log(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super);

        $usuario = $this->trabajador();

        $this->put(route('admin.usuarios.update', $usuario->id), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasNoErrors();

        $movimiento = Auditoria::where('accion', Auditoria::ROL_CAMBIADO)->first();

        $this->assertNotNull($movimiento);
        $this->assertSame($super->id, $movimiento->usuario_id);
        $this->assertSame($usuario->id, $movimiento->afectado_id);
        $this->assertSame(User::ROLE_TRABAJADOR, $movimiento->valor_anterior['role']);
        $this->assertSame(User::ROLE_ADMIN, $movimiento->valor_nuevo['role']);
    }

    public function test_creating_and_deactivating_are_also_recorded(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana@rancho.test',
            'password' => 'Secreta#2026',
            'password_confirmation' => 'Secreta#2026',
            'role' => User::ROLE_TRABAJADOR,
        ])->assertSessionHasNoErrors();

        $creado = User::where('email', 'ana@rancho.test')->first();

        $this->patch(route('admin.usuarios.estado', $creado->id), [
            'activo' => false,
            'motivo' => 'Terminó la temporada.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::USUARIO_CREADO]);

        $baja = Auditoria::where('accion', Auditoria::USUARIO_DESACTIVADO)->first();
        $this->assertSame('Terminó la temporada.', $baja->descripcion);
    }

    public function test_only_a_super_admin_can_read_the_audit_log(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get(route('admin.auditoria.index'))->assertOk();

        $this->actingAs($this->admin());
        $this->get(route('admin.auditoria.index'))->assertForbidden();

        $this->actingAs($this->trabajador());
        $this->get(route('admin.auditoria.index'))->assertForbidden();
    }

    /** La bitácora no tiene ruta de escritura ni de borrado, a propósito. */
    public function test_the_audit_log_has_no_delete_route(): void
    {
        $rutas = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->map(fn ($r) => $r->methods()[0] . ' ' . $r->uri())
            ->filter(fn ($r) => str_contains($r, 'auditoria'));

        $this->assertSame(['GET admin/auditoria'], $rutas->values()->all());
    }

    // ─── Configuración crítica ────────────────────────────────────────────

    public function test_only_a_super_admin_can_change_the_valuation_settings(): void
    {
        $valores = ['valores' => [['clave' => 'plus_cargada_semental_registro', 'valor' => 9000]]];

        $this->actingAs($this->trabajador());
        $this->put(route('valuaciones.configuracion'), $valores)->assertForbidden();

        $this->actingAs($this->admin());
        $this->put(route('valuaciones.configuracion'), $valores)->assertForbidden();

        $this->actingAs($this->superAdmin());
        $this->put(route('valuaciones.configuracion'), $valores)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::VALOR_VALUACION_MODIFICADO,
        ]);
    }

    // ─── Banderas enviadas a la interfaz ──────────────────────────────────

    public function test_the_interface_receives_the_permission_flags(): void
    {
        $this->actingAs($this->superAdmin());
        $props = $this->get(route('dashboard'))->getOriginalContent()->getData()['page']['props'];

        $this->assertTrue($props['auth']['user']['es_super_admin']);
        $this->assertSame('Superadministrador', $props['auth']['user']['rol_legible']);

        $this->actingAs($this->admin());
        $props = $this->get(route('dashboard'))->getOriginalContent()->getData()['page']['props'];

        $this->assertFalse($props['auth']['user']['es_super_admin']);
        $this->assertSame('Administrador', $props['auth']['user']['rol_legible']);
    }

    // ─── Comando de consola ───────────────────────────────────────────────

    public function test_the_artisan_command_promotes_an_existing_user(): void
    {
        $usuario = $this->trabajador(['email' => 'dueno@rancho.test']);

        $this->artisan('user:make-super-admin', ['email' => 'dueno@rancho.test', '--force' => true])
            ->assertSuccessful();

        $this->assertTrue($usuario->fresh()->isSuperAdmin());

        // El cambio queda en la bitácora aunque venga de la consola.
        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ROL_CAMBIADO,
            'afectado_id' => $usuario->id,
        ]);
    }

    public function test_the_artisan_command_never_creates_a_user(): void
    {
        $antes = User::count();

        $this->artisan('user:make-super-admin', ['email' => 'nadie@rancho.test', '--force' => true])
            ->assertFailed();

        $this->assertSame($antes, User::count());
    }
}
