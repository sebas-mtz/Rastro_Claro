<?php

namespace Database\Seeders;

use App\Models\Pajilla;
use App\Models\Termo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PajillaSeeder extends Seeder
{
    public function run(): void
    {
        $termoPrincipal  = Termo::where('codigo', 'T-001')->first();
        $termoSecundario = Termo::where('codigo', 'T-002')->first();

        // Pegaso ya fue insertado por DonadorExternoSeeder (corre primero)
        $pegasoId = DB::table('donadores_externos')
            ->where('codigo', 'DON-001')
            ->value('id');

        $pajillas = [
            /*
             * LOTE-A — Pegaso (DON-001)
             * PAJ-2024-001 usada el 2024-06-15 en IATF sobre Dulce (B22-001)
             * PAJ-2024-002 usada el 2024-06-15 en IATF sobre Valeria (B22-002)
             * PAJ-2024-003 sigue disponible como reserva
             */
            [
                'termo_id'           => $termoPrincipal->id,
                'animal_id'          => null,
                'donador_externo_id' => $pegasoId,
                'codigo'             => 'PAJ-2024-001',
                'lote'               => 'LOTE-A',
                'fecha_ingreso'      => '2024-01-15',
                'fecha_vencimiento'  => '2026-01-15',
                'fecha_utilizacion'  => '2024-06-15',
                'estado'             => 'utilizada',
                'observaciones'      => 'IATF sobre Dulce (B22-001). Donador: Pegaso (DON-001). Resultado: parto simple el 2024-11-09.',
            ],
            [
                'termo_id'           => $termoPrincipal->id,
                'animal_id'          => null,
                'donador_externo_id' => $pegasoId,
                'codigo'             => 'PAJ-2024-002',
                'lote'               => 'LOTE-A',
                'fecha_ingreso'      => '2024-01-15',
                'fecha_vencimiento'  => '2026-01-15',
                'fecha_utilizacion'  => '2024-06-15',
                'estado'             => 'utilizada',
                'observaciones'      => 'IATF sobre Valeria (B22-002). Donador: Pegaso (DON-001). Resultado: parto gemelar el 2024-11-09.',
            ],
            [
                'termo_id'           => $termoPrincipal->id,
                'animal_id'          => null,
                'donador_externo_id' => $pegasoId,
                'codigo'             => 'PAJ-2024-003',
                'lote'               => 'LOTE-A',
                'fecha_ingreso'      => '2024-01-15',
                'fecha_vencimiento'  => '2026-01-15',
                'fecha_utilizacion'  => null,
                'estado'             => 'disponible',
                'observaciones'      => 'Pajilla de Pegaso (DON-001). Reserva para próximo ciclo IATF.',
            ],

            /*
             * LOTE-B — sin donador asignado (origen genético no vinculado a Pegaso)
             * PAJ-2024-004 venció el 2025-03-10, pendiente de baja
             */
            [
                'termo_id'           => $termoPrincipal->id,
                'animal_id'          => null,
                'donador_externo_id' => null,
                'codigo'             => 'PAJ-2024-004',
                'lote'               => 'LOTE-B',
                'fecha_ingreso'      => '2024-03-10',
                'fecha_vencimiento'  => '2025-03-10',
                'fecha_utilizacion'  => null,
                'estado'             => 'vencida',
                'observaciones'      => 'Pajilla vencida, pendiente de baja.',
            ],

            /*
             * LOTE-C — Pegaso (DON-001)
             * PAJ-2024-005 usada el 2024-09-15 en IATF sobre Palma (B22-003)
             * PAJ-2024-006 sin donador asignado, disponible
             */
            [
                'termo_id'           => $termoSecundario->id,
                'animal_id'          => null,
                'donador_externo_id' => $pegasoId,
                'codigo'             => 'PAJ-2024-005',
                'lote'               => 'LOTE-C',
                'fecha_ingreso'      => '2024-06-01',
                'fecha_vencimiento'  => '2026-06-01',
                'fecha_utilizacion'  => '2024-09-15',
                'estado'             => 'utilizada',
                'observaciones'      => 'IATF sobre Palma (B22-003). Donador: Pegaso (DON-001). Resultado: parto simple el 2025-02-09.',
            ],
            [
                'termo_id'           => $termoSecundario->id,
                'animal_id'          => null,
                'donador_externo_id' => null,
                'codigo'             => 'PAJ-2024-006',
                'lote'               => 'LOTE-C',
                'fecha_ingreso'      => '2024-06-01',
                'fecha_vencimiento'  => '2026-06-01',
                'fecha_utilizacion'  => null,
                'estado'             => 'disponible',
                'observaciones'      => null,
            ],

            /*
             * LOTE-D — sin donador asignado, lote nuevo 2025
             */
            [
                'termo_id'           => $termoSecundario->id,
                'animal_id'          => null,
                'donador_externo_id' => null,
                'codigo'             => 'PAJ-2025-001',
                'lote'               => 'LOTE-D',
                'fecha_ingreso'      => '2025-02-20',
                'fecha_vencimiento'  => '2027-02-20',
                'fecha_utilizacion'  => null,
                'estado'             => 'disponible',
                'observaciones'      => 'Lote nuevo ingresado en febrero 2025.',
            ],
        ];

        foreach ($pajillas as $pajilla) {
            Pajilla::create($pajilla);
        }
    }
}