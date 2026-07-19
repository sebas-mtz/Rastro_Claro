<?php

namespace App\Providers;

use App\Validation\TenantPresenceVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new TenantPresenceVerifier($app['db']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The validation factory may already be resolved by another provider,
        // so replace its verifier explicitly during boot as well.
        $presenceVerifier = new TenantPresenceVerifier($this->app['db']);
        $this->app->instance('validation.presence', $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        foreach (config('tenancy.models', []) as $modelClass) {
            $modelClass::addGlobalScope('owner', function (Builder $builder): void {
                if ($ownerId = Auth::id()) {
                    $builder->where($builder->qualifyColumn('owner_id'), $ownerId);
                }
            });

            $modelClass::creating(function (Model $model): void {
                if (Auth::check() && $model->getAttribute('owner_id') === null) {
                    $model->setAttribute('owner_id', Auth::id());
                }
            });
        }

        Vite::prefetch(concurrency: 3);
    }
}
