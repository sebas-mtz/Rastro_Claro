<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            // Explícitos, para no depender del valor por omisión de la tabla:
            // el modelo recién creado no lo trae cargado y quedaría en null.
            'role' => \App\Models\User::ROLE_TRABAJADOR,
            'activo' => true,
        ];
    }

    /** Cuenta con acceso completo al sistema. */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\User::ROLE_SUPER_ADMIN,
        ]);
    }

    /** Cuenta con permisos operativos, sin administración de usuarios. */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Usuario con el correo sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
