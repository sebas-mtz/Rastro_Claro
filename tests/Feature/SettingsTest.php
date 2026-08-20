<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_account_and_operational_settings_without_changing_email(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/dashboard')
            ->patch('/settings', [
                'name' => 'Rancho El Roble',
                'email' => 'ignored@example.com',
                'location' => 'Tepatitlán, Jalisco',
                'weight_unit' => 'lb',
                'currency' => 'USD',
                'theme' => 'dark',
                'date_format' => 'named_month',
                'animal_age_format' => 'decimal',
                'gestation_days' => 280,
                'monthly_financial_goal' => 125000,
                'inventory_capacity_kg' => 5000,
                'daily_feed_kg' => 275,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $user->refresh();

        $this->assertSame('Rancho El Roble', $user->name);
        $this->assertSame('original@example.com', $user->email);
        $this->assertSame('Tepatitlán, Jalisco', $user->settings['location']);
        $this->assertSame('lb', $user->settings['weight_unit']);
        $this->assertSame('USD', $user->settings['currency']);
        $this->assertSame('dark', $user->settings['theme']);
        $this->assertSame('named_month', $user->settings['date_format']);
        $this->assertSame('decimal', $user->settings['animal_age_format']);
        $this->assertSame(280, $user->settings['gestation_days']);
    }

    public function test_settings_reject_unsupported_formats_and_out_of_range_values(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings', [
                'name' => 'Usuario',
                'location' => '',
                'weight_unit' => 'stone',
                'currency' => 'BTC',
                'theme' => 'neon',
                'date_format' => 'long',
                'animal_age_format' => 'weeks',
                'gestation_days' => 500,
                'monthly_financial_goal' => -1,
                'inventory_capacity_kg' => 0,
                'daily_feed_kg' => 0,
            ]);

        $response->assertSessionHasErrors([
            'weight_unit',
            'currency',
            'theme',
            'date_format',
            'animal_age_format',
            'gestation_days',
            'monthly_financial_goal',
            'inventory_capacity_kg',
            'daily_feed_kg',
        ]);
    }
}
