<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AnimalsSeeder — 7 generaciones de genealogía ovina
 * ─────────────────────────────────────────────────
 * Estrategia anti-consanguinidad:
 *   • Se mantienen DOS líneas genéticas (A y B) que se cruzan entre generaciones.
 *   • En generaciones pares se introduce un "semental externo" (comprado) sin
 *     padres conocidos para renovar la base genética.
 *   • Los sementales de cada generación se cruzan con borregas de la línea opuesta.
 *
 * Conteo total: ~59 animales distribuidos en G1–G7
 *
 * Lotes utilizados:
 *   Vientres en Producción | Sementales | Reemplazos | Crías Hembras
 *
 * IMPORTANTE: Ejecutar DESPUÉS de LotesSeeder.
 */
class AnimalSeeder extends Seeder
{
    public function run(): void
    {
        /* ── Lotes ───────────────────────────────────────────────────────── */
        $lotes = DB::table('lotes')->pluck('id', 'nombre');

        $lV  = $lotes['Vientres en Producción']; // borregas adultas
        $lS  = $lotes['Sementales'];             // machos reproductores
        $lR  = $lotes['Reemplazos'];             // jóvenes seleccionados
        $lCH = $lotes['Crías Hembras'];          // crías recientes


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 1 — Fundadores (2017)
         * Sin padre_id ni madre_id (origen externo / desconocido)
         * Línea A: raza Pelibuey  |  Línea B: raza Kathadin
         ══════════════════════════════════════════════════════════════════ */

        // — Sementales fundadores —
        $g1_sA1 = $this->a('S17-001', 'Trueno',    'M', 'Ovino', 'Pelibuey', '2017-02-05', 72.5, 3.5, 'Reproductor', $lS);
        $g1_sA2 = $this->a('S17-002', 'Relámpago', 'M', 'Ovino', 'Kathadin', '2017-03-12', 68.0, 3.5, 'Reproductor', $lS);

        // — Borregas Grupo A (se cruzarán con sementales B) —
        $g1_hA1 = $this->a('B17-001', 'Luna',     'F', 'Ovino', 'Pelibuey', '2017-01-20', 48.0, 3.0, 'vacia', $lV);
        $g1_hA2 = $this->a('B17-002', 'Nieve',    'F', 'Ovino', 'Pelibuey', '2017-02-14', 46.5, 3.0, 'vacia', $lV);
        $g1_hA3 = $this->a('B17-003', 'Canela',   'F', 'Ovino', 'Pelibuey', '2017-03-08', 50.0, 3.0, 'vacia', $lV);

        // — Borregas Grupo B (se cruzarán con sementales A) —
        $g1_hB1 = $this->a('B17-004', 'Paloma',   'F', 'Ovino', 'Kathadin', '2017-01-10', 52.0, 3.0, 'vacia', $lV);
        $g1_hB2 = $this->a('B17-005', 'Estrella', 'F', 'Ovino', 'Kathadin', '2017-04-02', 49.5, 3.0, 'vacia', $lV);
        $g1_hB3 = $this->a('B17-006', 'Perla',    'F', 'Ovino', 'Pelibuey', '2017-02-28', 47.0, 2.5, 'vacia', $lV);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 2 — 2018
         * Cruces: sA1×hB  y  sA2×hA  → hijos con genética mixta
         * + Semental externo introducido (Goliat / Dorper) sin padres
         ══════════════════════════════════════════════════════════════════ */

        // Semental externo comprado (no tiene padres registrados en el sistema)
        $g2_ext1 = $this->a('S18-EXT', 'Goliat', 'M', 'Ovino', 'Dorper', '2018-01-01', 85.0, 4.0, 'Reproductor', $lS);

        // Trueno(sA1) × Paloma(hB1) ──────────────────────────────────────
        $g2_sA  = $this->a('S18-001', 'Rayo',   'M', 'Ovino', 'Pelibuey x Kathadin', '2018-02-10', 65.0, 3.5, 'Reproductor', $lS,  $g1_sA1, $g1_hB1);
        $g2_hA1 = $this->a('B18-001', 'Rosa',   'F', 'Ovino', 'Pelibuey x Kathadin', '2018-02-10', 44.0, 3.0, 'vacia',         $lV,  $g1_sA1, $g1_hB1);

        // Trueno(sA1) × Estrella(hB2) ─────────────────────────────────────
        $g2_hA2 = $this->a('B18-002', 'Coral',  'F', 'Ovino', 'Pelibuey x Kathadin', '2018-03-05', 45.5, 3.0, 'vacia',         $lV,  $g1_sA1, $g1_hB2);

        // Relámpago(sA2) × Luna(hA1) ──────────────────────────────────────
        $g2_sB  = $this->a('S18-002', 'Apolo',  'M', 'Ovino', 'Kathadin x Pelibuey', '2018-04-01', 70.0, 3.5, 'Reproductor', $lS,  $g1_sA2, $g1_hA1);
        $g2_hB1 = $this->a('B18-003', 'Diana',  'F', 'Ovino', 'Kathadin x Pelibuey', '2018-04-01', 46.0, 3.0, 'vacia',         $lV,  $g1_sA2, $g1_hA1);

        // Relámpago(sA2) × Nieve(hA2) ─────────────────────────────────────
        $g2_hB2 = $this->a('B18-004', 'Niebla', 'F', 'Ovino', 'Kathadin x Pelibuey', '2018-02-20', 44.0, 3.0, 'vacia',         $lV,  $g1_sA2, $g1_hA2);

        // Relámpago(sA2) × Canela(hA3) ────────────────────────────────────
        $g2_hB3 = $this->a('B18-005', 'Miel',   'F', 'Ovino', 'Kathadin x Pelibuey', '2018-05-10', 45.0, 2.5, 'vacia',         $lV,  $g1_sA2, $g1_hA3);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 3 — 2019
         * Rayo(g2_sA) hijo de [sA1×hB] se cruza con hijas de [sA2×hA] ✓
         * Apolo(g2_sB) hijo de [sA2×hA] se cruza con hijas de [sA1×hB] ✓
         * Goliat(ext) sin parentesco → cruces libres
         ══════════════════════════════════════════════════════════════════ */

        // Rayo × Diana(hB1) — padres distintos ✓
        $g3_s1  = $this->a('S19-001', 'Zeus',  'M', 'Ovino', 'Pelibuey x Kathadin', '2019-01-15', 72.0, 3.5, 'Reproductor', $lS, $g2_sA, $g2_hB1);
        $g3_h1  = $this->a('B19-001', 'Azul',  'F', 'Ovino', 'Pelibuey x Kathadin', '2019-01-15', 45.0, 3.0, 'vacia',         $lV, $g2_sA, $g2_hB1);

        // Rayo × Niebla(hB2) ──────────────────────────────────────────────
        $g3_h2  = $this->a('B19-002', 'Brisa', 'F', 'Ovino', 'Pelibuey x Kathadin', '2019-02-20', 43.5, 3.0, 'vacia',         $lV, $g2_sA, $g2_hB2);

        // Apolo × Rosa(hA1) — padres distintos ✓
        $g3_h3  = $this->a('B19-003', 'Flora', 'F', 'Ovino', 'Kathadin x Pelibuey', '2019-03-08', 44.0, 3.0, 'vacia',         $lV, $g2_sB, $g2_hA1);
        $g3_h4  = $this->a('B19-004', 'Jade',  'F', 'Ovino', 'Kathadin x Pelibuey', '2019-04-12', 46.0, 3.0, 'vacia',         $lV, $g2_sB, $g2_hA1);

        // Goliat(ext1) × Coral(hA2) — línea Dorper ────────────────────────
        $g3_h5  = $this->a('B19-005', 'Rubí',  'F', 'Ovino', 'Dorper x Pelibuey',   '2019-02-14', 48.0, 3.0, 'vacia',         $lV, $g2_ext1, $g2_hA2);

        // Goliat(ext1) × Miel(hB3) ────────────────────────────────────────
        $g3_h6  = $this->a('B19-006', 'Topaz', 'F', 'Ovino', 'Dorper x Kathadin',   '2019-03-25', 47.0, 3.0, 'vacia',         $lV, $g2_ext1, $g2_hB3);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 4 — 2020
         * Zeus(g3_s1): hijo de [Rayo×Diana].  Ares se cruza con borregas de línea Goliat ✓
         * + Semental externo Goliat II (Dorper, sin padres registrados)
         ══════════════════════════════════════════════════════════════════ */

        $g4_ext2 = $this->a('S20-EXT', 'Goliat II', 'M', 'Ovino', 'Dorper', '2019-06-01', 82.0, 4.0, 'Reproductor', $lS);

        // Zeus × Flora(h3) — Flora es hija de Apolo×Rosa, diferente de Zeus ✓
        $g4_s1  = $this->a('S20-001', 'Ares',   'M', 'Ovino', 'Pelibuey x Kathadin', '2020-01-20', 68.0, 3.5, 'Reproductor', $lS, $g3_s1, $g3_h3);
        $g4_h1  = $this->a('B20-001', 'Rocío',  'F', 'Ovino', 'Pelibuey x Kathadin', '2020-01-20', 44.0, 3.0, 'vacia',         $lV, $g3_s1, $g3_h3);
        $g4_h2  = $this->a('B20-002', 'Lluvia', 'F', 'Ovino', 'Pelibuey x Kathadin', '2020-02-14', 43.5, 3.0, 'vacia',         $lV, $g3_s1, $g3_h4);

        // Zeus × Rubí(h5) — Rubí es hija de Goliat×Coral ✓
        $g4_h3  = $this->a('B20-003', 'Perla',  'F', 'Ovino', 'Dorper x Pelibuey',   '2020-03-05', 47.5, 3.0, 'vacia',         $lV, $g3_s1, $g3_h5);

        // Goliat II × Azul(h1) y Brisa(h2) — Azul/Brisa hijas de Rayo, distintas de Zeus ✓
        $g4_h4  = $this->a('B20-004', 'Selva',  'F', 'Ovino', 'Dorper x Pelibuey',   '2020-01-10', 46.0, 3.0, 'vacia',         $lV, $g4_ext2, $g3_h1);
        $g4_h5  = $this->a('B20-005', 'Sierra', 'F', 'Ovino', 'Dorper x Pelibuey',   '2020-02-28', 45.0, 3.0, 'vacia',         $lV, $g4_ext2, $g3_h2);

        // Goliat II × Topaz(h6) — hija de Goliat I×Miel ✓
        $g4_h6  = $this->a('B20-006', 'Ámbar',  'F', 'Ovino', 'Dorper x Kathadin',   '2020-04-10', 48.0, 3.0, 'vacia',         $lV, $g4_ext2, $g3_h6);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 5 — 2021
         * Ares(g4_s1): hijo de [Zeus×Flora]. Se cruza con línea Goliat II ✓
         * + Semental externo Sansón (Suffolk) para ampliar base genética
         ══════════════════════════════════════════════════════════════════ */

        $g5_ext3 = $this->a('S21-EXT', 'Sansón', 'M', 'Ovino', 'Suffolk', '2020-08-01', 90.0, 4.0, 'Reproductor', $lS);

        // Ares × Selva(h4) — Selva es hija de GoliatII×Azul, distinta de Ares ✓
        $g5_s1  = $this->a('S21-001', 'Héctor', 'M', 'Ovino', 'Pelibuey x Dorper',  '2021-01-12', 70.0, 3.5, 'Reproductor', $lS, $g4_s1, $g4_h4);
        $g5_h1  = $this->a('B21-001', 'Amber',  'F', 'Ovino', 'Pelibuey x Dorper',  '2021-01-12', 45.5, 3.0, 'vacia',         $lV, $g4_s1, $g4_h4);

        // Ares × Sierra(h5) ────────────────────────────────────────────────
        $g5_h2  = $this->a('B21-002', 'Nube',   'F', 'Ovino', 'Pelibuey x Dorper',  '2021-02-20', 44.0, 3.0, 'vacia',         $lV, $g4_s1, $g4_h5);

        // Ares × Ámbar(h6) ────────────────────────────────────────────────
        $g5_h3  = $this->a('B21-003', 'Duna',   'F', 'Ovino', 'Pelibuey x Dorper',  '2021-03-15', 46.0, 3.0, 'vacia',         $lV, $g4_s1, $g4_h6);

        // Sansón × Rocío(h1), Lluvia(h2), Perla(h3) — línea Zeus×Flora ✓
        $g5_h4  = $this->a('B21-004', 'Oliva',  'F', 'Ovino', 'Suffolk x Pelibuey', '2021-01-25', 47.0, 3.0, 'vacia',         $lV, $g5_ext3, $g4_h1);
        $g5_h5  = $this->a('B21-005', 'Mirra',  'F', 'Ovino', 'Suffolk x Pelibuey', '2021-03-08', 46.5, 3.0, 'vacia',         $lV, $g5_ext3, $g4_h2);
        $g5_h6  = $this->a('B21-006', 'Cedra',  'F', 'Ovino', 'Suffolk x Dorper',   '2021-02-12', 48.0, 3.0, 'vacia',         $lV, $g5_ext3, $g4_h3);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 6 — 2022
         * Héctor(g5_s1): hijo de [Ares×Selva]. Se cruza con borregas de línea Sansón ✓
         * + Semental externo Moisés (Dorper, sin padres)
         ══════════════════════════════════════════════════════════════════ */

        $g6_ext4 = $this->a('S22-EXT', 'Moisés', 'M', 'Ovino', 'Dorper', '2021-05-01', 88.0, 4.0, 'Reproductor', $lS);

        // Héctor × Oliva(h4) — Oliva es hija de Sansón×Rocío, diferente a Héctor ✓
        $g6_s1  = $this->a('S22-001', 'Aquiles', 'M', 'Ovino', 'Pelibuey x Suffolk',  '2022-01-08', 67.0, 3.5, 'Reproductor', $lS, $g5_s1, $g5_h4);
        $g6_h1  = $this->a('B22-001', 'Dulce',   'F', 'Ovino', 'Pelibuey x Suffolk',  '2022-01-08', 44.5, 3.0, 'vacia',         $lV, $g5_s1, $g5_h4);

        // Héctor × Mirra(h5) ───────────────────────────────────────────────
        $g6_h2  = $this->a('B22-002', 'Valeria', 'F', 'Ovino', 'Pelibuey x Suffolk',  '2022-02-14', 45.0, 3.0, 'vacia',         $lV, $g5_s1, $g5_h5);

        // Héctor × Cedra(h6) ───────────────────────────────────────────────
        $g6_h3  = $this->a('B22-003', 'Palma',   'F', 'Ovino', 'Pelibuey x Suffolk',  '2022-03-20', 46.0, 3.0, 'vacia',         $lV, $g5_s1, $g5_h6);

        // Moisés × Amber(h1), Nube(h2), Duna(h3) — línea Ares×Selva/Sierra ✓
        $g6_h4  = $this->a('B22-004', 'Mirto',   'F', 'Ovino', 'Dorper x Pelibuey',   '2022-01-20', 47.0, 3.0, 'vacia',         $lV, $g6_ext4, $g5_h1);
        $g6_h5  = $this->a('B22-005', 'Nimfa',   'F', 'Ovino', 'Dorper x Pelibuey',   '2022-03-05', 45.5, 3.0, 'vacia',         $lV, $g6_ext4, $g5_h2);
        $g6_h6  = $this->a('B22-006', 'Naiara',  'F', 'Ovino', 'Dorper x Pelibuey',   '2022-02-10', 46.5, 3.0, 'vacia',         $lV, $g6_ext4, $g5_h3);
        $g6_h7  = $this->a('B22-007', 'Miriam',  'F', 'Ovino', 'Dorper x Suffolk',    '2022-04-15', 44.0, 3.0, 'vacia',         $lV, $g6_ext4, $g5_h6);


        /* ══════════════════════════════════════════════════════════════════
         * GENERACIÓN 7 — 2023–2024 (Animales actuales)
         * Aquiles(g6_s1): hijo de [Héctor×Oliva]. Se cruza con borregas de línea Moisés ✓
         * + Semental externo Elías (Suffolk x Dorper, sin padres) para G7
         ══════════════════════════════════════════════════════════════════ */

        $g7_ext5 = $this->a('S23-EXT', 'Elías', 'M', 'Ovino', 'Suffolk x Dorper', '2022-09-01', 92.0, 4.0, 'Reproductor', $lS);

        // Aquiles × Nimfa(h5) — Nimfa es hija de Moisés×Nube, diferente a Aquiles ✓
        $g7_s1  = $this->a('S23-001', 'Titán II', 'M', 'Ovino', 'Pelibuey x Dorper',  '2023-01-10', 60.0, 3.5, 'reemplazo',   $lR, $g6_s1, $g6_h5);
        $g7_h1  = $this->a('B23-001', 'Zara',     'F', 'Ovino', 'Pelibuey x Dorper',  '2023-01-10', 38.0, 3.0, 'reemplazo',   $lR, $g6_s1, $g6_h5);

        // Aquiles × Naiara(h6) ─────────────────────────────────────────────
        $g7_h2  = $this->a('B23-002', 'Lara',     'F', 'Ovino', 'Pelibuey x Dorper',  '2023-02-14', 37.5, 3.0, 'reemplazo',   $lR, $g6_s1, $g6_h6);

        // Aquiles × Miriam(h7) ─────────────────────────────────────────────
        $g7_h3  = $this->a('B23-003', 'Naomi',    'F', 'Ovino', 'Pelibuey x Dorper',  '2023-03-20', 39.0, 3.0, 'reemplazo',   $lR, $g6_s1, $g6_h7);
        $g7_h4  = $this->a('B23-004', 'Vega',     'F', 'Ovino', 'Pelibuey x Dorper',  '2023-02-28', 38.5, 3.0, 'reemplazo',   $lR, $g6_s1, $g6_h7);

        // Elías × Dulce(h1), Valeria(h2), Palma(h3) — línea Héctor×Oliva/Mirra ✓
        $g7_s2  = $this->a('S23-002', 'Balam',    'M', 'Ovino', 'Suffolk x Pelibuey', '2023-01-25', 58.0, 3.5, 'reemplazo',   $lR, $g7_ext5, $g6_h1);
        $g7_h5  = $this->a('B23-005', 'Iris II',  'F', 'Ovino', 'Suffolk x Pelibuey', '2023-01-25', 37.0, 3.0, 'reemplazo',   $lR, $g7_ext5, $g6_h1);
        $g7_h6  = $this->a('B23-006', 'Aurora',   'F', 'Ovino', 'Suffolk x Pelibuey', '2023-02-10', 38.0, 3.0, 'reemplazo',   $lR, $g7_ext5, $g6_h2);
        $g7_h7  = $this->a('B23-007', 'Selene',   'F', 'Ovino', 'Suffolk x Pelibuey', '2023-03-05', 39.5, 3.0, 'reemplazo',   $lR, $g7_ext5, $g6_h3);

        // Elías × Mirto(h4) — Mirto es hija de Moisés×Amber ✓
        $g7_h8  = $this->a('B24-001', 'Celeste',  'F', 'Ovino', 'Suffolk x Dorper',   '2024-01-15', 35.0, 3.0, 'En crecimiento',        $lCH, $g7_ext5, $g6_h4);
    }


    /* ──────────────────────────────────────────────────────────────────────
     * Helper: inserta un animal y retorna su ID
     *
     * @param  string   $arete       Número de arete (único)
     * @param  string   $alias       Nombre o apodo
     * @param  string   $sexo        'M' | 'F'
     * @param  string   $especie     Ej: 'Ovino'
     * @param  string   $raza        Ej: 'Pelibuey', 'Dorper x Pelibuey'
     * @param  string   $fechaNac    Formato 'Y-m-d'
     * @param  float    $peso        Peso en kg
     * @param  float    $bcs         Body Condition Score (ej: 3.0)
     * @param  string   $estado      Estado productivo
     * @param  int|null $loteId      FK a lotes.id
     * @param  int|null $padreId     FK a animals.id (padre)
     * @param  int|null $madreId     FK a animals.id (madre)
     * @return int                   ID del registro insertado
     * ──────────────────────────────────────────────────────────────────── */
    private function a(
        string  $arete,
        ?string $alias,
        string  $sexo,
        string  $especie,
        string  $raza,
        string  $fechaNac,
        float   $peso,
        float   $bcs,
        string  $estado,
        ?int    $loteId,
        ?int    $padreId = null,
        ?int    $madreId = null
    ): int {
        return DB::table('animals')->insertGetId([
            'especie'           => $especie,
            'raza'              => $raza,
            'arete'             => $arete,
            'alias'             => $alias,
            'sexo'              => $sexo,
            'fecha_nac'         => $fechaNac,
            'peso'              => $peso,
            'BCS'               => $bcs,
            'estado_productivo' => $estado,
            'lote_id'           => $loteId,
            'padre_id'          => $padreId,
            'madre_id'          => $madreId,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}