<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlimentacionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Punto de partida: 6 meses atrás ──────────────────────────────────
        $inicio = Carbon::now()->subMonths(6)->startOfDay();
        $hoy    = Carbon::now()->startOfDay();

        // ── Tomamos animales y lotes existentes ───────────────────────────────
        $animales = DB::table('animals')->pluck('id')->toArray();
        $lotes    = DB::table('lotes')->pluck('id')->toArray();

        if (empty($animales) && empty($lotes)) {
            $this->command->warn('No hay animales ni lotes. Corre primero AnimalSeeder.');
            return;
        }

        // ─────────────────────────────────────────────────────────────────────
        // 1. INSUMOS
        // ─────────────────────────────────────────────────────────────────────
        $insumos = [
            [
                'nombre'         => 'Maíz molido',
                'tipo'           => 'Energético',
                'marca'          => null,
                'existencias'    => 2500.00,
                'unidad'         => 'kg',
                'costo_promedio' => 5.20,
                'MS'             => 88.00,
                'PB'             => 8.50,
                'EM'             => 3.30,
                'FDN'            => 12.00,
                'auto_rellenar'  => true,
                'cantidad_rellenado' => 1000.00,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Pasta de soya',
                'tipo'           => 'Proteico',
                'marca'          => 'Cargill',
                'existencias'    => 800.00,
                'unidad'         => 'kg',
                'costo_promedio' => 12.50,
                'MS'             => 89.00,
                'PB'             => 44.00,
                'EM'             => 3.10,
                'FDN'            => 14.00,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Alfalfa achicalada',
                'tipo'           => 'Forraje',
                'marca'          => null,
                'existencias'    => 5000.00,
                'unidad'         => 'kg',
                'costo_promedio' => 3.80,
                'MS'             => 90.00,
                'PB'             => 18.00,
                'EM'             => 2.50,
                'FDN'            => 40.00,
                'auto_rellenar'  => true,
                'cantidad_rellenado' => 2000.00,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Sorgo forrajero',
                'tipo'           => 'Forraje',
                'marca'          => null,
                'existencias'    => 3200.00,
                'unidad'         => 'kg',
                'costo_promedio' => 2.90,
                'MS'             => 92.00,
                'PB'             => 7.00,
                'EM'             => 2.80,
                'FDN'            => 55.00,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Melaza de caña',
                'tipo'           => 'Energético',
                'marca'          => null,
                'existencias'    => 400.00,
                'unidad'         => 'kg',
                'costo_promedio' => 4.10,
                'MS'             => 75.00,
                'PB'             => 4.00,
                'EM'             => 3.20,
                'FDN'            => null,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Minerales bovinos',
                'tipo'           => 'Mineral',
                'marca'          => 'Purina',
                'existencias'    => 150.00,
                'unidad'         => 'kg',
                'costo_promedio' => 28.00,
                'MS'             => null,
                'PB'             => null,
                'EM'             => null,
                'FDN'            => null,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Salvado de trigo',
                'tipo'           => 'Concentrado',
                'marca'          => null,
                'existencias'    => 600.00,
                'unidad'         => 'kg',
                'costo_promedio' => 6.70,
                'MS'             => 88.00,
                'PB'             => 15.50,
                'EM'             => 2.90,
                'FDN'            => 42.00,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Heno de pasto estrella',
                'tipo'           => 'Forraje',
                'marca'          => null,
                'existencias'    => 0.00,   // sin stock — desactivado por uso
                'unidad'         => 'kg',
                'costo_promedio' => 2.20,
                'MS'             => 91.00,
                'PB'             => 6.00,
                'EM'             => 2.10,
                'FDN'            => 70.00,
                'auto_rellenar'  => false,
                'cantidad_rellenado' => null,
                'activo'         => false,  // desactivado para probar "Ver desactivados"
                'desactivado_at' => Carbon::now()->subMonths(1),
            ],
        ];

        $now = Carbon::now();
        DB::table('inventario_insumos')->insert(
            array_map(fn($i) => array_merge($i, [
                'created_at' => $inicio,
                'updated_at' => $now,
                'desactivado_at' => $i['desactivado_at'] ?? null,
            ]), $insumos)
        );

        $insumoIds = DB::table('inventario_insumos')->pluck('id', 'nombre');

        $this->command->info('✓ Insumos insertados: ' . count($insumos));

        // ─────────────────────────────────────────────────────────────────────
        // 2. RACIONES
        // ─────────────────────────────────────────────────────────────────────
        // Ración 1: Crecimiento (maíz + soya + alfalfa + minerales)
        $racionCrecimientoId = DB::table('racions')->insertGetId([
            'nombre'      => 'Ración de crecimiento',
            'MS'          => 89.50,
            'PB'          => 16.80,
            'EM'          => 3.10,
            'FDN'         => 28.00,
            'costo_total' => 142.50,
            'precio_kg'   => 7.13,
            'activo'      => true,
            'created_at'  => $inicio,
            'updated_at'  => $now,
        ]);

        // Ración 2: Mantenimiento (forraje pesado + melaza + minerales)
        $racionMantenimientoId = DB::table('racions')->insertGetId([
            'nombre'      => 'Ración de mantenimiento',
            'MS'          => 90.00,
            'PB'          => 10.00,
            'EM'          => 2.70,
            'FDN'         => 48.00,
            'costo_total' => 89.00,
            'precio_kg'   => 4.45,
            'activo'      => true,
            'created_at'  => $inicio,
            'updated_at'  => $now,
        ]);

        // Ración 3: Alta producción (soya + alfalfa + salvado)
        $racionProduccionId = DB::table('racions')->insertGetId([
            'nombre'      => 'Ración alta producción',
            'MS'          => 89.00,
            'PB'          => 22.00,
            'EM'          => 2.85,
            'FDN'         => 36.00,
            'costo_total' => 198.00,
            'precio_kg'   => 9.90,
            'activo'      => true,
            'created_at'  => $inicio,
            'updated_at'  => $now,
        ]);

        // Ración 4: archivada (inactiva)
        $racionArchivaId = DB::table('racions')->insertGetId([
            'nombre'       => 'Ración experimental v1',
            'MS'           => 88.00,
            'PB'           => 13.00,
            'EM'           => 2.60,
            'FDN'          => 50.00,
            'costo_total'  => 110.00,
            'precio_kg'    => 5.50,
            'activo'       => false,
            'archivado_at' => Carbon::now()->subMonths(3),
            'created_at'   => $inicio->copy()->subMonths(1),
            'updated_at'   => $now,
        ]);

        $this->command->info('✓ Raciones insertadas');

        // ─── Pivot racion_insumo ──────────────────────────────────────────────
        $pivotRacionInsumo = [
            // Crecimiento: 50% maíz, 20% soya, 25% alfalfa, 5% minerales → por kg de ración
            ['racion_id' => $racionCrecimientoId,    'inventario_insumo_id' => $insumoIds['Maíz molido'],       'cantidad' => 0.50],
            ['racion_id' => $racionCrecimientoId,    'inventario_insumo_id' => $insumoIds['Pasta de soya'],     'cantidad' => 0.20],
            ['racion_id' => $racionCrecimientoId,    'inventario_insumo_id' => $insumoIds['Alfalfa achicalada'],'cantidad' => 0.25],
            ['racion_id' => $racionCrecimientoId,    'inventario_insumo_id' => $insumoIds['Minerales bovinos'], 'cantidad' => 0.05],

            // Mantenimiento: 40% sorgo, 35% alfalfa, 20% melaza, 5% minerales
            ['racion_id' => $racionMantenimientoId,  'inventario_insumo_id' => $insumoIds['Sorgo forrajero'],   'cantidad' => 0.40],
            ['racion_id' => $racionMantenimientoId,  'inventario_insumo_id' => $insumoIds['Alfalfa achicalada'],'cantidad' => 0.35],
            ['racion_id' => $racionMantenimientoId,  'inventario_insumo_id' => $insumoIds['Melaza de caña'],    'cantidad' => 0.20],
            ['racion_id' => $racionMantenimientoId,  'inventario_insumo_id' => $insumoIds['Minerales bovinos'], 'cantidad' => 0.05],

            // Alta producción: 30% soya, 40% alfalfa, 25% salvado, 5% minerales
            ['racion_id' => $racionProduccionId,     'inventario_insumo_id' => $insumoIds['Pasta de soya'],     'cantidad' => 0.30],
            ['racion_id' => $racionProduccionId,     'inventario_insumo_id' => $insumoIds['Alfalfa achicalada'],'cantidad' => 0.40],
            ['racion_id' => $racionProduccionId,     'inventario_insumo_id' => $insumoIds['Salvado de trigo'],  'cantidad' => 0.25],
            ['racion_id' => $racionProduccionId,     'inventario_insumo_id' => $insumoIds['Minerales bovinos'], 'cantidad' => 0.05],

            // Archivada: maíz + sorgo + melaza
            ['racion_id' => $racionArchivaId,        'inventario_insumo_id' => $insumoIds['Maíz molido'],       'cantidad' => 0.45],
            ['racion_id' => $racionArchivaId,        'inventario_insumo_id' => $insumoIds['Sorgo forrajero'],   'cantidad' => 0.45],
            ['racion_id' => $racionArchivaId,        'inventario_insumo_id' => $insumoIds['Melaza de caña'],    'cantidad' => 0.10],
        ];

        DB::table('racion_insumo')->insert(
            array_map(fn($r) => array_merge($r, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $pivotRacionInsumo)
        );

        $this->command->info('✓ Composición de raciones insertada');

        // ─────────────────────────────────────────────────────────────────────
        // 3. CONSUMOS (6 meses de historia)
        // ─────────────────────────────────────────────────────────────────────
        
$programaciones = [];

// Programación por lote: crecimiento
if (!empty($lotes)) {
    $programaciones[] = [
        'racion_id'    => $racionCrecimientoId,
        'animal_id'    => null,
        'lote_id'      => $lotes[0],
        'fecha_inicio' => $inicio->copy()->addDays(5)->toDateString(),
        'fecha_fin'    => null,
        'hora'         => '08:00:00',
        'cantidad'     => 2.50,
        'unidad'       => 'kg',
        'frecuencia'   => 'diaria',
        'activa'       => true,
        'notas'        => 'Ración diaria para animales ovinos en crecimiento.',
    ];
}

// Programación por lote: mantenimiento
if (isset($lotes[1])) {
    $programaciones[] = [
        'racion_id'    => $racionMantenimientoId,
        'animal_id'    => null,
        'lote_id'      => $lotes[1],
        'fecha_inicio' => $inicio->copy()->addDays(10)->toDateString(),
        'fecha_fin'    => null,
        'hora'         => '09:00:00',
        'cantidad'     => 2.00,
        'unidad'       => 'kg',
        'frecuencia'   => 'diaria',
        'activa'       => true,
        'notas'        => 'Ración diaria de mantenimiento para lote ovino adulto.',
    ];
}

// Programación individual para borregas en producción/lactancia
$hembras = DB::table('animals')
    ->where('especie', 'Ovino')
    ->where('sexo', 'F')
    ->whereNotIn('estado_productivo', ['vendido', 'sacrificado', 'faeneado', 'desecho'])
    ->limit(4)
    ->get();

foreach ($hembras as $hembra) {
    $programaciones[] = [
        'racion_id'    => $racionProduccionId,
        'animal_id'    => $hembra->id,
        'lote_id'      => null,
        'fecha_inicio' => $inicio->copy()->addDays(rand(15, 40))->toDateString(),
        'fecha_fin'    => null,
        'hora'         => '07:30:00',
        'cantidad'     => 1.80,
        'unidad'       => 'kg',
        'frecuencia'   => 'diaria',
        'activa'       => true,
        'notas'        => 'Alimentación individual para hembra ovina.',
    ];
}

$programacionesInsertadas = [];

foreach ($programaciones as $programacion) {
    $programacionId = DB::table('programacion_alimentacions')->insertGetId([
        'racion_id'    => $programacion['racion_id'],
        'animal_id'    => $programacion['animal_id'],
        'lote_id'      => $programacion['lote_id'],
        'fecha_inicio' => $programacion['fecha_inicio'],
        'fecha_fin'    => $programacion['fecha_fin'],
        'hora'         => $programacion['hora'],
        'cantidad'     => $programacion['cantidad'],
        'unidad'       => $programacion['unidad'],
        'frecuencia'   => $programacion['frecuencia'],
        'activa'       => $programacion['activa'],
        'notas'        => $programacion['notas'],
        'created_at'   => $now,
        'updated_at'   => $now,
    ]);

    $programacion['id'] = $programacionId;
    $programacionesInsertadas[] = $programacion;
}

$this->command->info('✓ Programaciones insertadas: ' . count($programacionesInsertadas));


// ─────────────────────────────────────────────────────────────────────
// 4. CONSUMOS DIARIOS GENERADOS DESDE PROGRAMACIONES
// ─────────────────────────────────────────────────────────────────────

$snapshots = [
    $racionCrecimientoId => [
        'composicion' => [
            ['insumo_id' => $insumoIds['Maíz molido'],        'nombre' => 'Maíz molido',        'cantidad' => 0.50, 'costo_promedio' => 5.20],
            ['insumo_id' => $insumoIds['Pasta de soya'],      'nombre' => 'Pasta de soya',      'cantidad' => 0.20, 'costo_promedio' => 12.50],
            ['insumo_id' => $insumoIds['Alfalfa achicalada'], 'nombre' => 'Alfalfa achicalada', 'cantidad' => 0.25, 'costo_promedio' => 3.80],
            ['insumo_id' => $insumoIds['Minerales bovinos'],  'nombre' => 'Minerales bovinos',  'cantidad' => 0.05, 'costo_promedio' => 28.00],
        ],
        'nutricion' => ['MS' => 89.50, 'PB' => 16.80, 'EM' => 3.10, 'FDN' => 28.00],
    ],
    $racionMantenimientoId => [
        'composicion' => [
            ['insumo_id' => $insumoIds['Sorgo forrajero'],    'nombre' => 'Sorgo forrajero',    'cantidad' => 0.40, 'costo_promedio' => 2.90],
            ['insumo_id' => $insumoIds['Alfalfa achicalada'], 'nombre' => 'Alfalfa achicalada', 'cantidad' => 0.35, 'costo_promedio' => 3.80],
            ['insumo_id' => $insumoIds['Melaza de caña'],     'nombre' => 'Melaza de caña',     'cantidad' => 0.20, 'costo_promedio' => 4.10],
            ['insumo_id' => $insumoIds['Minerales bovinos'],  'nombre' => 'Minerales bovinos',  'cantidad' => 0.05, 'costo_promedio' => 28.00],
        ],
        'nutricion' => ['MS' => 90.00, 'PB' => 10.00, 'EM' => 2.70, 'FDN' => 48.00],
    ],
    $racionProduccionId => [
        'composicion' => [
            ['insumo_id' => $insumoIds['Pasta de soya'],      'nombre' => 'Pasta de soya',      'cantidad' => 0.30, 'costo_promedio' => 12.50],
            ['insumo_id' => $insumoIds['Alfalfa achicalada'], 'nombre' => 'Alfalfa achicalada', 'cantidad' => 0.40, 'costo_promedio' => 3.80],
            ['insumo_id' => $insumoIds['Salvado de trigo'],   'nombre' => 'Salvado de trigo',   'cantidad' => 0.25, 'costo_promedio' => 6.70],
            ['insumo_id' => $insumoIds['Minerales bovinos'],  'nombre' => 'Minerales bovinos',  'cantidad' => 0.05, 'costo_promedio' => 28.00],
        ],
        'nutricion' => ['MS' => 89.00, 'PB' => 22.00, 'EM' => 2.85, 'FDN' => 36.00],
    ],
];

$consumos = [];

foreach ($programacionesInsertadas as $programacion) {
    $fechaInicio = Carbon::parse($programacion['fecha_inicio']);
    $fechaFin = $programacion['fecha_fin']
        ? Carbon::parse($programacion['fecha_fin'])
        : $hoy->copy();

    if ($fechaFin->gt($hoy)) {
        $fechaFin = $hoy->copy();
    }

    $fechaActual = $fechaInicio->copy();

    while ($fechaActual->lte($fechaFin)) {
        $cantidadBase = (float) $programacion['cantidad'];

        // Variación realista del consumo
        $factorConsumo = rand(88, 105) / 100;
        $cantidadConsumida = round($cantidadBase * $factorConsumo, 2);

        $snap = $snapshots[$programacion['racion_id']] ?? [
            'composicion' => [],
            'nutricion' => [],
        ];

        $consumos[] = [
            'fecha'                        => $fechaActual->toDateString(),
            'hora'                         => $programacion['hora'],
            'animal_id'                    => $programacion['animal_id'],
            'lote_id'                      => $programacion['lote_id'],
            'racion_id'                    => $programacion['racion_id'],
            'programacion_alimentacion_id' => $programacion['id'],
            'cantidad'                     => $cantidadConsumida,
            'unidad'                       => $programacion['unidad'],
            'tipo'                         => 'racion',
            'generado_automaticamente'     => true,
            'snapshot_composicion'         => json_encode($snap['composicion']),
            'snapshot_nutricion'           => json_encode($snap['nutricion']),
            'notas'                        => $cantidadConsumida < $cantidadBase
                ? 'Consumo automático parcial generado desde programación.'
                : 'Consumo automático generado desde programación.',
            'created_at'                   => $fechaActual->toDateTimeString(),
            'updated_at'                   => $fechaActual->toDateTimeString(),
        ];

        if ($programacion['frecuencia'] === 'una_vez') {
            break;
        }

        $fechaActual->addDay();
    }
}

foreach (array_chunk($consumos, 500) as $chunk) {
    DB::table('alimentacions')->insert($chunk);
}

$this->command->info('✓ Consumos insertados desde programaciones: ' . count($consumos));
$this->command->info('  Período: ' . $inicio->toDateString() . ' → ' . $hoy->toDateString());
$this->command->info('');
$this->command->info('Recuerda registrar DatabaseSeeder:');
$this->command->info('  $this->call(AlimentacionSeeder::class);');
    }
}