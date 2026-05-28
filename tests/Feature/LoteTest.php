<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'activo' => true]);
    }

    public function test_guest_cannot_access_lotes(): void
    {
        $this->get('/lotes')->assertRedirect('/login');
    }

    public function test_admin_can_view_lotes(): void
    {
        $response = $this->actingAs($this->admin())->get('/lotes');
        $response->assertStatus(200);
    }

    public function test_nombre_is_required_for_lote(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->post('/lotes', [
            'corral_potrero' => 'A1',
        ]);
        $response->assertSessionHasErrors('nombre');
    }

    public function test_admin_can_create_lote(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->post('/lotes', [
            'nombre'           => 'Lote Test',
            'corral_potrero'   => 'C1',
            'responsable_id'   => $admin->id,
            // LoteController::store requiere datos de animal en el mismo request
            'animal' => [
                'especie'          => 'Bovino',
                'raza'             => 'Holstein',
                'arete_inicio'     => 1,
                'arete_fin'        => 2,
                'sexo'             => 'M',
                'fecha_nac'        => null,
                'peso'             => null,
                'estado_productivo'=> null,
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('lotes', ['nombre' => 'Lote Test']);
    }

    public function test_admin_can_delete_lote(): void
    {
        $admin = $this->admin();
        $lote  = Lote::factory()->create();

        $response = $this->actingAs($admin)->delete("/lotes/{$lote->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('lotes', ['id' => $lote->id]);
    }
}
