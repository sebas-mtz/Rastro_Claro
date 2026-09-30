<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoteSeeder extends Seeder
{
    public function run(): void
    {
        $lotes = [
            [
                'nombre'         => 'Vientres en Producción',
                'corral_potrero' => 'Corral A',
                'responsable_id' => null,
            ],
            [
                'nombre'         => 'Sementales',
                'corral_potrero' => 'Corral B',
                'responsable_id' => null,
            ],
            [
                'nombre'         => 'Crías Hembras',
                'corral_potrero' => 'Potrero Norte',
                'responsable_id' => null,
            ],
            [
                'nombre'         => 'Crías Machos',
                'corral_potrero' => 'Potrero Sur',
                'responsable_id' => null,
            ],
            [
                'nombre'         => 'Reemplazos',
                'corral_potrero' => 'Corral C',
                'responsable_id' => null,
            ],
            [
                'nombre'         => 'Engorda',
                'corral_potrero' => 'Corral D',
                'responsable_id' => null,
            ],
        ];

        foreach ($lotes as $lote) {
            DB::table('lotes')->insert(array_merge($lote, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}