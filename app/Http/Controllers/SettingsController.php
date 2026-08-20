<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:150'],
            'weight_unit' => ['required', Rule::in(['kg', 'lb'])],
            'currency' => ['required', Rule::in(['MXN', 'USD', 'EUR', 'COP', 'ARS'])],
            'theme' => ['required', Rule::in(['light', 'dark'])],
            'date_format' => ['required', Rule::in(['numeric', 'named_month'])],
            'animal_age_format' => ['required', Rule::in(['decimal', 'words'])],
            'gestation_days' => ['required', 'integer', 'min:250', 'max:320'],
            'monthly_financial_goal' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'inventory_capacity_kg' => ['required', 'numeric', 'min:1', 'max:999999999.99'],
            'daily_feed_kg' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        unset($validated['name']);

        $user->settings = array_merge(
            User::defaultSettings(),
            $user->settings ?? [],
            $validated,
        );
        $user->save();

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
