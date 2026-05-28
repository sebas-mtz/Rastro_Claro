<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ── Admin panel access ─────────────────────────────────────────

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'activo' => true]);

        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertStatus(200);
    }

    public function test_trabajador_cannot_access_admin_panel(): void
    {
        $trabajador = User::factory()->create(['role' => User::ROLE_TRABAJADOR, 'activo' => true]);

        $response = $this->actingAs($trabajador)->get('/admin/usuarios');

        $response->assertStatus(403);
    }

    public function test_solo_lectura_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SOLO_LECTURA, 'activo' => true]);

        $response = $this->actingAs($user)->get('/admin/usuarios');

        $response->assertStatus(403);
    }

    // ── Trabajadores routes require auth ───────────────────────────

    public function test_guest_cannot_access_trabajadores(): void
    {
        $this->get('/trabajadores')->assertRedirect('/login');
    }

    public function test_trabajador_cannot_access_trabajadores_module(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TRABAJADOR, 'activo' => true]);

        $response = $this->actingAs($user)->get('/trabajadores');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_trabajadores_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'activo' => true]);

        $response = $this->actingAs($admin)->get('/trabajadores');

        $response->assertStatus(200);
    }

    // ── Inactive users are blocked ─────────────────────────────────

    public function test_inactive_admin_is_blocked_and_logged_out(): void
    {
        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMINISTRADOR,
            'activo' => false,
        ]);

        // Login first by bypassing middleware
        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── Dashboard requires auth ────────────────────────────────────

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TRABAJADOR, 'activo' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    // ── Premium middleware ─────────────────────────────────────────

    public function test_normal_user_cannot_access_costos(): void
    {
        $user = User::factory()->create([
            'role'   => User::ROLE_TRABAJADOR,
            'activo' => true,
            'plan'   => User::PLAN_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/costos');

        // Should redirect to planes
        $response->assertRedirect('/planes');
    }

    public function test_premium_admin_can_access_costos(): void
    {
        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMINISTRADOR,
            'activo' => true,
            'plan'   => User::PLAN_NORMAL, // admins are always premium
        ]);

        $response = $this->actingAs($admin)->get('/costos');

        $response->assertStatus(200);
    }
}
