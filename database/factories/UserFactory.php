<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        'name'              => fake()->name(),
        'email'             => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password'          => bcrypt('password'),
        'role'              => \App\Models\User::ROLE_ADMINISTRADOR,
        'activo'            => true,
        'plan'              => \App\Models\User::PLAN_NORMAL,
    ];
}

    public function trabajador(): static
    {
        return $this->state(['role' => \App\Models\User::ROLE_TRABAJADOR]);
    }

    public function premium(): static
    {
        return $this->state(['plan' => \App\Models\User::PLAN_PREMIUM]);
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
