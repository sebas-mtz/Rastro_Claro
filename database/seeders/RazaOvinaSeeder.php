<?php

namespace Database\Seeders;

use App\Models\Raza;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo de razas ovinas para cada cuenta.
 * Idempotente: no duplica ni pisa las razas que el usuario haya editado.
 */
class RazaOvinaSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user) {
            foreach (Raza::CATALOGO_INICIAL as [$nombre, $origen, $aptitud]) {
                Raza::withoutGlobalScope('owner')->firstOrCreate(
                    ['owner_id' => $user->id, 'nombre' => $nombre],
                    ['origen' => $origen, 'aptitud' => $aptitud, 'activo' => true]
                );
            }
        });

        $this->command?->info('Catálogo de razas ovinas sembrado.');
    }
}
