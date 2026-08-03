<?php

namespace App\Providers;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Costo;
use App\Models\EventoSalud;
use App\Models\Tratamiento;
use App\Models\User;
use App\Observers\MovimientoLoteObserver;
use App\Observers\ValuacionRecalculoObserver;
use App\Support\ModuloSistema;
use App\Validation\TenantPresenceVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
                if ($cuentaId = self::cuentaActiva()) {
                    $builder->where($builder->qualifyColumn('owner_id'), $cuentaId);
                }
            });

            $modelClass::creating(function (Model $model): void {
                if ($model->getAttribute('owner_id') !== null) {
                    return;
                }

                if ($cuentaId = self::cuentaActiva()) {
                    $model->setAttribute('owner_id', $cuentaId);
                }
            });
        }

        // Recálculo automático de cotizaciones cuando cambia un gasto del animal.
        // Cada recálculo deja constancia en el historial de precios.
        foreach ([Costo::class, EventoSalud::class, Tratamiento::class, Alimentacion::class] as $modelClass) {
            $modelClass::observe(ValuacionRecalculoObserver::class);
        }

        // Conserva el lote anterior de cada ejemplar antes de sobrescribirlo.
        Animal::observe(MovimientoLoteObserver::class);

        $this->definirGatesCriticos();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Rancho sobre el que trabaja quien hizo la petición.
     *
     * Es el valor con el que se filtra y se sella `owner_id` en todos los
     * módulos. Antes se usaba Auth::id() directamente, lo que ataba cada
     * usuario a su propio rancho; ahora se pregunta a qué cuenta pertenece,
     * de modo que varias personas puedan trabajar sobre el mismo rebaño.
     *
     * Devuelve null sin sesión: sin usuario no se filtra nada, igual que antes
     * (los comandos de consola y las tareas programadas sellan owner_id a mano,
     * ver GenerarAlimentacionesProgramadas y EjecutarProgramacionesAlimentacion).
     *
     * Auth::user() no añade consultas: el guard cachea la instancia durante la
     * petición, y la tabla users no participa del scope de tenencia.
     */
    public static function cuentaActiva(): ?int
    {
        return Auth::user()?->cuentaId();
    }

    /**
     * Acciones que afectan a todo el sistema, no solo a un registro.
     *
     * Se declaran como Gates con nombre para que la comprobación sea la misma
     * en la ruta, en el controlador y en lo que se envía al navegador, y para
     * que quede en un solo lugar qué se considera crítico.
     */
    private function definirGatesCriticos(): void
    {
        $soloSuperAdmin = fn (User $user): bool => $user->isSuperAdmin();

        $criticos = [
            'gestionar-usuarios',        // altas, bajas, roles y contraseñas
            'configurar-sistema',        // configuración general
            'administrar-catalogos',     // catálogos críticos (razas, puestos)
            'modificar-formulas',        // fórmulas de costos y valuación
            'configurar-margenes',       // márgenes genéticos
            'configurar-plus-reproductivo',
            'ver-auditoria',
            'exportar-datos-sensibles',
            'corregir-movimientos-economicos',
            'reabrir-ventas',
        ];

        foreach ($criticos as $habilidad) {
            Gate::define($habilidad, $soloSuperAdmin);
        }

        // Acceso a un módulo, para usarlo desde controladores y vistas con la
        // misma respuesta que da el middleware.
        Gate::define(
            'modulo',
            fn (User $user, string $modulo, string $accion = ModuloSistema::VER) => $user->puede($modulo, $accion)
        );
    }
}
