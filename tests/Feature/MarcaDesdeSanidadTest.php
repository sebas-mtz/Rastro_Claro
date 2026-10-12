<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\EventoSalud;
use App\Models\Lote;
use App\Models\MarcaCorporal;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La marca sobre el cuerpo aparece sola al registrar la atención sanitaria.
 *
 * Sin esto habría que capturar lo mismo dos veces —el evento en Salud y la
 * marca en la ficha—, que es la forma más segura de que la segunda no se
 * capture nunca.
 */
class MarcaDesdeSanidadTest extends TestCase
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

    private function vacuna(): \App\Models\Vacuna
    {
        return \App\Models\Vacuna::create([
            'nombre' => 'Clostridiasis',
            'patogeno' => 'Clostridium',
        ]);
    }

    /**
     * Evento de salud con lo mínimo obligatorio ya puesto.
     *
     * `diagnostico` no admite nulos y la vacunación exige su vacuna: sin este
     * atajo, cada prueba repetiría los mismos campos y costaría ver qué está
     * comprobando de verdad.
     */
    private function evento(array $datos): EventoSalud
    {
        return EventoSalud::create(array_merge([
            'tipo' => EventoSalud::TIPO_REVISION,
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Revisión de rutina',
        ], $datos));
    }

    // ─── Alta ─────────────────────────────────────────────────────────────

    public function test_a_health_event_with_a_body_part_creates_its_mark(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $evento = $this->evento([
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'vacuna_id' => $this->vacuna()->id,
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Clostridiasis',
            'zona_corporal' => 'cuello',
        ]);

        $marca = MarcaCorporal::first();

        $this->assertNotNull($marca);
        $this->assertSame('cuello', $marca->zona);
        // Una vacunación se marca como vacuna, no como lesión.
        $this->assertSame(MarcaCorporal::VACUNA, $marca->tipo);
        $this->assertSame('Clostridiasis', $marca->descripcion);
        $this->assertSame(MarcaCorporal::ACTIVA, $marca->estado);
        $this->assertSame(EventoSalud::class, $marca->origen_tipo);
        $this->assertSame($evento->id, (int) $marca->origen_id);
    }

    public function test_a_lesion_event_is_marked_as_a_lesion(): void
    {
        $this->usuario();

        $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'pata_trasera_derecha',
        ]);

        $this->assertSame(MarcaCorporal::LESION, MarcaCorporal::first()->tipo);
    }

    public function test_a_treatment_with_a_body_part_creates_its_mark(): void
    {
        $this->usuario();

        Tratamiento::create([
            'animal_id' => $this->animal()->id,
            'nombre' => 'Curación de herida',
            'fecha_inicio' => now()->toDateString(),
            'zona_corporal' => 'flanco_izquierdo',
        ]);

        $marca = MarcaCorporal::first();

        $this->assertNotNull($marca);
        $this->assertSame(MarcaCorporal::TRATAMIENTO, $marca->tipo);
        $this->assertSame('Curación de herida', $marca->descripcion);
        $this->assertSame(Tratamiento::class, $marca->origen_tipo);
    }

    /** Sin zona no hay nada que señalar: no se inventa una. */
    public function test_an_event_without_a_body_part_creates_nothing(): void
    {
        $this->usuario();

        $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_DESPARASITACION,
            'fecha_programada' => now()->toDateString(),
        ]);

        $this->assertSame(0, MarcaCorporal::count());
    }

    /**
     * Un evento de lote no marca el cuerpo de cuarenta animales: sería ruido,
     * no información.
     */
    public function test_a_lot_wide_event_does_not_mark_any_body(): void
    {
        $user = $this->usuario();
        $lote = Lote::create(['nombre' => 'Lote A', 'corral_potrero' => 'Norte', 'responsable_id' => $user->id]);

        $this->evento([
            'lote_id' => $lote->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'vacuna_id' => $this->vacuna()->id,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'cuello',
        ]);

        $this->assertSame(0, MarcaCorporal::count());
    }

    /** Una zona que el catálogo no reconoce no se dibuja en ningún lado. */
    public function test_an_unknown_body_part_creates_nothing(): void
    {
        $this->usuario();

        $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_REVISION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'ala_derecha',
        ]);

        $this->assertSame(0, MarcaCorporal::count());
    }

    // ─── Cambios posteriores ──────────────────────────────────────────────

    public function test_changing_the_body_part_moves_the_mark(): void
    {
        $this->usuario();

        $evento = $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'lomo',
        ]);

        $evento->update(['zona_corporal' => 'grupa']);

        // Se mueve la misma marca, no se crea una segunda.
        $this->assertSame(1, MarcaCorporal::count());
        $this->assertSame('grupa', MarcaCorporal::first()->zona);
    }

    public function test_clearing_the_body_part_removes_the_mark(): void
    {
        $this->usuario();

        $evento = $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'lomo',
        ]);

        $this->assertSame(1, MarcaCorporal::count());

        $evento->update(['zona_corporal' => null]);

        // Dejarla mentiría sobre dónde ocurrió.
        $this->assertSame(0, MarcaCorporal::count());
    }

    public function test_deleting_the_event_removes_its_mark(): void
    {
        $this->usuario();

        $evento = $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'ubre',
        ]);

        $evento->delete();

        $this->assertSame(0, MarcaCorporal::count());
    }

    /** Un tratamiento terminado deja la marca resuelta, no la borra. */
    public function test_a_finished_treatment_resolves_its_mark(): void
    {
        $this->usuario();

        $tratamiento = Tratamiento::create([
            'animal_id' => $this->animal()->id,
            'nombre' => 'Curación',
            'fecha_inicio' => now()->subDays(7)->toDateString(),
            'zona_corporal' => 'pata_delantera_derecha',
        ]);

        $this->assertSame(MarcaCorporal::ACTIVA, MarcaCorporal::first()->estado);

        $tratamiento->update([
            'estado' => Tratamiento::ESTADO_COMPLETADO,
            'fecha_fin' => now()->toDateString(),
        ]);

        $marca = MarcaCorporal::first();

        $this->assertSame(MarcaCorporal::RESUELTA, $marca->estado);
        $this->assertNotNull($marca->fecha_resolucion);
        // La marca sigue existiendo: que sanara es parte del historial.
        $this->assertSame(1, MarcaCorporal::count());
    }

    // ─── Convivencia con las marcas capturadas a mano ─────────────────────

    /**
     * Las marcas manuales no se tocan jamás, aunque coincidan en zona y fecha
     * con una atención sanitaria.
     */
    public function test_manual_marks_are_never_touched(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $manual = $animal->marcasCorporales()->create([
            'zona' => 'lomo',
            'tipo' => MarcaCorporal::OBSERVACION,
            'descripcion' => 'Anotación del pastor',
            'fecha' => now()->toDateString(),
            'estado' => MarcaCorporal::ACTIVA,
        ]);

        $evento = $this->evento([
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'lomo',
        ]);

        // Conviven: una manual y una automática, en la misma zona.
        $this->assertSame(2, MarcaCorporal::count());

        $evento->delete();

        // Al borrar el evento solo se va la suya.
        $this->assertSame(1, MarcaCorporal::count());
        $this->assertSame($manual->id, MarcaCorporal::first()->id);
        $this->assertSame('Anotación del pastor', MarcaCorporal::first()->descripcion);
    }

    /**
     * Si alguien dio por resuelta la marca a mano, editar el evento no la
     * reabre: el estado lo manda quien lo cambió.
     */
    public function test_editing_the_event_does_not_reopen_a_resolved_mark(): void
    {
        $this->usuario();

        $evento = $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_LESION,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'costillar_izquierdo',
        ]);

        MarcaCorporal::first()->update([
            'estado' => MarcaCorporal::RESUELTA,
            'fecha_resolucion' => now()->toDateString(),
        ]);

        $evento->update(['diagnostico' => 'Herida superficial ya cerrada']);

        $marca = MarcaCorporal::first();

        $this->assertSame(MarcaCorporal::RESUELTA, $marca->estado);
        // Y sí se actualiza lo que describe.
        $this->assertSame('Herida superficial ya cerrada', $marca->descripcion);
    }

    // ─── Aislamiento ──────────────────────────────────────────────────────

    public function test_the_mark_belongs_to_the_same_ranch_as_the_event(): void
    {
        $dueno = $this->usuario();

        $this->evento([
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'vacuna_id' => $this->vacuna()->id,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'cuello',
        ]);

        $this->assertSame($dueno->id, (int) MarcaCorporal::first()->owner_id);

        $this->actingAs(User::factory()->create());
        $this->assertSame(0, MarcaCorporal::count());
    }

    // ─── Por HTTP ─────────────────────────────────────────────────────────

    public function test_recording_a_vaccine_through_the_form_draws_it_on_the_body(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $animal->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'vacuna_id' => $this->vacuna()->id,
            'fecha_programada' => now()->toDateString(),
            'diagnostico' => 'Refuerzo anual',
            'zona_corporal' => 'cuello',
        ])->assertSessionHasNoErrors();

        $marca = MarcaCorporal::first();

        $this->assertNotNull($marca);
        $this->assertSame('cuello', $marca->zona);
        $this->assertSame(MarcaCorporal::VACUNA, $marca->tipo);
    }

    public function test_an_unknown_body_part_is_rejected_by_the_form(): void
    {
        $this->usuario();

        $this->post(route('eventos-salud.store'), [
            'animal_id' => $this->animal()->id,
            'tipo' => EventoSalud::TIPO_VACUNACION,
            'vacuna_id' => $this->vacuna()->id,
            'fecha_programada' => now()->toDateString(),
            'zona_corporal' => 'ala_derecha',
        ])->assertSessionHasErrors('zona_corporal');
    }
}
