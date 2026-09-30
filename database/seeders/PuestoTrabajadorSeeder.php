<?php

namespace Database\Seeders;

use App\Models\PuestoTrabajador;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo de puestos del rancho para cada cuenta.
 * Idempotente: no duplica ni pisa los puestos que el usuario haya editado.
 */
class PuestoTrabajadorSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user) {
            foreach (PuestoTrabajador::BASE as $puesto) {
                PuestoTrabajador::withoutGlobalScope('owner')->firstOrCreate(
                    ['owner_id' => $user->id, 'clave' => $puesto['clave']],
                    [
                        'nombre' => $puesto['nombre'],
                        'area' => $puesto['area'],
                        'activo' => true,
                    ]
                );
            }
        });

        $this->command?->info('Catálogo de puestos de trabajador sembrado.');
    }
}
