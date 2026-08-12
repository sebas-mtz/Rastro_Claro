<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\ConfiguracionLector;
use App\Models\User;
use App\Support\NormalizadorLectura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ajustes del lector por rancho.
 *
 * El sistema se vende a explotaciones con equipos que nadie del lado del
 * desarrollo va a ver. Estos ajustes existen para que un lector poco común se
 * adapte desde la interfaz, sin tocar el código: lo que se prueba aquí es que
 * ese camino funcione de verdad y que no se contamine entre ranchos.
 */
class ConfiguracionLectorTest extends TestCase
{
    use RefreshDatabase;

    private const CODIGO = '484000123456789';

    private function dueno(): User
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

    // ─── Normalizador ─────────────────────────────────────────────────────

    public function test_without_settings_the_reading_is_only_trimmed(): void
    {
        $normalizador = NormalizadorLectura::porDefecto();

        $this->assertSame(self::CODIGO, $normalizador->aplicar(" 484 000 123 456 789 \r\n"));
        $this->assertSame('OV-100', $normalizador->aplicar('ov-100'));
        $this->assertSame(15, $normalizador->longitudEsperada());
    }

    public function test_a_configured_prefix_is_discarded(): void
    {
        $config = new ConfiguracionLector(['prefijo_descartar' => 'LA']);

        $this->assertSame(self::CODIGO, (new NormalizadorLectura($config))->aplicar('LA484000123456789'));
    }

    /**
     * El recorte solo procede si la lectura empieza así de verdad. Cortar a
     * ciegas los primeros caracteres mutilaría los códigos que llegan limpios.
     */
    public function test_a_prefix_that_is_not_there_is_not_cut(): void
    {
        $config = new ConfiguracionLector(['prefijo_descartar' => 'LA']);

        $this->assertSame(self::CODIGO, (new NormalizadorLectura($config))->aplicar(self::CODIGO));
    }

    public function test_a_configured_suffix_is_discarded(): void
    {
        $config = new ConfiguracionLector(['sufijo_descartar' => '#']);

        $this->assertSame(self::CODIGO, (new NormalizadorLectura($config))->aplicar('484000123456789#'));
    }

    public function test_prefix_and_suffix_work_together(): void
    {
        $config = new ConfiguracionLector([
            'prefijo_descartar' => 'LA',
            'sufijo_descartar' => '#',
        ]);

        $this->assertSame(self::CODIGO, (new NormalizadorLectura($config))->aplicar('LA484000123456789#'));
    }

    public function test_digits_only_can_be_turned_on(): void
    {
        $limpio = new NormalizadorLectura(new ConfiguracionLector(['solo_digitos' => true]));

        $this->assertSame(self::CODIGO, $limpio->aplicar('484-000-123-456-789'));
        // Con la opción activa se pierden los identificadores con letras: por
        // eso viene apagada de fábrica.
        $this->assertSame('100', $limpio->aplicar('OV-100'));
    }

    public function test_the_expected_length_can_be_overridden(): void
    {
        $config = new ConfiguracionLector(['longitud_esperada' => 10]);

        $this->assertSame(10, (new NormalizadorLectura($config))->longitudEsperada());
    }

    public function test_the_explanation_lists_what_was_done(): void
    {
        $config = new ConfiguracionLector([
            'prefijo_descartar' => 'LA',
            'sufijo_descartar' => 'XX',
        ]);

        $detalle = (new NormalizadorLectura($config))->explicar('LA484000123456789');

        $this->assertSame(self::CODIGO, $detalle['normalizado']);
        $this->assertSame(15, $detalle['longitud']);
        // Dice tanto lo que recortó como lo que estaba configurado y no aplicó.
        $this->assertStringContainsString('Se descartó el prefijo', $detalle['pasos'][0]);
        $this->assertStringContainsString('no termina con él', $detalle['pasos'][1]);
    }

    // ─── Efecto sobre el sistema ──────────────────────────────────────────

    /**
     * Un prefijo de LETRAS no necesita configurarse.
     *
     * La lectura del código ISO se queda solo con los dígitos, así que un
     * lector que antepone «LA» ya funcionaba sin ajustar nada. Queda escrito
     * para que nadie configure un recorte que no hace falta.
     */
    public function test_a_letter_prefix_already_works_without_settings(): void
    {
        $this->dueno();
        $animal = $this->animal();
        $animal->update(['microchip_codigo' => self::CODIGO]);

        $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => 'LA484000123456789']));

        $respuesta->assertOk();
        $this->assertTrue($respuesta->json('encontrado'));
        $this->assertSame($animal->id, $respuesta->json('animal.id'));
    }

    /**
     * El caso que sí justifica esta pantalla: un prefijo de DÍGITOS.
     *
     * Ahí la limpieza automática no puede ayudar, porque no hay forma de
     * distinguir un cero añadido por el lector de un cero del código. Sin el
     * ajuste, el animal es inencontrable; con él, aparece — y nadie tuvo que
     * tocar el código del sistema.
     */
    public function test_a_numeric_prefix_needs_the_setting(): void
    {
        $this->dueno();
        $animal = $this->animal();
        $animal->update(['microchip_codigo' => self::CODIGO]);

        $lectura = '00' . self::CODIGO;   // 17 dígitos

        $this->getJson(route('animales.buscar-identificador', ['codigo' => $lectura]))
            ->assertOk()
            ->assertJson(['encontrado' => false]);

        ConfiguracionLector::delRancho()->update(['prefijo_descartar' => '00']);

        $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => $lectura]));

        $respuesta->assertOk();
        $this->assertTrue($respuesta->json('encontrado'));
        $this->assertSame($animal->id, $respuesta->json('animal.id'));
    }

    /** Lo que se guarda tiene que ser lo mismo que después se busca. */
    public function test_the_prefix_is_also_stripped_when_recording(): void
    {
        $this->dueno();
        $animal = $this->animal();

        ConfiguracionLector::delRancho()->update(['prefijo_descartar' => '00']);

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => '00' . self::CODIGO,
        ])->assertSessionHasNoErrors();

        $this->assertSame(self::CODIGO, $animal->fresh()->microchip_codigo);
    }

    // ─── Aislamiento y permisos ───────────────────────────────────────────

    public function test_each_ranch_has_its_own_settings(): void
    {
        $primero = $this->dueno();
        ConfiguracionLector::delRancho()->update(['prefijo_descartar' => 'AAA']);

        $this->actingAs(User::factory()->create());

        // El segundo rancho arranca limpio, no hereda nada del primero.
        $this->assertNull(ConfiguracionLector::delRancho()->prefijo_descartar);

        $this->actingAs($primero);
        $this->assertSame('AAA', ConfiguracionLector::delRancho()->prefijo_descartar);
    }

    public function test_the_owner_can_open_and_save_the_settings(): void
    {
        $this->dueno();

        $this->get(route('herramientas.lector'))->assertOk();

        $this->put(route('herramientas.lector.update'), [
            'prefijo_descartar' => 'LA',
            'tipo_conexion' => ConfiguracionLector::CONEXION_SERIAL,
            'baud_rate' => 19200,
            'modelo_lector' => 'Lector de bastón',
        ])->assertSessionHasNoErrors();

        $config = ConfiguracionLector::delRancho();

        $this->assertSame('LA', $config->prefijo_descartar);
        $this->assertSame(19200, $config->baud_rate);
        $this->assertSame('Lector de bastón', $config->modelo_lector);
    }

    /**
     * Un empleado no configura el equipo del rancho: es una decisión del
     * dueño, y un ajuste mal puesto rompería la lectura para todos.
     */
    public function test_an_employee_cannot_change_the_settings(): void
    {
        $dueno = User::factory()->create();
        $empleado = User::factory()->create(['cuenta_id' => $dueno->id]);

        $this->actingAs($empleado);

        $this->get(route('herramientas.lector'))->assertForbidden();
        $this->put(route('herramientas.lector.update'), [
            'tipo_conexion' => 'teclado',
            'baud_rate' => 9600,
        ])->assertForbidden();
    }

    public function test_an_unusual_baud_rate_is_rejected(): void
    {
        $this->dueno();

        $this->put(route('herramientas.lector.update'), [
            'tipo_conexion' => 'serial',
            'baud_rate' => 7777,
        ])->assertSessionHasErrors('baud_rate');
    }

    // ─── Probador ─────────────────────────────────────────────────────────

    /** Probar no guarda: es lo que permite experimentar sin romper nada. */
    public function test_trying_out_settings_changes_nothing(): void
    {
        $this->dueno();

        $respuesta = $this->postJson(route('herramientas.lector.probar'), [
            'lectura' => 'LA484000123456789#',
            'prefijo_descartar' => 'LA',
            'sufijo_descartar' => '#',
        ]);

        $respuesta->assertOk();
        $this->assertSame(self::CODIGO, $respuesta->json('normalizado'));
        $this->assertTrue($respuesta->json('coincide'));
        $this->assertSame('484', $respuesta->json('iso.pais'));

        // Nada de eso quedó guardado.
        $this->assertNull(ConfiguracionLector::delRancho()->prefijo_descartar);
    }

    public function test_the_tester_reports_a_length_that_does_not_match(): void
    {
        $this->dueno();

        $respuesta = $this->postJson(route('herramientas.lector.probar'), [
            'lectura' => '48400012345',
        ]);

        $respuesta->assertOk();
        $this->assertFalse($respuesta->json('coincide'));
        $this->assertSame(15, $respuesta->json('longitud_esperada'));
        $this->assertNull($respuesta->json('iso'));
    }
}
