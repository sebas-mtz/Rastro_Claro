<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ReproduccionSeeder
 * ──────────────────────────────────────────────────────────────────────────
 * Genera el ciclo reproductivo completo por cada parto conocido:
 *
 *   celo → servicio → diagnóstico_positivo → parto → crías
 *
 * Tablas que puebla (en orden de FK):
 *   1. evento_reproductivos  (tipo: celo, servicio, diagnostico, parto)
 *   2. servicio_reproductivos
 *   3. diagnostico_gestacions
 *   4. partos
 *   5. crias
 *
 * Total: 38 partos, ~46 crías distribuidas en G2–G7 (2018-2024)
 *
 * IMPORTANTE: Ejecutar DESPUÉS de AnimalsSeeder.
 *
 * Nota sobre fechas: en los animales G6 y G7 existen dos borregas
 * (Cedra y Miriam) que aparecen con dos partos muy cercanos entre sí.
 * Esto es una herencia de AnimalsSeeder; aquí se registran tal cual
 * están en la tabla animals, sin omitir ningún vínculo madre-cría.
 * ──────────────────────────────────────────────────────────────────────────
 */
class ReproduccionSeeder extends Seeder
{
    /** Días de gestación estándar para ovinos */
    private const GESTACION = 147;

    public function run(): void
    {
        // Índices rápidos por arete
        $animals = DB::table('animals')->pluck('id', 'arete');
        $lotes   = DB::table('lotes')->pluck('id', 'nombre');

        $lV = $lotes['Vientres en Producción'];

        /*
         * Definición de todos los partos conocidos.
         * Cada entrada equivale a UN evento de parto.
         *
         * tipo_parto: 'normal' | 'distocico' | 'cesarea'
         * crias[sexo]: 'macho' | 'hembra'   (enum de la migración)
         */
        $partosData = [

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 2 — 2018
             ═══════════════════════════════════════════════════════════════ */

            // Luna (B17-001) × Relámpago → gemelos Apolo + Diana
            [
                'madre'         => 'B17-001',
                'padre'         => 'S17-002',
                'parto_fecha'   => '2018-04-01',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S18-002', 'sexo' => 'macho',  'peso' => 4.2],
                    ['arete' => 'B18-003', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Nieve (B17-002) × Relámpago → Niebla
            [
                'madre'         => 'B17-002',
                'padre'         => 'S17-002',
                'parto_fecha'   => '2018-02-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B18-004', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Canela (B17-003) × Relámpago → Miel
            [
                'madre'         => 'B17-003',
                'padre'         => 'S17-002',
                'parto_fecha'   => '2018-05-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B18-005', 'sexo' => 'hembra', 'peso' => 3.7],
                ],
            ],

            // Paloma (B17-004) × Trueno → gemelos Rayo + Rosa
            [
                'madre'         => 'B17-004',
                'padre'         => 'S17-001',
                'parto_fecha'   => '2018-02-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S18-001', 'sexo' => 'macho',  'peso' => 4.5],
                    ['arete' => 'B18-001', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Estrella (B17-005) × Trueno → Coral
            [
                'madre'         => 'B17-005',
                'padre'         => 'S17-001',
                'parto_fecha'   => '2018-03-05',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B18-002', 'sexo' => 'hembra', 'peso' => 4.1],
                ],
            ],

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 3 — 2019
             ═══════════════════════════════════════════════════════════════ */

            // Diana (B18-003) × Rayo → gemelos Zeus + Azul
            [
                'madre'         => 'B18-003',
                'padre'         => 'S18-001',
                'parto_fecha'   => '2019-01-15',
                'tipo_parto'    => 'distocico',
                'tipo_servicio' => 'monta_natural',
                'asistencia'    => true,
                'crias'         => [
                    ['arete' => 'S19-001', 'sexo' => 'macho',  'peso' => 4.0],
                    ['arete' => 'B19-001', 'sexo' => 'hembra', 'peso' => 3.7],
                ],
            ],

            // Niebla (B18-004) × Rayo → Brisa
            [
                'madre'         => 'B18-004',
                'padre'         => 'S18-001',
                'parto_fecha'   => '2019-02-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B19-002', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Rosa (B18-001) × Apolo → Flora  [1er parto]
            [
                'madre'         => 'B18-001',
                'padre'         => 'S18-002',
                'parto_fecha'   => '2019-03-08',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B19-003', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Rosa (B18-001) × Apolo → Jade  [2do parto mismo año — herencia AnimalsSeeder]
            [
                'madre'         => 'B18-001',
                'padre'         => 'S18-002',
                'parto_fecha'   => '2019-04-12',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B19-004', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Coral (B18-002) × Goliat → Rubí
            [
                'madre'         => 'B18-002',
                'padre'         => 'S18-EXT',
                'parto_fecha'   => '2019-02-14',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B19-005', 'sexo' => 'hembra', 'peso' => 4.3],
                ],
            ],

            // Miel (B18-005) × Goliat → Topaz
            [
                'madre'         => 'B18-005',
                'padre'         => 'S18-EXT',
                'parto_fecha'   => '2019-03-25',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B19-006', 'sexo' => 'hembra', 'peso' => 4.1],
                ],
            ],

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 4 — 2020
             ═══════════════════════════════════════════════════════════════ */

            // Flora (B19-003) × Zeus → gemelos Ares + Rocío
            [
                'madre'         => 'B19-003',
                'padre'         => 'S19-001',
                'parto_fecha'   => '2020-01-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S20-001', 'sexo' => 'macho',  'peso' => 4.4],
                    ['arete' => 'B20-001', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Jade (B19-004) × Zeus → Lluvia
            [
                'madre'         => 'B19-004',
                'padre'         => 'S19-001',
                'parto_fecha'   => '2020-02-14',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B20-002', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Rubí (B19-005) × Zeus → Perla
            [
                'madre'         => 'B19-005',
                'padre'         => 'S19-001',
                'parto_fecha'   => '2020-03-05',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B20-003', 'sexo' => 'hembra', 'peso' => 4.2],
                ],
            ],

            // Azul (B19-001) × Goliat II → Selva
            [
                'madre'         => 'B19-001',
                'padre'         => 'S20-EXT',
                'parto_fecha'   => '2020-01-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B20-004', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Brisa (B19-002) × Goliat II → Sierra
            [
                'madre'         => 'B19-002',
                'padre'         => 'S20-EXT',
                'parto_fecha'   => '2020-02-28',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B20-005', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Topaz (B19-006) × Goliat II → Ámbar
            [
                'madre'         => 'B19-006',
                'padre'         => 'S20-EXT',
                'parto_fecha'   => '2020-04-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B20-006', 'sexo' => 'hembra', 'peso' => 4.1],
                ],
            ],

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 5 — 2021
             ═══════════════════════════════════════════════════════════════ */

            // Selva (B20-004) × Ares → gemelos Héctor + Amber
            [
                'madre'         => 'B20-004',
                'padre'         => 'S20-001',
                'parto_fecha'   => '2021-01-12',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S21-001', 'sexo' => 'macho',  'peso' => 4.3],
                    ['arete' => 'B21-001', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Sierra (B20-005) × Ares → Nube
            [
                'madre'         => 'B20-005',
                'padre'         => 'S20-001',
                'parto_fecha'   => '2021-02-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B21-002', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Ámbar (B20-006) × Ares → Duna
            [
                'madre'         => 'B20-006',
                'padre'         => 'S20-001',
                'parto_fecha'   => '2021-03-15',
                'tipo_parto'    => 'distocico',
                'tipo_servicio' => 'monta_natural',
                'asistencia'    => true,
                'crias'         => [
                    ['arete' => 'B21-003', 'sexo' => 'hembra', 'peso' => 3.6],
                ],
            ],

            // Rocío (B20-001) × Sansón → Oliva
            [
                'madre'         => 'B20-001',
                'padre'         => 'S21-EXT',
                'parto_fecha'   => '2021-01-25',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B21-004', 'sexo' => 'hembra', 'peso' => 4.1],
                ],
            ],

            // Lluvia (B20-002) × Sansón → Mirra
            [
                'madre'         => 'B20-002',
                'padre'         => 'S21-EXT',
                'parto_fecha'   => '2021-03-08',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B21-005', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Perla (B20-003) × Sansón → Cedra
            [
                'madre'         => 'B20-003',
                'padre'         => 'S21-EXT',
                'parto_fecha'   => '2021-02-12',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B21-006', 'sexo' => 'hembra', 'peso' => 4.2],
                ],
            ],

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 6 — 2022
             ═══════════════════════════════════════════════════════════════ */

            // Oliva (B21-004) × Héctor → gemelos Aquiles + Dulce
            [
                'madre'         => 'B21-004',
                'padre'         => 'S21-001',
                'parto_fecha'   => '2022-01-08',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S22-001', 'sexo' => 'macho',  'peso' => 4.4],
                    ['arete' => 'B22-001', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Mirra (B21-005) × Héctor → Valeria
            [
                'madre'         => 'B21-005',
                'padre'         => 'S21-001',
                'parto_fecha'   => '2022-02-14',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-002', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Cedra (B21-006) × Héctor → Palma  [1er registro 2022]
            [
                'madre'         => 'B21-006',
                'padre'         => 'S21-001',
                'parto_fecha'   => '2022-03-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-003', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Amber (B21-001) × Moisés → Mirto
            [
                'madre'         => 'B21-001',
                'padre'         => 'S22-EXT',
                'parto_fecha'   => '2022-01-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-004', 'sexo' => 'hembra', 'peso' => 4.1],
                ],
            ],

            // Nube (B21-002) × Moisés → Nimfa
            [
                'madre'         => 'B21-002',
                'padre'         => 'S22-EXT',
                'parto_fecha'   => '2022-03-05',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-005', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Duna (B21-003) × Moisés → Naiara
            [
                'madre'         => 'B21-003',
                'padre'         => 'S22-EXT',
                'parto_fecha'   => '2022-02-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-006', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Cedra (B21-006) × Moisés → Miriam  [2do registro 2022 — herencia AnimalsSeeder]
            [
                'madre'         => 'B21-006',
                'padre'         => 'S22-EXT',
                'parto_fecha'   => '2022-04-15',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B22-007', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            /* ═══════════════════════════════════════════════════════════════
             * GENERACIÓN 7 — 2023-2024
             ═══════════════════════════════════════════════════════════════ */

            // Nimfa (B22-005) × Aquiles → gemelos Titán II + Zara
            [
                'madre'         => 'B22-005',
                'padre'         => 'S22-001',
                'parto_fecha'   => '2023-01-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S23-001', 'sexo' => 'macho',  'peso' => 4.2],
                    ['arete' => 'B23-001', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Naiara (B22-006) × Aquiles → Lara
            [
                'madre'         => 'B22-006',
                'padre'         => 'S22-001',
                'parto_fecha'   => '2023-02-14',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B23-002', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Miriam (B22-007) × Aquiles → Vega  [1er registro 2023]
            [
                'madre'         => 'B22-007',
                'padre'         => 'S22-001',
                'parto_fecha'   => '2023-02-28',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B23-004', 'sexo' => 'hembra', 'peso' => 3.7],
                ],
            ],

            // Miriam (B22-007) × Aquiles → Naomi  [2do registro 2023 — herencia AnimalsSeeder]
            [
                'madre'         => 'B22-007',
                'padre'         => 'S22-001',
                'parto_fecha'   => '2023-03-20',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B23-003', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Dulce (B22-001) × Elías → gemelos Balam + Iris II
            [
                'madre'         => 'B22-001',
                'padre'         => 'S23-EXT',
                'parto_fecha'   => '2023-01-25',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'S23-002', 'sexo' => 'macho',  'peso' => 4.1],
                    ['arete' => 'B23-005', 'sexo' => 'hembra', 'peso' => 3.8],
                ],
            ],

            // Valeria (B22-002) × Elías → Aurora
            [
                'madre'         => 'B22-002',
                'padre'         => 'S23-EXT',
                'parto_fecha'   => '2023-02-10',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B23-006', 'sexo' => 'hembra', 'peso' => 3.9],
                ],
            ],

            // Palma (B22-003) × Elías → Selene
            [
                'madre'         => 'B22-003',
                'padre'         => 'S23-EXT',
                'parto_fecha'   => '2023-03-05',
                'tipo_parto'    => 'normal',
                'tipo_servicio' => 'monta_natural',
                'crias'         => [
                    ['arete' => 'B23-007', 'sexo' => 'hembra', 'peso' => 4.0],
                ],
            ],

            // Mirto (B22-004) × Elías → Celeste  [2024]
            [
                'madre'         => 'B22-004',
                'padre'         => 'S23-EXT',
                'parto_fecha'   => '2024-01-15',
                'tipo_parto'    => 'cesarea',
                'tipo_servicio' => 'monta_natural',
                'asistencia'    => true,
                'complicaciones' => true,
                'detalle_complicaciones' => 'Presentación podálica, se realizó cesárea de emergencia. Cría recuperada con éxito.',
                'crias'         => [
                    ['arete' => 'B24-001', 'sexo' => 'hembra', 'peso' => 3.5],
                ],
            ],
        ];

        foreach ($partosData as $parto) {
            $this->crearCicloReproductivo($parto, $animals, $lV);
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
     * Crea un ciclo reproductivo completo para un parto dado:
     *   evento(celo) → evento(servicio) + detalle
     *                → evento(diagnostico) + detalle
     *                → evento(parto) + detalle + crías
     * ──────────────────────────────────────────────────────────────────── */
    private function crearCicloReproductivo(
        array  $p,
        object $animals,
        int    $loteId
    ): void {
        $madreId = $animals[$p['madre']];
        $padreId = $animals[$p['padre']];

        $partoFecha    = Carbon::parse($p['parto_fecha']);
        $servicioFecha = $partoFecha->copy()->subDays(self::GESTACION);
        $celoFecha     = $servicioFecha->copy()->subDays(7);
        $diagFecha     = $servicioFecha->copy()->addDays(30);

        $asistencia        = $p['asistencia']        ?? ($p['tipo_parto'] !== 'normal');
        $complicaciones    = $p['complicaciones']     ?? false;
        $detalleComp       = $p['detalle_complicaciones'] ?? null;

        /* ── 1. Celo ──────────────────────────────────────────────────── */
        DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'    => $madreId,
            'lote_id'      => $loteId,
            'user_id'      => null,
            'tipo_evento'  => 'celo',
            'fecha'        => $celoFecha->toDateString(),
            'costo'        => null,
            'observaciones'=> 'Celo observado; animal presentó inquietud y monta.',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        /* ── 2. Servicio ──────────────────────────────────────────────── */
        $evServicioId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'    => $madreId,
            'lote_id'      => $loteId,
            'user_id'      => null,
            'tipo_evento'  => 'servicio',
            'fecha'        => $servicioFecha->toDateString(),
            'costo'        => null,
            'observaciones'=> null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('servicio_reproductivos')->insert([
            'evento_id'       => $evServicioId,
            'macho_id'        => $padreId,
            'tipo_servicio'   => $p['tipo_servicio'],
            'pajilla_codigo'  => null,
            'pajilla_raza'    => null,
            'pajilla_origen'  => null,
            'tecnico_id'      => null,
            'tecnico_externo' => null,
            'numero_servicio' => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        /* ── 3. Diagnóstico de gestación (positivo) ───────────────────── */
        $evDiagId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'    => $madreId,
            'lote_id'      => $loteId,
            'user_id'      => null,
            'tipo_evento'  => 'diagnostico',
            'fecha'        => $diagFecha->toDateString(),
            'costo'        => null,
            'observaciones'=> 'Diagnóstico positivo. Animal integrada a lote de vientres gestantes.',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('diagnostico_gestacions')->insert([
            'evento_id'                => $evDiagId,
            'servicio_evento_id'       => $evServicioId,
            'metodo'                   => 'tacto_rectal',
            'resultado'                => 'positivo',
            'dias_gestacion_estimados' => 30,   // evaluado a los 30 días del servicio
            'fecha_probable_parto'     => $servicioFecha->copy()->addDays(self::GESTACION)->toDateString(),
            'veterinario_id'           => null,
            'veterinario_externo'      => 'MVZ Externo',
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        /* ── 4. Parto ─────────────────────────────────────────────────── */
        $evPartoId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'    => $madreId,
            'lote_id'      => $loteId,
            'user_id'      => null,
            'tipo_evento'  => 'parto',
            'fecha'        => $partoFecha->toDateString(),
            'costo'        => null,
            'observaciones'=> $complicaciones ? $detalleComp : null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $partoId = DB::table('partos')->insertGetId([
            'evento_id'             => $evPartoId,
            'servicio_evento_id'    => $evServicioId,
            'tipo_parto'            => $p['tipo_parto'],
            'asistencia_requerida'  => $asistencia,
            'complicaciones'        => $complicaciones,
            'detalle_complicaciones'=> $detalleComp,
            'numero_crias'          => count($p['crias']),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        /* ── 5. Crías ─────────────────────────────────────────────────── */
        foreach ($p['crias'] as $cria) {
            DB::table('crias')->insert([
                'parto_id'        => $partoId,
                'animal_id'       => $animals[$cria['arete']] ?? null,
                'sexo'            => $cria['sexo'],
                'peso_nacimiento' => $cria['peso'],
                'condicion'       => 'vivo',
                'arete_temporal'  => null,
                'observaciones'   => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}