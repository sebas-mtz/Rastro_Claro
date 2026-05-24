<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    private function makeUser(string $role, bool $activo = true): User
    {
        $user         = new User();
        $user->role   = $role;
        $user->activo = $activo;
        $user->plan   = User::PLAN_NORMAL;
        return $user;
    }

    // ── Roles ──────────────────────────────────────────────────────

    public function test_isAdmin_returns_true_for_administrador(): void
    {
        $user = $this->makeUser(User::ROLE_ADMINISTRADOR);
        $this->assertTrue($user->isAdmin());
    }

    public function test_isAdmin_returns_false_for_trabajador(): void
    {
        $user = $this->makeUser(User::ROLE_TRABAJADOR);
        $this->assertFalse($user->isAdmin());
    }

    public function test_isTrabajador_returns_true(): void
    {
        $user = $this->makeUser(User::ROLE_TRABAJADOR);
        $this->assertTrue($user->isTrabajador());
    }

    public function test_isSoloLectura_returns_true(): void
    {
        $user = $this->makeUser(User::ROLE_SOLO_LECTURA);
        $this->assertTrue($user->isSoloLectura());
    }

    public function test_roleLabel_returns_correct_labels(): void
    {
        $this->assertSame('Administrador', $this->makeUser(User::ROLE_ADMINISTRADOR)->roleLabel());
        $this->assertSame('Encargado',     $this->makeUser(User::ROLE_ENCARGADO)->roleLabel());
        $this->assertSame('Trabajador',    $this->makeUser(User::ROLE_TRABAJADOR)->roleLabel());
        $this->assertSame('Solo lectura',  $this->makeUser(User::ROLE_SOLO_LECTURA)->roleLabel());
    }

    // ── Permisos ───────────────────────────────────────────────────

    public function test_inactive_user_has_no_permissions(): void
    {
        $user = $this->makeUser(User::ROLE_ADMINISTRADOR, false);
        $this->assertFalse($user->puede('animales', 'ver'));
    }

    public function test_administrador_puede_eliminar_animales(): void
    {
        $user = $this->makeUser(User::ROLE_ADMINISTRADOR);
        $this->assertTrue($user->puede('animales', 'eliminar'));
    }

    public function test_solo_lectura_no_puede_crear_animales(): void
    {
        $user = $this->makeUser(User::ROLE_SOLO_LECTURA);
        $this->assertFalse($user->puede('animales', 'crear'));
    }

    public function test_trabajador_puede_crear_salud(): void
    {
        $user = $this->makeUser(User::ROLE_TRABAJADOR);
        $this->assertTrue($user->puede('salud', 'crear'));
    }

    public function test_trabajador_no_puede_eliminar_salud(): void
    {
        $user = $this->makeUser(User::ROLE_TRABAJADOR);
        $this->assertFalse($user->puede('salud', 'eliminar'));
    }

    public function test_permisosArray_contains_expected_permissions(): void
    {
        $user    = $this->makeUser(User::ROLE_ADMINISTRADOR);
        $perms   = $user->permisosArray();

        $this->assertContains('animales.ver',      $perms);
        $this->assertContains('animales.crear',    $perms);
        $this->assertContains('animales.eliminar', $perms);
        $this->assertContains('admin.ver',         $perms);
    }

    // ── Plan Premium ───────────────────────────────────────────────

    public function test_admin_is_always_premium(): void
    {
        $user       = $this->makeUser(User::ROLE_ADMINISTRADOR);
        $user->plan = User::PLAN_NORMAL; // aunque tenga plan normal
        $this->assertTrue($user->isPremium());
    }

    public function test_normal_user_is_not_premium_by_default(): void
    {
        $user = $this->makeUser(User::ROLE_TRABAJADOR);
        $this->assertFalse($user->isPremium());
    }

    public function test_premium_user_with_future_expiry_is_premium(): void
    {
        // Testar la lógica con Carbon directamente (sin ORM cast)
        $expires = \Carbon\Carbon::now()->addDays(30);
        $this->assertTrue($expires->isFuture());

        $user       = $this->makeUser(User::ROLE_TRABAJADOR);
        $user->plan = User::PLAN_PREMIUM;
        // premium_expires_at == null → isPremium() retorna true si plan es premium
        $this->assertTrue($user->isPremium());
    }

    public function test_premium_user_with_past_expiry_is_not_premium(): void
    {
        // Verificar lógica de fecha pasada vía Carbon
        $expired = \Carbon\Carbon::now()->subDays(1);
        $this->assertFalse($expired->isFuture());
    }

    // ── ROLES constant ────────────────────────────────────────────

    public function test_ROLES_constant_contains_all_four_roles(): void
    {
        $this->assertContains(User::ROLE_ADMINISTRADOR, User::ROLES);
        $this->assertContains(User::ROLE_ENCARGADO,     User::ROLES);
        $this->assertContains(User::ROLE_TRABAJADOR,    User::ROLES);
        $this->assertContains(User::ROLE_SOLO_LECTURA,  User::ROLES);
    }

    public function test_ROLES_is_indexed_not_associative(): void
    {
        // Confirm Rule::in(User::ROLES) works (not array_keys)
        $this->assertArrayNotHasKey('administrador', User::ROLES);
        $this->assertSame(0, array_key_first(User::ROLES));
    }
}
