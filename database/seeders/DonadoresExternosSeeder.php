<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonadoresExternosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('donadores_externos')->insert([
            [
                'codigo'     => 'DON-001',
                'nombre'     => 'Pegaso',
                'raza'       => 'Dorper x Suffolk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}