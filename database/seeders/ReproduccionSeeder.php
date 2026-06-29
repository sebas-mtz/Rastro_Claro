<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReproduccionSeeder extends Seeder
{
    private const GESTACION = 147;

    public function run(): void
    {
        $animals  = DB::table('animals')->pluck('id', 'arete');
        $lotes    = DB::table('lotes')->pluck('id', 'nombre');
        $pajillas = DB::table('pajillas')->pluck('id', 'codigo'); // lookup por código

        $lV = $lotes['Vientres en Producción'];

        $partosData = [

            /* ══════ G2 — 2018 ══════════════════════════════════════════ */

            ['madre' => 'B17-001', 'padre' => 'S17-002', 'parto_fecha' => '2018-04-01', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S18-002', 'sexo' => 'macho', 'peso' => 4.2], ['arete' => 'B18-003', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B17-002', 'padre' => 'S17-002', 'parto_fecha' => '2018-02-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B18-004', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B17-003', 'padre' => 'S17-002', 'parto_fecha' => '2018-05-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B18-005', 'sexo' => 'hembra', 'peso' => 3.7]]],

            ['madre' => 'B17-004', 'padre' => 'S17-001', 'parto_fecha' => '2018-02-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S18-001', 'sexo' => 'macho', 'peso' => 4.5], ['arete' => 'B18-001', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B17-005', 'padre' => 'S17-001', 'parto_fecha' => '2018-03-05', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B18-002', 'sexo' => 'hembra', 'peso' => 4.1]]],

            /* ══════ G3 — 2019 ══════════════════════════════════════════ */

            ['madre' => 'B18-003', 'padre' => 'S18-001', 'parto_fecha' => '2019-01-15', 'tipo_parto' => 'distocico', 'tipo_servicio' => 'monta_natural', 'asistencia' => true,
                'crias' => [['arete' => 'S19-001', 'sexo' => 'macho', 'peso' => 4.0], ['arete' => 'B19-001', 'sexo' => 'hembra', 'peso' => 3.7]]],

            ['madre' => 'B18-004', 'padre' => 'S18-001', 'parto_fecha' => '2019-02-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B19-002', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B18-001', 'padre' => 'S18-002', 'parto_fecha' => '2019-03-08', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B19-003', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B18-001', 'padre' => 'S18-002', 'parto_fecha' => '2019-04-12', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B19-004', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B18-002', 'padre' => 'S18-EXT', 'parto_fecha' => '2019-02-14', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B19-005', 'sexo' => 'hembra', 'peso' => 4.3]]],

            ['madre' => 'B18-005', 'padre' => 'S18-EXT', 'parto_fecha' => '2019-03-25', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B19-006', 'sexo' => 'hembra', 'peso' => 4.1]]],

            /* ══════ G4 — 2020 ══════════════════════════════════════════ */

            ['madre' => 'B19-003', 'padre' => 'S19-001', 'parto_fecha' => '2020-01-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S20-001', 'sexo' => 'macho', 'peso' => 4.4], ['arete' => 'B20-001', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B19-004', 'padre' => 'S19-001', 'parto_fecha' => '2020-02-14', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B20-002', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B19-005', 'padre' => 'S19-001', 'parto_fecha' => '2020-03-05', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B20-003', 'sexo' => 'hembra', 'peso' => 4.2]]],

            ['madre' => 'B19-001', 'padre' => 'S20-EXT', 'parto_fecha' => '2020-01-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B20-004', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B19-002', 'padre' => 'S20-EXT', 'parto_fecha' => '2020-02-28', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B20-005', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B19-006', 'padre' => 'S20-EXT', 'parto_fecha' => '2020-04-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B20-006', 'sexo' => 'hembra', 'peso' => 4.1]]],

            /* ══════ G5 — 2021 ══════════════════════════════════════════ */

            ['madre' => 'B20-004', 'padre' => 'S20-001', 'parto_fecha' => '2021-01-12', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S21-001', 'sexo' => 'macho', 'peso' => 4.3], ['arete' => 'B21-001', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B20-005', 'padre' => 'S20-001', 'parto_fecha' => '2021-02-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B21-002', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B20-006', 'padre' => 'S20-001', 'parto_fecha' => '2021-03-15', 'tipo_parto' => 'distocico', 'tipo_servicio' => 'monta_natural', 'asistencia' => true,
                'crias' => [['arete' => 'B21-003', 'sexo' => 'hembra', 'peso' => 3.6]]],

            ['madre' => 'B20-001', 'padre' => 'S21-EXT', 'parto_fecha' => '2021-01-25', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B21-004', 'sexo' => 'hembra', 'peso' => 4.1]]],

            ['madre' => 'B20-002', 'padre' => 'S21-EXT', 'parto_fecha' => '2021-03-08', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B21-005', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B20-003', 'padre' => 'S21-EXT', 'parto_fecha' => '2021-02-12', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B21-006', 'sexo' => 'hembra', 'peso' => 4.2]]],

            /* ══════ G6 — 2022 ══════════════════════════════════════════ */

            ['madre' => 'B21-004', 'padre' => 'S21-001', 'parto_fecha' => '2022-01-08', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S22-001', 'sexo' => 'macho', 'peso' => 4.4], ['arete' => 'B22-001', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B21-005', 'padre' => 'S21-001', 'parto_fecha' => '2022-02-14', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-002', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B21-006', 'padre' => 'S21-001', 'parto_fecha' => '2022-03-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-003', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B21-001', 'padre' => 'S22-EXT', 'parto_fecha' => '2022-01-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-004', 'sexo' => 'hembra', 'peso' => 4.1]]],

            ['madre' => 'B21-002', 'padre' => 'S22-EXT', 'parto_fecha' => '2022-03-05', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-005', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B21-003', 'padre' => 'S22-EXT', 'parto_fecha' => '2022-02-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-006', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B21-006', 'padre' => 'S22-EXT', 'parto_fecha' => '2022-04-15', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B22-007', 'sexo' => 'hembra', 'peso' => 3.8]]],

            /* ══════ G7 — 2023-2024 ═════════════════════════════════════ */

            ['madre' => 'B22-005', 'padre' => 'S22-001', 'parto_fecha' => '2023-01-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S23-001', 'sexo' => 'macho', 'peso' => 4.2], ['arete' => 'B23-001', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B22-006', 'padre' => 'S22-001', 'parto_fecha' => '2023-02-14', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B23-002', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B22-007', 'padre' => 'S22-001', 'parto_fecha' => '2023-02-28', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B23-004', 'sexo' => 'hembra', 'peso' => 3.7]]],

            ['madre' => 'B22-007', 'padre' => 'S22-001', 'parto_fecha' => '2023-03-20', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B23-003', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B22-001', 'padre' => 'S23-EXT', 'parto_fecha' => '2023-01-25', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'S23-002', 'sexo' => 'macho', 'peso' => 4.1], ['arete' => 'B23-005', 'sexo' => 'hembra', 'peso' => 3.8]]],

            ['madre' => 'B22-002', 'padre' => 'S23-EXT', 'parto_fecha' => '2023-02-10', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B23-006', 'sexo' => 'hembra', 'peso' => 3.9]]],

            ['madre' => 'B22-003', 'padre' => 'S23-EXT', 'parto_fecha' => '2023-03-05', 'tipo_parto' => 'normal', 'tipo_servicio' => 'monta_natural',
                'crias' => [['arete' => 'B23-007', 'sexo' => 'hembra', 'peso' => 4.0]]],

            ['madre' => 'B22-004', 'padre' => 'S23-EXT', 'parto_fecha' => '2024-01-15',
                'tipo_parto' => 'cesarea', 'tipo_servicio' => 'monta_natural',
                'asistencia' => true, 'complicaciones' => true,
                'detalle_complicaciones' => 'Presentación podálica, se realizó cesárea de emergencia. Cría recuperada con éxito.',
                'crias' => [['arete' => 'B24-001', 'sexo' => 'hembra', 'peso' => 3.5]]],

            /* ══════ G8 — 2024-2025  (IATF × Pegaso DON-001) ═══════════
             *
             * 'padre'          => null   (donador externo, no es animal del predio)
             * 'pajilla_codigo' => string (solo para resolver pajilla_id en PHP;
             *                             no se inserta directamente en la BD)
             *
             * servicio_reproductivos.pajilla_id → pajillas.donador_externo_id
             *                                   → donador_externos (Pegaso)
             ══════════════════════════════════════════════════════════════ */

            // Dulce (B22-001) × Pegaso → Nova  — 2024-06-15 + 147d = 2024-11-09
            [
                'madre'           => 'B22-001',
                'padre'           => null,
                'pajilla_codigo'  => 'PAJ-2024-001',
                'tecnico_externo' => 'MVZ Ricardo Núñez – Especialista en IA',
                'obs_servicio'    => 'IATF Lote 1 (jun-2024). Sincronización con esponja + eCG. Pajilla de Pegaso (DON-001).',
                'parto_fecha'     => '2024-11-09',
                'tipo_parto'      => 'normal',
                'tipo_servicio'   => 'iatf',
                'crias'           => [
                    ['arete' => 'B24-002', 'sexo' => 'hembra', 'peso' => 3.8,
                     'obs'   => 'Nova – hija de Dulce (B22-001) y Pegaso (DON-001) vía IATF PAJ-2024-001.'],
                ],
            ],

            // Valeria (B22-002) × Pegaso → Orión + Lyra  — 2024-06-15 + 147d = 2024-11-09
            [
                'madre'           => 'B22-002',
                'padre'           => null,
                'pajilla_codigo'  => 'PAJ-2024-002',
                'tecnico_externo' => 'MVZ Ricardo Núñez – Especialista en IA',
                'obs_servicio'    => 'IATF Lote 1 (jun-2024). Parto gemelar confirmado por laboratorio d30. Pajilla de Pegaso (DON-001).',
                'parto_fecha'     => '2024-11-09',
                'tipo_parto'      => 'normal',
                'tipo_servicio'   => 'iatf',
                'crias'           => [
                    ['arete' => 'S24-001', 'sexo' => 'macho',  'peso' => 4.1,
                     'obs'   => 'Orión – hijo de Valeria (B22-002) y Pegaso (DON-001) vía IATF PAJ-2024-002. Gemelar.'],
                    ['arete' => 'B24-003', 'sexo' => 'hembra', 'peso' => 3.7,
                     'obs'   => 'Lyra – hija de Valeria (B22-002) y Pegaso (DON-001) vía IATF PAJ-2024-002. Gemelar.'],
                ],
            ],

            // Palma (B22-003) × Pegaso → Vela  — 2024-09-15 + 147d = 2025-02-09
            [
                'madre'           => 'B22-003',
                'padre'           => null,
                'pajilla_codigo'  => 'PAJ-2024-005',
                'tecnico_externo' => 'MVZ Ricardo Núñez – Especialista en IA',
                'obs_servicio'    => 'IATF Lote 2 (sep-2024). Sincronización con CIDR + eCG. Pajilla de Pegaso (DON-001).',
                'parto_fecha'     => '2025-02-09',
                'tipo_parto'      => 'normal',
                'tipo_servicio'   => 'iatf',
                'crias'           => [
                    ['arete' => 'B25-001', 'sexo' => 'hembra', 'peso' => 3.9,
                     'obs'   => 'Vela – hija de Palma (B22-003) y Pegaso (DON-001) vía IATF PAJ-2024-005.'],
                ],
            ],
        ];

        foreach ($partosData as $parto) {
            $this->crearCicloReproductivo($parto, $animals, $pajillas, $lV);
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
     * Modos soportados:
     *   A) Monta natural → $p['padre'] = 'arete'  /  $p['pajilla_codigo'] ausente
     *   B) IATF          → $p['padre'] = null      /  $p['pajilla_codigo'] = 'PAJ-...'
     * ──────────────────────────────────────────────────────────────────── */
    private function crearCicloReproductivo(
        array  $p,
        object $animals,
        object $pajillas,
        int    $loteId
    ): void {
        $madreId = $animals[$p['madre']];

        // null cuando el padre es donador externo (IATF)
        $padreId = ($p['padre'] ?? null) !== null
            ? ($animals[$p['padre']] ?? null)
            : null;

        // Resuelve el ID real de la pajilla; null en monta natural
        $pajillaId = isset($p['pajilla_codigo'])
            ? ($pajillas[$p['pajilla_codigo']] ?? null)
            : null;

        $partoFecha    = Carbon::parse($p['parto_fecha']);
        $servicioFecha = $partoFecha->copy()->subDays(self::GESTACION);
        $celoFecha     = $servicioFecha->copy()->subDays(7);
        $diagFecha     = $servicioFecha->copy()->addDays(30);

        $esIatf         = ($p['tipo_servicio'] === 'iatf');
        $asistencia     = $p['asistencia']        ?? ($p['tipo_parto'] !== 'normal');
        $complicaciones = $p['complicaciones']     ?? false;
        $detalleComp    = $p['detalle_complicaciones'] ?? null;

        /* ── 1. Celo ──────────────────────────────────────────────────── */
        DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'     => $madreId,
            'lote_id'       => $loteId,
            'user_id'       => null,
            'tipo_evento'   => 'celo',
            'fecha'         => $celoFecha->toDateString(),
            'costo'         => null,
            'observaciones' => $esIatf
                ? 'Celo sincronizado por protocolo IATF; respuesta adecuada confirmada antes de la inseminación.'
                : 'Celo observado; animal presentó inquietud y monta.',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        /* ── 2. Servicio ──────────────────────────────────────────────── */
        $evServicioId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'     => $madreId,
            'lote_id'       => $loteId,
            'user_id'       => null,
            'tipo_evento'   => 'servicio',
            'fecha'         => $servicioFecha->toDateString(),
            'costo'         => null,
            'observaciones' => $p['obs_servicio'] ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Solo se insertan las columnas que realmente existen en la tabla
        DB::table('servicio_reproductivos')->insert([
            'evento_id'       => $evServicioId,
            'macho_id'        => $padreId,    // null en IATF
            'tipo_servicio'   => $p['tipo_servicio'],
            'pajilla_id'      => $pajillaId,  // null en monta natural
            'tecnico_id'      => null,
            'tecnico_externo' => $p['tecnico_externo'] ?? null,
            'numero_servicio' => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        /* ── 3. Diagnóstico de gestación ──────────────────────────────── */
        $evDiagId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'     => $madreId,
            'lote_id'       => $loteId,
            'user_id'       => null,
            'tipo_evento'   => 'diagnostico',
            'fecha'         => $diagFecha->toDateString(),
            'costo'         => null,
            'observaciones' => $esIatf
                ? 'Gestación confirmada por ecografía a los 30 días post-IATF. Animal integrada a lote de gestantes.'
                : 'Diagnóstico positivo. Animal integrada a lote de vientres gestantes.',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('diagnostico_gestacions')->insert([
            'evento_id'                => $evDiagId,
            'servicio_evento_id'       => $evServicioId,
            'metodo'                   => $esIatf ? 'laboratorio' : 'tacto_rectal',
            'resultado'                => 'positivo',
            'dias_gestacion_estimados' => 30,
            'fecha_probable_parto'     => $servicioFecha->copy()->addDays(self::GESTACION)->toDateString(),
            'veterinario_id'           => null,
            'veterinario_externo'      => $p['tecnico_externo'] ?? 'MVZ Externo',
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        /* ── 4. Parto ─────────────────────────────────────────────────── */
        $evPartoId = DB::table('evento_reproductivos')->insertGetId([
            'hembra_id'     => $madreId,
            'lote_id'       => $loteId,
            'user_id'       => null,
            'tipo_evento'   => 'parto',
            'fecha'         => $partoFecha->toDateString(),
            'costo'         => null,
            'observaciones' => $complicaciones ? $detalleComp : null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $partoId = DB::table('partos')->insertGetId([
            'evento_id'              => $evPartoId,
            'servicio_evento_id'     => $evServicioId,
            'tipo_parto'             => $p['tipo_parto'],
            'asistencia_requerida'   => $asistencia,
            'complicaciones'         => $complicaciones,
            'detalle_complicaciones' => $detalleComp,
            'numero_crias'           => count($p['crias']),
            'created_at'             => now(),
            'updated_at'             => now(),
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
                'observaciones'   => $cria['obs'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}