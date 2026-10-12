<?php

namespace Tests\Feature\Animals;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\ServicioReproductivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrato de negocio del módulo Animales.
 *
 * IMPORTANTE:
 * - Estos tests describen lo que el sistema DEBERÍA permitir o rechazar.
 * - No dependen de conocer qué Controller/Request/Service implementa la regla.
 * - Si un test falla, puede significar que encontramos una regla todavía no protegida.
 *
 * Ajustes mínimos que quizá necesites hacer antes de correr:
 * 1. Si tus rutas no son POST /animales y PUT /animales/{id}, cambia storeUrl()/updateUrl().
 * 2. Si AnimalFactory necesita datos de tenancy/cuenta, agrégalos en crearAnimal().
 * 3. Los tests de identificadores asumen que, cuando se proporcionan,
 *    arete, siniiga_id, identificador y numero_registro deben ser únicos.
 *    Si por negocio alguno NO es único, elimina ese test.
 * 4. No se incluyen aquí padre_externo_id porque el formulario de alta compartido
 *    actualmente no expone ese campo.
 */
class AnimalModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function storeUrl(): string
    {
        return '/animales';
    }

    private function updateUrl(Animal $animal): string
    {
        return "/animales/{$animal->id}";
    }

    private function payloadBase(array $overrides = []): array
    {
        return array_merge([
            'alias' => 'Animal prueba',
            'especie' => 'Ovino',
            'arete' => 'TEST-' . uniqid(),
            'sexo' => 'H',
            'fecha_nac' => now()->subYear()->toDateString(),

            // opcionales
            'raza' => '',
            'peso' => '',
            'BCS' => '',
            'estado_productivo' => '',
            'lote_id' => '',
            'siniiga_id' => '',
            'identificador' => '',
            'numero_registro' => '',
            'grado_pureza' => '',
            'color' => '',

            // genealogía
            'madre_id' => '',
            'padre_id' => '',

            // historial reproductivo; solo aplican si hay madre
            'concepcion_historica' => '',
            'tipo_nacimiento_historico' => '',
            'tipo_parto_origen' => '',
        ], $overrides);
    }

    private function updatePayload(Animal $animal, array $overrides = []): array
    {
        return array_merge([
            'alias' => $animal->alias ?? '',
            'especie' => $animal->especie,
            'arete' => $animal->arete,
            'sexo' => $animal->sexo,
            'raza' => $animal->raza ?? '',
            'fecha_nac' => optional($animal->fecha_nac)->toDateString(),
            'peso' => $animal->peso ?? '',
            'BCS' => $animal->BCS ?? '',
            'estado_productivo' => $animal->estado_productivo ?? '',
            'lote_id' => $animal->lote_id ?? '',
            'siniiga_id' => $animal->siniiga_id ?? '',
            'identificador' => $animal->identificador ?? '',
            'numero_registro' => $animal->numero_registro ?? '',
            'grado_pureza' => $animal->grado_pureza ?? '',
            'color' => $animal->color ?? '',
            'madre_id' => $animal->madre_id ?? '',
            'padre_id' => $animal->padre_id ?? '',
        ], $overrides);
    }

    private function crearAnimal(array $overrides = []): Animal
    {
        return Animal::factory()->create(array_merge([
            'especie' => 'Ovino',
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(2),
            'estado_productivo' => 'Cordero',
        ], $overrides));
    }

    // ============================================================
    // A. ALTA BÁSICA Y CAMPOS OBLIGATORIOS
    // ============================================================

    public function test_alta_minima_valida_crea_un_animal(): void
    {
        $payload = $this->payloadBase([
            'arete' => 'ALTA-001',
            'alias' => '',
            'fecha_nac' => '',
        ]);

        $response = $this->post($this->storeUrl(), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', [
            'arete' => 'ALTA-001',
            'especie' => 'Ovino',
            'sexo' => 'H',
        ]);
    }

    public function test_rechaza_alta_sin_especie(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'especie' => '',
        ]));

        $response->assertSessionHasErrors('especie');
    }

    public function test_rechaza_alta_sin_arete(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => '',
        ]));

        $response->assertSessionHasErrors('arete');
    }

    public function test_rechaza_alta_sin_sexo(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'sexo' => '',
        ]));

        $response->assertSessionHasErrors('sexo');
    }

    public function test_rechaza_sexo_fuera_de_macho_o_hembra(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'sexo' => 'X',
        ]));

        $response->assertSessionHasErrors('sexo');
    }

    public function test_rechaza_especie_no_soportada(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'especie' => 'Especie inventada',
        ]));

        $response->assertSessionHasErrors('especie');
    }

    // ============================================================
    // B. FECHA DE NACIMIENTO
    // ============================================================

    public function test_fecha_de_nacimiento_es_opcional_si_no_hay_madre(): void
    {
        $arete = 'SIN-FECHA-001';

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => $arete,
            'fecha_nac' => '',
            'madre_id' => '',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', ['arete' => $arete]);
    }

    public function test_rechaza_fecha_de_nacimiento_futura(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => now()->addDay()->toDateString(),
        ]));

        $response->assertSessionHasErrors('fecha_nac');
    }

    public function test_rechaza_fecha_de_nacimiento_invalida(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => 'no-es-fecha',
        ]));

        $response->assertSessionHasErrors('fecha_nac');
    }

    // ============================================================
    // C. PESO Y BCS
    // ============================================================

    public function test_peso_es_opcional(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'peso' => '',
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_rechaza_peso_negativo(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'peso' => -1,
        ]));

        $response->assertSessionHasErrors('peso');
    }

    public function test_rechaza_peso_cero(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'peso' => 0,
        ]));

        $response->assertSessionHasErrors('peso');
    }

    public function test_acepta_peso_decimal_positivo(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'peso' => 3.7,
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_bcs_es_opcional(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'BCS' => '',
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_acepta_bcs_minimo_1(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'BCS' => 1.0,
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_acepta_bcs_maximo_5(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'BCS' => 5.0,
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_rechaza_bcs_menor_a_1(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'BCS' => 0.9,
        ]));

        $response->assertSessionHasErrors('BCS');
    }

    public function test_rechaza_bcs_mayor_a_5(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'BCS' => 5.1,
        ]));

        $response->assertSessionHasErrors('BCS');
    }

    // ============================================================
    // D. IDENTIFICADORES
    // ============================================================

    public function test_rechaza_arete_duplicado(): void
    {
        $this->crearAnimal(['arete' => 'DUP-ARETE']);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => 'DUP-ARETE',
        ]));

        $response->assertSessionHasErrors('arete');
    }

    public function test_rechaza_siniiga_duplicado_cuando_se_proporciona(): void
    {
        $this->crearAnimal(['siniiga_id' => 'SINIIGA-001']);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'siniiga_id' => 'SINIIGA-001',
        ]));

        $response->assertSessionHasErrors('siniiga_id');
    }

    public function test_rechaza_identificador_duplicado_cuando_se_proporciona(): void
    {
        $this->crearAnimal(['identificador' => 'IDENT-001']);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'identificador' => 'IDENT-001',
        ]));

        $response->assertSessionHasErrors('identificador');
    }

    public function test_rechaza_numero_registro_duplicado_cuando_se_proporciona(): void
    {
        $this->crearAnimal(['numero_registro' => 'REG-001']);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'numero_registro' => 'REG-001',
        ]));

        $response->assertSessionHasErrors('numero_registro');
    }

    // ============================================================
    // E. ESTADO PRODUCTIVO MANUAL
    // ============================================================

    public function test_ovino_nuevo_sin_estado_recibe_cordero_como_estado_inicial(): void
    {
        $arete = 'ESTADO-INICIAL-001';

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => $arete,
            'estado_productivo' => '',
        ]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('animals', [
            'arete' => $arete,
            'estado_productivo' => 'Cordero',
        ]);
    }

    public function test_rechaza_estado_productivo_inexistente(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'estado_productivo' => 'Estado inventado',
        ]));

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_rechaza_asignar_manualmente_estado_automatico(): void
    {
        foreach (['empadre', 'gestante', 'lactancia', 'vacia'] as $estado) {
            $response = $this->post($this->storeUrl(), $this->payloadBase([
                'arete' => 'AUTO-' . $estado . '-' . uniqid(),
                'estado_productivo' => $estado,
            ]));

            $response->assertSessionHasErrors('estado_productivo');
        }
    }

    public function test_rechaza_asignar_manualmente_estado_terminal_al_crear(): void
    {
        foreach ([
            'muerto',
            'vendido',
            'faeneado',
            'sacrificado',
            'descarte_reproductivo',
            'robado',
            'extraviado',
            'donado',
            'trasladado',
            'baja_otra',
        ] as $estado) {
            $response = $this->post($this->storeUrl(), $this->payloadBase([
                'arete' => 'TERMINAL-' . $estado . '-' . uniqid(),
                'estado_productivo' => $estado,
            ]));

            $response->assertSessionHasErrors('estado_productivo');
        }
    }

    public function test_hembra_adulta_puede_pasar_de_cordero_a_vientre(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subMonths(8),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Vientre'])
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'estado_productivo' => 'Vientre',
        ]);
    }

    public function test_macho_adulto_puede_pasar_de_cordero_a_semental(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subMonths(8),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Semental'])
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'estado_productivo' => 'Semental',
        ]);
    }

    public function test_rechaza_hembra_como_semental(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYear(),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Semental'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_rechaza_macho_como_vientre(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYear(),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Vientre'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_rechaza_cordero_hembra_menor_de_7_meses_como_vientre(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subMonths(6),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Vientre'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_rechaza_cordero_macho_menor_de_7_meses_como_semental(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subMonths(6),
            'estado_productivo' => 'Cordero',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Semental'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_vientre_no_puede_regresar_a_cordero(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(2),
            'estado_productivo' => 'Vientre',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Cordero'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    public function test_semental_no_puede_regresar_a_cordero(): void
    {
        $animal = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(2),
            'estado_productivo' => 'Semental',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['estado_productivo' => 'Cordero'])
        );

        $response->assertSessionHasErrors('estado_productivo');
    }

    // ============================================================
    // F. EDICIÓN
    // ============================================================

    public function test_animal_activo_puede_editar_alias(): void
    {
        $animal = $this->crearAnimal(['alias' => 'Antes']);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['alias' => 'Después'])
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'alias' => 'Después',
        ]);
    }

    public function test_no_permite_cambiar_peso_directo_si_ya_existen_pesajes(): void
    {
        $animal = $this->crearAnimal(['peso' => 40]);

        Pesaje::create([
            'animal_id' => $animal->id,
            'fecha' => now()->subDays(10)->toDateString(),
            'peso' => 40,
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['peso' => 50])
        );

        $response->assertSessionHasErrors('peso');
    }

    public function test_animal_terminal_no_puede_editarse(): void
    {
        $animal = $this->crearAnimal([
            'estado_productivo' => 'muerto',
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['alias' => 'Intento'])
        );

        $response->assertSessionHasErrors('animal');
        $this->assertDatabaseMissing('animals', [
            'id' => $animal->id,
            'alias' => 'Intento',
        ]);
    }

    // ============================================================
    // G. GENEALOGÍA BÁSICA EN EL ALTA
    // ============================================================

    public function test_alta_sin_padres_es_valida(): void
    {
        $arete = 'SIN-PADRES-001';

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => $arete,
            'madre_id' => '',
            'padre_id' => '',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('animals', ['arete' => $arete]);
    }

    public function test_rechaza_macho_asignado_como_madre(): void
    {
        $madreInvalida = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madreInvalida->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_rechaza_hembra_asignada_como_padre(): void
    {
        $padreInvalido = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'padre_id' => $padreInvalido->id,
        ]));

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_rechaza_madre_inexistente(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => 999999999,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_rechaza_padre_inexistente(): void
    {
        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'padre_id' => 999999999,
        ]));

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_padre_sin_madre_no_obliga_a_enviar_historial_reproductivo(): void
    {
        $padre = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'padre_id' => $padre->id,
            'madre_id' => '',
            'concepcion_historica' => '',
            'tipo_nacimiento_historico' => '',
            'tipo_parto_origen' => '',
        ]));

        $response->assertSessionHasNoErrors();
    }

    // ============================================================
    // H. HISTORIAL REPRODUCTIVO AL DAR DE ALTA CON MADRE
    // ============================================================

    public function test_si_hay_madre_fecha_nacimiento_es_obligatoria(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => '',
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('fecha_nac');
    }

    public function test_si_hay_madre_concepcion_historica_es_obligatoria(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => '',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('concepcion_historica');
    }

    public function test_si_hay_madre_tipo_nacimiento_historico_es_obligatorio(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => '',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('tipo_nacimiento_historico');
    }

    public function test_si_hay_madre_tipo_parto_origen_es_obligatorio(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => '',
        ]));

        $response->assertSessionHasErrors('tipo_parto_origen');
    }

    public function test_rechaza_tipo_concepcion_historica_invalido(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => 'teletransportacion',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('concepcion_historica');
    }

    public function test_rechaza_tipo_nacimiento_historico_invalido(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'sextuple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('tipo_nacimiento_historico');
    }

    public function test_rechaza_tipo_parto_historico_invalido(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'tipo_inventado',
        ]));

        $response->assertSessionHasErrors('tipo_parto_origen');
    }

    public function test_monta_natural_historica_requiere_padre_interno(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'padre_id' => '',
            'concepcion_historica' => 'monta_natural',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_concepcion_no_natural_puede_registrarse_sin_padre_interno(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'madre_id' => $madre->id,
            'padre_id' => '',
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasNoErrors();
    }

    // ============================================================
    // I. CONSISTENCIA TEMPORAL DE PADRES
    // ============================================================

    public function test_rechaza_madre_nacida_despues_de_la_cria(): void
    {
        $fechaCria = Carbon::parse('2026-06-01');

        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => Carbon::parse('2026-07-01'),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => $fechaCria->toDateString(),
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_rechaza_padre_nacido_despues_de_la_cria(): void
    {
        $fechaCria = Carbon::parse('2026-06-01');

        $padre = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => Carbon::parse('2026-07-01'),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => $fechaCria->toDateString(),
            'padre_id' => $padre->id,
        ]));

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_rechaza_madre_sin_edad_reproductiva_en_fecha_estimada_de_concepcion(): void
    {
        // Cría nace 2026-09-01.
        // En ovinos la concepción histórica estimada es ~147 días antes.
        // La madre debe tener al menos 7 meses EN ESA FECHA, no hoy.
        $fechaCria = Carbon::parse('2026-09-01');
        $fechaConcepcion = $fechaCria->copy()->subDays(147);

        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => $fechaConcepcion->copy()->subMonths(6),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => $fechaCria->toDateString(),
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_rechaza_padre_sin_edad_reproductiva_en_fecha_estimada_de_concepcion(): void
    {
        $fechaCria = Carbon::parse('2026-09-01');
        $fechaConcepcion = $fechaCria->copy()->subDays(147);

        $padre = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => $fechaConcepcion->copy()->subMonths(6),
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => $fechaCria->toDateString(),
            'padre_id' => $padre->id,
        ]));

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_rechaza_parto_historico_demasiado_cercano_a_otro_parto_de_la_madre(): void
    {
        $fechaNuevaCria = Carbon::parse('2026-09-01');

        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => Carbon::parse('2023-01-01'),
        ]);

        EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $madre->lote_id,
            'user_id' => $this->user->id,
            'tipo_evento' => 'parto',
            'fecha' => $fechaNuevaCria->copy()->subDays(60)->toDateString(),
            'es_historico' => true,
        ]);

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'fecha_nac' => $fechaNuevaCria->toDateString(),
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasErrors('madre_id');
    }

    // ============================================================
    // J. RECONSTRUCCIÓN HISTÓRICA EXITOSA
    // ============================================================

    public function test_alta_con_madre_reconstruye_servicio_parto_y_cria_historicos(): void
    {
        $fechaNacimiento = Carbon::parse('2026-09-01');

        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => Carbon::parse('2023-01-01'),
            'estado_productivo' => 'Vientre',
        ]);

        $padre = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => Carbon::parse('2023-01-01'),
            'estado_productivo' => 'Semental',
        ]);

        $areteCria = 'HIST-001';

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => $areteCria,
            'sexo' => 'H',
            'fecha_nac' => $fechaNacimiento->toDateString(),
            'madre_id' => $madre->id,
            'padre_id' => $padre->id,
            'concepcion_historica' => 'monta_natural',
            'tipo_nacimiento_historico' => 'simple',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasNoErrors();

        $criaAnimal = Animal::where('arete', $areteCria)->firstOrFail();

        $this->assertEquals($madre->id, $criaAnimal->madre_id);
        $this->assertEquals($padre->id, $criaAnimal->padre_id);

        $eventoServicio = EventoReproductivo::where('hembra_id', $madre->id)
            ->where('tipo_evento', 'servicio')
            ->where('es_historico', true)
            ->first();

        $this->assertNotNull($eventoServicio);
        $this->assertEquals(
            $fechaNacimiento->copy()->subDays(147)->toDateString(),
            Carbon::parse($eventoServicio->fecha)->toDateString()
        );

        $servicio = ServicioReproductivo::where('evento_id', $eventoServicio->id)->first();
        $this->assertNotNull($servicio);
        $this->assertEquals('monta_natural', $servicio->tipo_servicio);
        $this->assertEquals($padre->id, $servicio->macho_id);

        $eventoParto = EventoReproductivo::where('hembra_id', $madre->id)
            ->where('tipo_evento', 'parto')
            ->where('es_historico', true)
            ->first();

        $this->assertNotNull($eventoParto);
        $this->assertEquals(
            $fechaNacimiento->toDateString(),
            Carbon::parse($eventoParto->fecha)->toDateString()
        );

        $parto = Parto::where('evento_id', $eventoParto->id)->first();
        $this->assertNotNull($parto);
        $this->assertEquals($eventoServicio->id, $parto->servicio_evento_id);
        $this->assertEquals('normal', $parto->tipo_parto);
        $this->assertEquals(1, $parto->numero_crias);

        $cria = Cria::where('parto_id', $parto->id)
            ->where('animal_id', $criaAnimal->id)
            ->first();

        $this->assertNotNull($cria);
        $this->assertEquals('hembra', $cria->sexo);
        $this->assertEquals('vivo', $cria->condicion);
    }

    public function test_nacimiento_gemelar_historico_guarda_numero_crias_dos_pero_solo_crea_la_cria_conocida(): void
    {
        $fechaNacimiento = Carbon::parse('2026-09-01');

        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => Carbon::parse('2023-01-01'),
            'estado_productivo' => 'Vientre',
        ]);

        $areteCria = 'GEMELAR-001';

        $response = $this->post($this->storeUrl(), $this->payloadBase([
            'arete' => $areteCria,
            'fecha_nac' => $fechaNacimiento->toDateString(),
            'madre_id' => $madre->id,
            'concepcion_historica' => 'transferencia_embriones',
            'tipo_nacimiento_historico' => 'gemelar',
            'tipo_parto_origen' => 'normal',
        ]));

        $response->assertSessionHasNoErrors();

        $animal = Animal::where('arete', $areteCria)->firstOrFail();

        $eventoParto = EventoReproductivo::where('hembra_id', $madre->id)
            ->where('tipo_evento', 'parto')
            ->where('es_historico', true)
            ->firstOrFail();

        $parto = Parto::where('evento_id', $eventoParto->id)->firstOrFail();

        $this->assertEquals(2, $parto->numero_crias);
        $this->assertEquals(
            1,
            Cria::where('parto_id', $parto->id)
                ->where('animal_id', $animal->id)
                ->count()
        );
    }

    // ============================================================
    // K. REGLAS QUE NECESITAN EL ESQUEMA DE BAJAS PARA SER EXACTAS
    // ============================================================

    public function test_pendiente_madre_dada_de_baja_antes_del_nacimiento_debe_rechazarse(): void
    {
        $this->markTestIncomplete(
            'Necesita conocer la estructura exacta de Baja/fecha de salida para crear el escenario sin inventar columnas.'
        );
    }

    public function test_pendiente_madre_dada_de_baja_despues_del_nacimiento_historico_debe_ser_valida(): void
    {
        $this->markTestIncomplete(
            'Necesita conocer la estructura exacta de Baja/fecha de salida para comprobar que un nacimiento histórico anterior a la baja siga siendo válido.'
        );
    }

    // ============================================================
    // L. GENEALOGÍA EN EDICIÓN / DEFENSA DEL BACKEND
    // Aunque el formulario Edit actual no muestra estos selects,
    // una petición manipulada no debe poder corromper genealogía.
    // ============================================================

    public function test_update_rechaza_que_animal_sea_su_propia_madre(): void
    {
        $animal = $this->crearAnimal(['sexo' => 'H']);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['madre_id' => $animal->id])
        );

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_update_rechaza_que_animal_sea_su_propio_padre(): void
    {
        $animal = $this->crearAnimal(['sexo' => 'M']);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['padre_id' => $animal->id])
        );

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_update_rechaza_macho_como_madre(): void
    {
        $animal = $this->crearAnimal(['sexo' => 'H']);

        $madreInvalida = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['madre_id' => $madreInvalida->id])
        );

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_update_rechaza_hembra_como_padre(): void
    {
        $animal = $this->crearAnimal(['sexo' => 'H']);

        $padreInvalido = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(3),
        ]);

        $response = $this->put(
            $this->updateUrl($animal),
            $this->updatePayload($animal, ['padre_id' => $padreInvalido->id])
        );

        $response->assertSessionHasErrors('padre_id');
    }

    public function test_update_rechaza_usar_descendiente_como_madre_y_crear_ciclo(): void
    {
        $madre = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(4),
        ]);

        $hija = $this->crearAnimal([
            'sexo' => 'H',
            'fecha_nac' => now()->subYears(2),
            'madre_id' => $madre->id,
        ]);

        $response = $this->put(
            $this->updateUrl($madre),
            $this->updatePayload($madre, ['madre_id' => $hija->id])
        );

        $response->assertSessionHasErrors('madre_id');
    }

    public function test_update_rechaza_usar_descendiente_como_padre_y_crear_ciclo(): void
    {
        $padre = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(4),
        ]);

        $hijo = $this->crearAnimal([
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(2),
            'padre_id' => $padre->id,
        ]);

        $response = $this->put(
            $this->updateUrl($padre),
            $this->updatePayload($padre, ['padre_id' => $hijo->id])
        );

        $response->assertSessionHasErrors('padre_id');
    }
}