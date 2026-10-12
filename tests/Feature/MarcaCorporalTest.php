<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\MarcaCorporal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marcas sobre el cuerpo del ejemplar.
 *
 * Lo que se guarda es la ZONA anatómica, no una coordenada de la figura: si
 * mañana se cambia la borrega de geometrías por un modelo realista, estas
 * marcas siguen valiendo. Varias de estas pruebas fijan justamente eso.
 */
class MarcaCorporalTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-001'): Animal
    {
        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
        ]);
    }

    /** Empleado del rancho con un puesto del catálogo y sus permisos. */
    private function empleadoConPuesto(User $dueno, string $clave): User
    {
        $puesto = \App\Models\PuestoTrabajador::withoutGlobalScope('owner')->create([
            'owner_id' => $dueno->id,
            'clave' => $clave,
            'nombre' => ucfirst($clave),
            'permisos' => \App\Models\PuestoTrabajador::permisosPorDefecto($clave),
            'activo' => true,
        ]);

        return User::factory()->create([
            'cuenta_id' => $dueno->id,
            'role' => User::ROLE_TRABAJADOR,
            'puesto_id' => $puesto->id,
            'puesto' => $clave,
        ]);
    }

    private function marca(Animal $animal, array $extra = []): MarcaCorporal
    {
        return $animal->marcasCorporales()->create(array_merge([
            'zona' => 'pata_trasera_derecha',
            'tipo' => MarcaCorporal::LESION,
            'fecha' => now()->toDateString(),
            'estado' => MarcaCorporal::ACTIVA,
        ], $extra));
    }

    // ─── Catálogo ─────────────────────────────────────────────────────────

    /**
     * Las zonas del backend y las de la figura 3D tienen que coincidir: una
     * marca en una zona que la figura no conoce no se podría dibujar.
     */
    public function test_every_backend_zone_exists_in_the_3d_figure(): void
    {
        $archivo = resource_path('js/Components/Borrega3D/zonas.js');

        $this->assertFileExists($archivo);

        $js = file_get_contents($archivo);

        foreach (array_keys(MarcaCorporal::ZONAS) as $zona) {
            $this->assertStringContainsString(
                $zona . ':',
                $js,
                "La zona «{$zona}» existe en el modelo pero no tiene posición en la figura 3D."
            );
        }
    }

    public function test_the_body_condition_zones_are_real_zones(): void
    {
        foreach (MarcaCorporal::ZONAS_CONDICION as $zona) {
            $this->assertArrayHasKey($zona, MarcaCorporal::ZONAS);
        }
    }

    // ─── Registro ─────────────────────────────────────────────────────────

    public function test_a_mark_is_recorded_on_a_body_part(): void
    {
        $user = $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'pata_trasera_derecha',
            'tipo' => MarcaCorporal::LESION,
            'descripcion' => 'Cojera leve, se observa inflamación.',
            'fecha' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $marca = MarcaCorporal::first();

        $this->assertNotNull($marca);
        $this->assertSame($animal->id, $marca->animal_id);
        $this->assertSame('Pata trasera derecha', $marca->zona_legible);
        $this->assertSame('Lesión o herida', $marca->tipo_legible);
        $this->assertSame(MarcaCorporal::ACTIVA, $marca->estado);
        $this->assertSame($user->id, $marca->registrado_por);
    }

    public function test_an_unknown_body_part_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'ala_izquierda',
            'tipo' => MarcaCorporal::LESION,
            'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('zona');

        $this->assertSame(0, MarcaCorporal::count());
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'lomo',
            'tipo' => 'ordeña',
            'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('tipo');
    }

    public function test_a_mark_cannot_be_in_the_future(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'lomo',
            'tipo' => MarcaCorporal::OBSERVACION,
            'fecha' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('fecha');
    }

    /** Nada pudo pasarle al ejemplar antes de que naciera. */
    public function test_a_mark_cannot_predate_the_birth(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'lomo',
            'tipo' => MarcaCorporal::OBSERVACION,
            'fecha' => now()->subYears(3)->toDateString(),
        ])->assertSessionHasErrors('fecha');

        $this->assertSame(0, MarcaCorporal::count());
    }

    // ─── Ciclo de vida de la marca ────────────────────────────────────────

    public function test_a_mark_is_resolved_without_losing_it(): void
    {
        $this->usuario();
        $marca = $this->marca($this->animal());

        $this->patch(route('marcas.resolver', $marca->id), [
            'estado' => MarcaCorporal::RESUELTA,
        ])->assertSessionHasNoErrors();

        $marca->refresh();

        $this->assertSame(MarcaCorporal::RESUELTA, $marca->estado);
        $this->assertNotNull($marca->fecha_resolucion);
        // Sigue existiendo: que una lesión sanara es parte del historial.
        $this->assertSame(1, MarcaCorporal::count());
    }

    public function test_a_resolved_mark_can_be_reopened(): void
    {
        $this->usuario();
        $marca = $this->marca($this->animal(), [
            'estado' => MarcaCorporal::RESUELTA,
            'fecha_resolucion' => now()->toDateString(),
        ]);

        $this->patch(route('marcas.resolver', $marca->id), [
            'estado' => MarcaCorporal::ACTIVA,
        ])->assertSessionHasNoErrors();

        $marca->refresh();

        $this->assertSame(MarcaCorporal::ACTIVA, $marca->estado);
        $this->assertNull($marca->fecha_resolucion);
    }

    public function test_a_mark_captured_today_can_be_deleted(): void
    {
        $this->usuario();
        $marca = $this->marca($this->animal());

        $this->delete(route('marcas.destroy', $marca->id))->assertSessionHasNoErrors();

        $this->assertSame(0, MarcaCorporal::count());
    }

    /**
     * Pasado el día de captura la marca ya es historial: se resuelve, no se
     * borra. Así nadie hace desaparecer una lesión incómoda.
     */
    public function test_an_older_mark_cannot_be_deleted(): void
    {
        $this->usuario();
        $marca = $this->marca($this->animal());

        $marca->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();

        $this->delete(route('marcas.destroy', $marca->id))->assertSessionHasErrors('marca');

        $this->assertSame(1, MarcaCorporal::count());
    }

    // ─── Ficha del animal ─────────────────────────────────────────────────

    public function test_the_animal_page_sends_the_marks_with_active_ones_first(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->marca($animal, [
            'zona' => 'lomo',
            'estado' => MarcaCorporal::RESUELTA,
            'fecha' => now()->subDays(5)->toDateString(),
        ]);
        $this->marca($animal, [
            'zona' => 'ubre',
            'fecha' => now()->subDays(9)->toDateString(),
        ]);

        $props = $this->get(route('animales.show', $animal->id))
            ->getOriginalContent()->getData()['page']['props'];

        $marcas = $props['marcasCorporales'];

        $this->assertCount(2, $marcas);
        // La activa va primero aunque sea más antigua: es la que urge ver.
        $this->assertSame('ubre', $marcas[0]['zona']);
        $this->assertSame('activa', $marcas[0]['estado']);
        $this->assertArrayHasKey('tiposMarca', $props);
    }

    // ─── Aislamiento ──────────────────────────────────────────────────────

    public function test_marks_belong_to_the_ranch_that_recorded_them(): void
    {
        $dueno = $this->usuario();
        $animal = $this->animal();

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'cruz',
            'tipo' => MarcaCorporal::VACUNA,
            'fecha' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame($dueno->id, (int) MarcaCorporal::first()->owner_id);

        // Otro rancho no ve nada.
        $this->actingAs(User::factory()->create());
        $this->assertSame(0, MarcaCorporal::count());
    }

    public function test_a_mark_from_another_ranch_cannot_be_resolved(): void
    {
        $this->usuario();
        $marca = $this->marca($this->animal());

        $this->actingAs(User::factory()->create());

        $this->patch(route('marcas.resolver', $marca->id), [
            'estado' => MarcaCorporal::RESUELTA,
        ])->assertNotFound();
    }

    /**
     * El veterinario marca lesiones.
     *
     * Estas rutas cuelgan del módulo de Salud, no del de Ejemplares. Señalar
     * dónde tiene una lesión es trabajo sanitario, y el veterinario tiene
     * Ejemplares solo en lectura: si dependiera de ese módulo no podría
     * hacerlo, que era justo lo contrario de lo que se busca.
     */
    public function test_a_vet_can_mark_a_lesion_on_their_ranchs_flock(): void
    {
        $dueno = $this->usuario();
        $animal = $this->animal();
        $this->marca($animal);

        $veterinario = $this->empleadoConPuesto($dueno, 'veterinario');
        $this->actingAs($veterinario);

        $this->assertSame(1, MarcaCorporal::count());

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'pata_delantera_izquierda',
            'tipo' => MarcaCorporal::LESION,
            'fecha' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        // El registro queda a nombre del rancho, y de quien lo capturó.
        $nueva = MarcaCorporal::where('zona', 'pata_delantera_izquierda')->first();

        $this->assertNotNull($nueva);
        $this->assertSame($dueno->id, (int) $nueva->owner_id);
        $this->assertSame($veterinario->id, $nueva->registrado_por);
    }

    /** Un empleado sin puesto no tiene acceso a ningún módulo. */
    public function test_an_employee_without_a_job_title_cannot_mark(): void
    {
        $dueno = $this->usuario();
        $animal = $this->animal();

        $this->actingAs(User::factory()->create([
            'cuenta_id' => $dueno->id,
            'role' => User::ROLE_TRABAJADOR,
        ]));

        $this->post(route('marcas.store', $animal->id), [
            'zona' => 'lomo',
            'tipo' => MarcaCorporal::OBSERVACION,
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, MarcaCorporal::count());
    }
}
