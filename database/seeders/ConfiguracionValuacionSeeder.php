<?php

namespace Database\Seeders;

use App\Models\ConfiguracionValuacion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Siembra los valores iniciales del plus reproductivo para cada cuenta.
 * Es idempotente: se puede correr varias veces sin duplicar ni pisar los
 * valores que el usuario ya haya ajustado.
 */
class ConfiguracionValuacionSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user) {
            foreach (ConfiguracionValuacion::PLUS_POR_DEFECTO as $clave => [$valor, $descripcion]) {
                ConfiguracionValuacion::withoutGlobalScope('owner')
                    ->firstOrCreate(
                        ['owner_id' => $user->id, 'clave' => $clave],
                        ['valor' => $valor, 'descripcion' => $descripcion]
                    );
            }
        });
    }
}
