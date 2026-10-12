<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Pesaje;
use App\Models\User;
use App\Services\PrediccionPesoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proyección del peso futuro de un ejemplar.
 *
 * El servicio es un porte a PHP del script scripts/prediccion_pesajes.py, para
 * que la ficha no dependa de tener Python instalado en el servidor. Estas
 * pruebas comprueban que el porte da lo mismo: los datos de referencia son los
 * mismos con los que se validó el script, generados de una curva Gompertz
 * conocida (peso adulto 62 kg), así que se sabe de antemano qué debe salir.
 */
class PrediccionPesoTest extends TestCase
{
    use RefreshDatabase;

    /** Pesajes de un cordero real: de 3.4 kg al nacer a 56.5 kg en 200 días. */
    private const CURVA = [
        [0, 3.41], [14, 6.51], [30, 10.67], [47, 16.20],
        [62, 22.25], [80, 28.76], [95, 34.87], [112, 41.19],
        [130, 44.76], [150, 49.00], [175, 53.81], [200, 56.53],
    ];

    private function usuario(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function animal(string $arete = 'OV-142'): Animal
    {
        return Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => $arete,
            'sexo' => 'F',
            'fecha_nac' => '2026-01-10',
        ]);
    }

    /** @param array<array{0:int,1:float}> $puntos */
    private function conPesajes(Animal $animal, array $puntos): Animal
    {
        $origen = new \DateTimeImmutable('2026-01-10');

        foreach ($puntos as [$dia, $peso]) {
            Pesaje::create([
                'animal_id' => $animal->id,
                'fecha' => $origen->modify("+{$dia} days")->format('Y-m-d'),
                'peso' => $peso,
            ]);
        }

        return $animal;
    }

    private function servicio(int $dias = 90): PrediccionPesoService
    {
        return new PrediccionPesoService($dias);
    }

    // ─── Elección del modelo ──────────────────────────────────────────────

    /**
     * La prueba que justifica todo el servicio.
     *
     * Con la curva completa debe ganar Gompertz, no la recta. Y la proyección
     * a 90 días tiene que aplanarse cerca del peso adulto real (62 kg), no
     * dispararse como haría una recta: extrapolando la pendiente media daría
     * más de 80 kg.
     */
    public function test_with_a_full_curve_it_picks_gompertz_and_flattens_out(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $res = $this->servicio(90)->para($animal);

        $this->assertNotNull($res);
        $this->assertSame('Gompertz', $res['modelo']);
        $this->assertGreaterThan(0.99, $res['r2']);

        $noventa = collect($res['hitos'])->firstWhere('dias', 90);

        // El script en Python da 61.11 kg con estos mismos datos.
        $this->assertEqualsWithDelta(61.1, $noventa['peso'], 1.5);

        // Y sobre todo: NO se dispara como lo haría una recta.
        $this->assertLessThan(70, $noventa['peso']);
    }

    /**
     * Con pocos pesajes gana la recta, y es lo correcto: con tres puntos nadie
     * puede afirmar dónde está la meseta.
     */
    public function test_with_few_weighings_it_falls_back_to_a_line(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), array_slice(self::CURVA, 0, 3));

        $res = $this->servicio(90)->para($animal);

        $this->assertSame('Lineal', $res['modelo']);
        $this->assertNotNull($res['aviso']);
    }

    // ─── Ganancia diaria ──────────────────────────────────────────────────

    public function test_it_reports_the_observed_daily_gain(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $res = $this->servicio()->para($animal);

        // (56.53 − 3.41) / 200 días = 0.2656 kg/día
        $this->assertEqualsWithDelta(0.2656, $res['gdp_media'], 0.001);

        // El último tramo va mucho más lento: el animal se está frenando.
        $this->assertLessThan($res['gdp_media'], $res['gdp_reciente']);
    }

    /**
     * El aviso dice hacia dónde se equivoca la recta con ESTE animal.
     * La dirección depende de su fase, no del modelo.
     */
    public function test_the_warning_says_the_line_falls_short_for_a_growing_lamb(): void
    {
        $this->usuario();

        // Cordero acelerando: cada tramo gana más que el anterior.
        $animal = $this->conPesajes($this->animal(), [[0, 4.0], [30, 9.0], [60, 16.0]]);

        $res = $this->servicio()->para($animal);

        $this->assertStringContainsString('se queda corta', $res['aviso']);
    }

    public function test_the_warning_says_the_line_overshoots_for_a_finishing_animal(): void
    {
        $this->usuario();

        // Animal cerca de su techo: cada tramo gana menos.
        $animal = $this->conPesajes($this->animal(), [[0, 45.0], [30, 52.0], [60, 55.0]]);

        $res = $this->servicio()->para($animal);

        $this->assertStringContainsString('se pasa', $res['aviso']);
    }

    // ─── Datos insuficientes ──────────────────────────────────────────────

    /** Sin datos no se enseñan ceros: no se enseña nada. */
    public function test_a_single_weighing_yields_no_prediction(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), [[0, 4.0]]);

        $this->assertNull($this->servicio()->para($animal));
    }

    public function test_an_animal_without_weighings_yields_no_prediction(): void
    {
        $this->usuario();

        $this->assertNull($this->servicio()->para($this->animal()));
    }

    /** Todos los pesajes el mismo día: no hay recorrido temporal que modelar. */
    public function test_weighings_on_the_same_day_yield_no_prediction(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), [[0, 40.0], [0, 41.0]]);

        $this->assertNull($this->servicio()->para($animal));
    }

    /** Dos básculas el mismo día son una medición, no dos puntos. */
    public function test_same_day_weighings_are_averaged(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), [
            [0, 40.0], [0, 42.0],   // promedio: 41
            [100, 51.0],
        ]);

        $res = $this->servicio()->para($animal);

        $this->assertSame(2, $res['n_pesajes']);
        // (51 − 41) / 100 = 0.10 kg/día
        $this->assertEqualsWithDelta(0.10, $res['gdp_media'], 0.001);
    }

    // ─── Forma de la respuesta ────────────────────────────────────────────

    public function test_the_horizon_is_always_among_the_milestones(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $res = $this->servicio(45)->para($animal);

        $dias = collect($res['hitos'])->pluck('dias')->all();

        // 45 no es un hito habitual, pero se pidió: tiene que aparecer.
        $this->assertContains(45, $dias);
        // Y no se cuelan hitos más allá del horizonte.
        $this->assertEmpty(array_filter($dias, fn ($d) => $d > 45));
    }

    public function test_the_projection_carries_a_widening_margin(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $res = $this->servicio(90)->para($animal);

        $primero = $res['futuro'][0];
        $ultimo = end($res['futuro']);

        $anchoInicial = $primero['max'] - $primero['min'];
        $anchoFinal = $ultimo['max'] - $ultimo['min'];

        // Extrapolar lejos es más frágil, y la banda tiene que decirlo.
        $this->assertGreaterThan($anchoInicial, $anchoFinal);
    }

    public function test_the_series_pairs_each_real_weighing_with_the_fit(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $res = $this->servicio()->para($animal);

        $this->assertCount(count(self::CURVA), $res['serie']);

        foreach ($res['serie'] as $punto) {
            $this->assertArrayHasKey('real', $punto);
            $this->assertArrayHasKey('ajuste', $punto);
            // El ajuste sigue de cerca a los datos: es lo que hace creíble
            // la parte proyectada de la misma curva.
            $this->assertEqualsWithDelta($punto['real'], $punto['ajuste'], 2.5);
        }
    }

    // ─── En la ficha ──────────────────────────────────────────────────────

    public function test_the_animal_page_carries_the_prediction(): void
    {
        $this->usuario();
        $animal = $this->conPesajes($this->animal(), self::CURVA);

        $props = $this->get(route('animales.show', $animal->id))
            ->getOriginalContent()->getData()['page']['props'];

        $this->assertArrayHasKey('prediccionPeso', $props);
        $this->assertSame('Gompertz', $props['prediccionPeso']['modelo']);
    }

    public function test_the_page_loads_for_an_animal_without_weighings(): void
    {
        $this->usuario();
        $animal = $this->animal();

        $props = $this->get(route('animales.show', $animal->id))
            ->getOriginalContent()->getData()['page']['props'];

        // Sin datos la clave viaja en null y la ficha no pinta la sección.
        $this->assertNull($props['prediccionPeso']);
    }
}
