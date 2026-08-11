<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuentaDestino = env('CONTROL_PARTOS_SEED_EMAIL');

        if ($cuentaDestino) {
            if (!User::where('email', $cuentaDestino)->exists()) {
                throw new \RuntimeException(
                    "No existe la cuenta {$cuentaDestino}. Se canceló el sembrado."
                );
            }

            return;
        }

        User::firstOrCreate(
            ['email' => 'demo@rastroclaro.test'],
            [
                'name' => 'Usuario Demo',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
