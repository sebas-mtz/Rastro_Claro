<?php

namespace Database\Seeders;

use App\Models\Termo;
use Illuminate\Database\Seeder;

class TermoSeeder extends Seeder
{
    public function run(): void
    {
        $termos = [
            [
                'codigo'      => 'T-001',
                'nombre'      => 'Termo Principal',
                'ubicacion'   => 'Bodega central',
                'capacidad'   => 500,
                'estado'      => 'activo',
                'descripcion' => 'Termo principal de almacenamiento de material genético.',
            ],
            [
                'codigo'      => 'T-002',
                'nombre'      => 'Termo Secundario',
                'ubicacion'   => 'Corral de reproducción',
                'capacidad'   => 250,
                'estado'      => 'activo',
                'descripcion' => 'Termo de uso operativo cerca del área de trabajo.',
            ],
            [
                'codigo'      => 'T-003',
                'nombre'      => 'Termo de Respaldo',
                'ubicacion'   => 'Bodega central',
                'capacidad'   => 300,
                'estado'      => 'inactivo',
                'descripcion' => 'Termo de respaldo, actualmente fuera de servicio para mantenimiento.',
            ],
        ];

        foreach ($termos as $termo) {
            Termo::create($termo);
        }
    }
}