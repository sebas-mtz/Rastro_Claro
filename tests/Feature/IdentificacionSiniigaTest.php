<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use App\Support\CodigoIso11784;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Identificación oficial: arete visual SINIIGA y arete electrónico ISO 11784.
 *
 * Nota sobre HDX y FDX-B: son las dos formas de transmisión de la norma
 * ISO 11785 y las resuelve el lector, no el sistema. Con cualquiera de las dos
 * llega el mismo código de 15 dígitos, así que aquí no hay dos caminos de
 * lectura que probar: lo que se prueba es que el código se valide, no se
 * duplique y se encuentre, y que la tecnología quede anotada.
 */
class IdentificacionSiniigaTest extends TestCase
{
    use RefreshDatabase;

    /** Código mexicano de ejemplo: 484 + 12 dígitos. */
    private const CODIGO_MX = '484000123456789';

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

    // ─── Lectura del código ───────────────────────────────────────────────

    public function test_an_iso_code_is_broken_into_country_and_national_number(): void
    {
        $iso = CodigoIso11784::desde(self::CODIGO_MX);

        $this->assertNotNull($iso);
        $this->assertSame('484', $iso->pais);
        $this->assertSame('000123456789', $iso->nacional);
        $this->assertSame('484 000123456789', $iso->formateado());
        $this->assertTrue($iso->esMexico());
        $this->assertFalse($iso->esDeFabricante());
    }

    /**
     * Cada marca de lector presenta el código a su manera. Todas deben acabar
     * en el mismo número.
     */
    public function test_the_code_is_read_no_matter_how_the_reader_presents_it(): void
    {
        $variantes = [
            '484000123456789',
            '484 000123456789',
            '484-000123456789',
            '484.000123456789',
            "484000123456789\r\n",
            ' 484 000 123 456 789 ',
        ];

        foreach ($variantes as $variante) {
            $iso = CodigoIso11784::desde($variante);

            $this->assertNotNull($iso, "No se interpretó: {$variante}");
            $this->assertSame(self::CODIGO_MX, $iso->codigo);
        }
    }

    public function test_a_code_of_the_wrong_length_is_not_an_iso_code(): void
    {
        $this->assertNull(CodigoIso11784::desde('48400012345678'));    // 14
        $this->assertNull(CodigoIso11784::desde('4840001234567890'));  // 16
        $this->assertNull(CodigoIso11784::desde('OV-100'));
        $this->assertNull(CodigoIso11784::desde(''));
        $this->assertNull(CodigoIso11784::desde(null));
    }

    /**
     * Los prefijos 900 en adelante identifican al fabricante, no a un país:
     * un arete así no viene del padrón nacional y conviene decirlo.
     */
    public function test_a_manufacturer_prefix_is_not_a_country(): void
    {
        $iso = CodigoIso11784::desde('982000123456789');

        $this->assertTrue($iso->esDeFabricante());
        $this->assertFalse($iso->esMexico());
        $this->assertSame('Código de fabricante, no de país', $iso->origen());
    }

    // ─── Registro ─────────────────────────────────────────────────────────

    public function test_an_electronic_tag_is_recorded_with_its_technology(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => '484 000123456789',
            'siniiga_numero' => '07 1234 5678',
            'tecnologia_rfid' => Animal::TECNOLOGIA_FDX_B,
            'fecha_colocacion_microchip' => now()->toDateString(),
            'estado_microchip' => 'activo',
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        // El código se guarda sin separadores, venga como venga del lector.
        $this->assertSame(self::CODIGO_MX, $animal->microchip_codigo);
        $this->assertSame('0712345678', $animal->siniiga_numero);
        $this->assertSame('FDX-B', $animal->tecnologia_rfid);
        $this->assertSame('484', $animal->pais_codigo);
        $this->assertSame('484 000123456789', $animal->codigo_iso_formateado);
    }

    public function test_an_hdx_tag_is_recorded_the_same_way(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => self::CODIGO_MX,
            'tecnologia_rfid' => Animal::TECNOLOGIA_HDX,
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        $this->assertSame('HDX', $animal->tecnologia_rfid);
        $this->assertSame(self::CODIGO_MX, $animal->microchip_codigo);
    }

    public function test_an_unknown_technology_is_rejected(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => self::CODIGO_MX,
            'tecnologia_rfid' => 'FDX-A',
        ])->assertSessionHasErrors('tecnologia_rfid');
    }

    /**
     * Una lectura incompleta no debe guardarse como si fuera buena: es la
     * forma más fácil de acabar con un animal imposible de encontrar.
     */
    public function test_an_electronic_tag_must_follow_the_iso_structure(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => '4840001234',
        ])->assertSessionHasErrors('microchip_codigo');

        $this->assertNull($animal->fresh()->microchip_codigo);
    }

    /** Un arete visual o una marca a mano no tienen por qué cumplir la norma. */
    public function test_a_non_electronic_identifier_is_free_form(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'arete',
            'microchip_codigo' => 'AZUL-17',
        ])->assertSessionHasNoErrors();

        $this->assertSame('AZUL-17', $animal->fresh()->microchip_codigo);
    }

    /**
     * A los microchips tampoco se les exige la estructura ISO.
     *
     * Existen implantes anteriores a la norma, con formatos de 9 o 10 dígitos,
     * que siguen puestos en animales vivos. Rechazarlos obligaría al rancho a
     * mentir sobre el tipo de identificador para poder registrarlos.
     */
    public function test_a_pre_standard_microchip_is_still_accepted(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'microchip',
            'microchip_codigo' => '0006A1B2C3',
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        $this->assertSame('0006A1B2C3', $animal->microchip_codigo);
        // No es ISO, así que no se le inventa un país.
        $this->assertNull($animal->pais_codigo);
    }

    /**
     * Un ejemplar importado trae un código de otro país. Se acepta —el sistema
     * registra, no decide quién puede estar en el rebaño— pero deja aviso.
     */
    public function test_a_foreign_code_is_accepted_with_a_warning(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $this->post(route('animales.identificador.store', $animal->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => '840000123456789',   // Estados Unidos
        ])->assertSessionHasNoErrors()->assertSessionHas('aviso');

        $this->assertSame('840', $animal->fresh()->pais_codigo);
    }

    public function test_the_same_electronic_code_cannot_be_on_two_animals(): void
    {
        $this->usuario();
        $primero = $this->animal('OV-001');
        $segundo = $this->animal('OV-002');

        $this->post(route('animales.identificador.store', $primero->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => self::CODIGO_MX,
        ])->assertSessionHasNoErrors();

        $this->post(route('animales.identificador.store', $segundo->id), [
            'tipo_identificador' => 'rfid',
            'microchip_codigo' => self::CODIGO_MX,
        ])->assertSessionHasErrors('microchip_codigo');

        $this->assertNull($segundo->fresh()->microchip_codigo);
    }

    public function test_the_same_official_tag_number_cannot_be_on_two_animals(): void
    {
        $this->usuario();
        $primero = $this->animal('OV-001');
        $segundo = $this->animal('OV-002');

        $this->post(route('animales.identificador.store', $primero->id), [
            'tipo_identificador' => 'arete',
            'siniiga_numero' => '0712345678',
        ])->assertSessionHasNoErrors();

        $this->post(route('animales.identificador.store', $segundo->id), [
            'tipo_identificador' => 'arete',
            // Mismo número, escrito con separadores: debe detectarse igual.
            'siniiga_numero' => '07-1234-5678',
        ])->assertSessionHasErrors('siniiga_numero');
    }

    // ─── Búsqueda ─────────────────────────────────────────────────────────

    public function test_an_animal_is_found_by_every_one_of_its_identifiers(): void
    {
        $this->usuario();
        $animal = $this->animal('OV-500');

        $animal->update([
            'alias' => 'Lucera',
            'microchip_codigo' => self::CODIGO_MX,
            'siniiga_numero' => '0712345678',
        ]);

        $busquedas = [
            'OV-500',            // arete interno
            'Lucera',            // alias
            self::CODIGO_MX,     // código electrónico
            '484 000123456789',  // el mismo, como lo muestra el lector
            '0712345678',        // arete visual oficial
            '07-1234-5678',      // el mismo, con separadores
        ];

        foreach ($busquedas as $busqueda) {
            $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => $busqueda]));

            $respuesta->assertOk();
            $this->assertTrue(
                $respuesta->json('encontrado'),
                "No se encontró el ejemplar buscando por: {$busqueda}"
            );
            $this->assertSame($animal->id, $respuesta->json('animal.id'));
        }
    }

    /**
     * Cuando no hay coincidencia, la respuesta explica igual qué se leyó: no
     * es lo mismo un código válido de un animal no registrado que una lectura
     * a medias.
     */
    public function test_a_failed_search_still_explains_the_code(): void
    {
        $this->usuario();

        $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => self::CODIGO_MX]));

        $respuesta->assertOk();
        $this->assertFalse($respuesta->json('encontrado'));
        $this->assertSame('484', $respuesta->json('iso.pais'));
        $this->assertSame('México (SINIIGA)', $respuesta->json('iso.origen'));
    }

    public function test_a_search_that_is_not_an_iso_code_says_so(): void
    {
        $this->usuario();

        $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => 'OV-999']));

        $respuesta->assertOk();
        $this->assertFalse($respuesta->json('encontrado'));
        $this->assertNull($respuesta->json('iso'));
    }

    // ─── Diagnóstico del lector ───────────────────────────────────────────

    /**
     * La pantalla de diagnóstico existe para instalaciones en ranchos con
     * equipos que nadie del lado del desarrollo va a ver. Tiene que abrirse
     * incluso para quien todavía no tiene permiso de ningún módulo: es
     * justamente esa persona la que necesita probar su lector.
     */
    public function test_the_reader_diagnostic_is_open_to_anyone_signed_in(): void
    {
        // Un trabajador sin puesto no tiene acceso a ningún módulo.
        $dueno = User::factory()->create();
        $empleado = User::factory()->create([
            'cuenta_id' => $dueno->id,
            'role' => User::ROLE_TRABAJADOR,
            'puesto_id' => null,
        ]);

        $this->actingAs($empleado);

        $this->get(route('herramientas.diagnostico-lector'))->assertOk();
    }

    public function test_the_reader_diagnostic_still_requires_signing_in(): void
    {
        $this->get(route('herramientas.diagnostico-lector'))->assertRedirect(route('login'));
    }

    /** El arete de un rancho ajeno no aparece, ni buscándolo por su código. */
    public function test_a_tag_from_another_ranch_is_not_found(): void
    {
        $ajeno = User::factory()->create();
        $this->actingAs($ajeno);
        $this->animal('OV-AJENO')->update(['microchip_codigo' => self::CODIGO_MX]);

        $this->actingAs(User::factory()->create());

        $respuesta = $this->getJson(route('animales.buscar-identificador', ['codigo' => self::CODIGO_MX]));

        $respuesta->assertOk();
        $this->assertFalse($respuesta->json('encontrado'));
    }
}
