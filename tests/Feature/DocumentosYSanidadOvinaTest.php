<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Documento;
use App\Models\EventoSalud;
use App\Models\PuestoTrabajador;
use App\Models\User;
use App\Services\AlertaOperativaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 4: documentos y evidencias + sanidad ovina específica.
 */
class DocumentosYSanidadOvinaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-900'): Animal
    {
        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    // ─── Documentos ───────────────────────────────────────────────────────

    public function test_a_document_can_be_attached_to_an_animal(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'certificado_pureza',
            'nombre' => 'Certificado Dorper 2026',
            'archivo' => UploadedFile::fake()->create('certificado.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = Documento::first();

        $this->assertNotNull($doc);
        $this->assertSame('certificado_pureza', $doc->tipo);
        $this->assertSame(Animal::class, $doc->documentable_type);
        $this->assertSame($animal->id, $doc->documentable_id);
        $this->assertSame(1, $animal->documentos()->count());

        Storage::disk(Documento::DISCO)->assertExists($doc->ruta);
    }

    public function test_the_file_is_stored_on_a_private_disk(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'estudio_veterinario',
            'archivo' => UploadedFile::fake()->create('evidencia.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        // No se guarda en el disco público: no queda accesible por URL directa.
        $this->assertSame(Documento::DISCO, 'local');
        $this->assertStringStartsWith('documentos/', Documento::first()->ruta);
    }

    public function test_an_unsupported_file_type_is_rejected(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'otro',
            'archivo' => UploadedFile::fake()->create('script.exe', 10),
        ])->assertSessionHasErrors('archivo');

        $this->assertSame(0, Documento::count());
    }

    public function test_a_file_over_the_size_limit_is_rejected(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'otro',
            'archivo' => UploadedFile::fake()->create('grande.pdf', Documento::TAMANO_MAXIMO_KB + 500, 'application/pdf'),
        ])->assertSessionHasErrors('archivo');
    }

    public function test_an_unknown_attachable_type_is_rejected(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'App\Models\User',
            'documentable_id' => 1,
            'tipo' => 'otro',
            'archivo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('documentable_tipo');
    }

    public function test_a_document_cannot_be_attached_to_another_accounts_animal(): void
    {
        Storage::fake(Documento::DISCO);

        $this->usuario();
        $ajeno = $this->animal('OV-AJENO');

        // Otra cuenta intenta adjuntar al animal del primero
        $this->usuario();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $ajeno->id,
            'tipo' => 'otro',
            'archivo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('documentable_id');

        $this->assertSame(0, Documento::count());
    }

    public function test_documents_are_isolated_per_account(): void
    {
        Storage::fake(Documento::DISCO);

        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'estudio_veterinario',
            'archivo' => UploadedFile::fake()->create('evidencia.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = Documento::first();

        // Otra cuenta no puede descargarlo: para ella el registro no existe.
        $this->usuario();
        $this->get(route('documentos.download', $doc->id))->assertNotFound();
    }

    public function test_deleting_a_document_removes_the_file(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'estudio_veterinario',
            'archivo' => UploadedFile::fake()->create('evidencia.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = Documento::first();
        $ruta = $doc->ruta;

        $this->delete(route('documentos.destroy', $doc->id))->assertSessionHasNoErrors();

        $this->assertSame(0, Documento::count());
        Storage::disk(Documento::DISCO)->assertMissing($ruta);
    }

    public function test_the_animal_page_carries_its_documents_and_catalog(): void
    {
        Storage::fake(Documento::DISCO);
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('documentos.store'), [
            'documentable_tipo' => 'animal',
            'documentable_id' => $animal->id,
            'tipo' => 'certificado_pureza',
            'archivo' => UploadedFile::fake()->create('cert.pdf', 40, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $respuesta = $this->get("/animales/{$animal->id}");
        $respuesta->assertOk();

        $props = $respuesta->getOriginalContent()->getData()['page']['props'];

        $this->assertCount(1, $props['documentos']);
        $this->assertSame('certificado_pureza', $props['documentos'][0]['tipo']);
        $this->assertArrayHasKey('certificado_pureza', $props['tiposDocumento']);
        $this->assertContains('pdf', $props['extensionesDocumento']);
    }

    // ─── Sanidad ovina ────────────────────────────────────────────────────

    public function test_ovine_specific_health_types_are_accepted(): void
    {
        $this->usuario();
        $animal = $this->animal();

        foreach ([
            EventoSalud::TIPO_RECORTE_PEZUNAS,
            EventoSalud::TIPO_MASTITIS,
            EventoSalud::TIPO_DESPARASITACION,
            EventoSalud::TIPO_BANO_EXTERNO,
        ] as $tipo) {
            $this->post(route('eventos-salud.store'), [
                'animal_id' => $animal->id,
                'tipo' => $tipo,
                'fecha_programada' => now()->toDateString(),
                'diagnostico' => 'Registro de ' . $tipo,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(4, EventoSalud::count());
    }

    public function test_a_non_ovine_health_type_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => 'ordeña_mecanica',
            'fecha_programada' => now()->toDateString(),
        ])->assertSessionHasErrors('tipo');
    }

    public function test_the_withdrawal_end_date_is_calculated_from_application(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->toDateString(),
            'fecha_aplicacion' => now()->toDateString(),
            'estado' => 'aplicada',
            'diagnostico' => 'Ivermectina',
            'producto' => 'Ivermectina 1%',
            'via_administracion' => 'subcutanea',
            'periodo_retiro_dias' => 35,
        ])->assertSessionHasNoErrors();

        $evento = EventoSalud::first();

        $this->assertSame(35, $evento->periodo_retiro_dias);
        $this->assertSame(
            now()->addDays(35)->toDateString(),
            $evento->fecha_fin_retiro->toDateString()
        );
        $this->assertTrue($evento->en_periodo_retiro);
    }

    public function test_without_a_withdrawal_period_no_end_date_is_invented(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_REVISION_PEZUNAS,
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Revisión de rutina',
        ])->assertSessionHasNoErrors();

        $evento = EventoSalud::first();

        $this->assertNull($evento->fecha_fin_retiro);
        $this->assertFalse($evento->en_periodo_retiro);
    }

    public function test_an_expired_withdrawal_period_is_no_longer_active(): void
    {
        $this->usuario();
        $animal = $this->animal();

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->subDays(60)->toDateString(),
            'fecha_aplicacion' => now()->subDays(60)->toDateString(),
            'diagnostico' => 'Tratamiento anterior',
            'periodo_retiro_dias' => 21,
        ]);

        $this->assertFalse(EventoSalud::first()->en_periodo_retiro);
    }

    public function test_animals_in_withdrawal_raise_a_critical_alert(): void
    {
        $this->usuario();
        $animal = $this->animal();

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->toDateString(),
            'fecha_aplicacion' => now()->toDateString(),
            'diagnostico' => 'Ivermectina',
            'periodo_retiro_dias' => 30,
            'estado' => EventoSalud::ESTADO_APLICADA,
        ]);

        $alerta = collect(app(AlertaOperativaService::class)->todas())
            ->firstWhere('titulo', 'Ejemplares en periodo de retiro');

        $this->assertNotNull($alerta);
        $this->assertSame(AlertaOperativaService::CRITICA, $alerta['nivel']);
        $this->assertSame(1, $alerta['cantidad']);
    }

    public function test_an_out_of_range_withdrawal_period_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->toDateString(),
            'periodo_retiro_dias' => 500,
        ])->assertSessionHasErrors('periodo_retiro_dias');
    }

    public function test_an_unknown_administration_route_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->toDateString(),
            'via_administracion' => 'telepatica',
        ])->assertSessionHasErrors('via_administracion');
    }

    // ─── Puestos de trabajadores ──────────────────────────────────────────

    /**
     * El puesto sale del catálogo del rancho, no de una lista fija en el
     * código, y asignarlo es atribución del superadministrador.
     */
    public function test_a_super_admin_can_assign_an_ovine_job_title(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($super);

        $pastor = PuestoTrabajador::withoutGlobalScope('owner')->create([
            'owner_id' => $super->id,
            'clave' => 'pastor',
            'nombre' => 'Pastor',
            'area' => 'Manejo del rebaño',
            'permisos' => PuestoTrabajador::permisosPorDefecto('pastor'),
            'activo' => true,
        ]);

        $trabajador = User::factory()->create(['cuenta_id' => $super->id]);

        $this->put(route('admin.usuarios.update', $trabajador->id), [
            'name' => $trabajador->name,
            'email' => $trabajador->email,
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $super->id,
            'puesto_id' => $pastor->id,
        ])->assertSessionHasNoErrors();

        $trabajador->refresh();

        $this->assertSame($pastor->id, $trabajador->puesto_id);
        // La columna histórica queda coherente con el catálogo.
        $this->assertSame('pastor', $trabajador->puesto);
        // Y el puesto es lo que le abre los módulos.
        $this->assertTrue($trabajador->puede('animales'));
        $this->assertFalse($trabajador->puede('costos'));
    }

    public function test_a_job_title_outside_the_catalog_is_rejected(): void
    {
        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($super);

        $trabajador = User::factory()->create(['cuenta_id' => $super->id]);

        $this->put(route('admin.usuarios.update', $trabajador->id), [
            'name' => $trabajador->name,
            'email' => $trabajador->email,
            'role' => User::ROLE_TRABAJADOR,
            'cuenta_id' => $super->id,
            'puesto_id' => 99999,
        ])->assertSessionHasErrors('puesto_id');

        $this->assertNull($trabajador->fresh()->puesto_id);
    }
}
