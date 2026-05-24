<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ─── Roles ────────────────────────────────────────────────────────────────

    public const ROLE_ADMINISTRADOR = 'administrador';
    public const ROLE_ENCARGADO     = 'encargado';
    public const ROLE_TRABAJADOR    = 'trabajador';
    public const ROLE_SOLO_LECTURA  = 'solo_lectura';

    public const ROLES = [
        self::ROLE_ADMINISTRADOR,
        self::ROLE_ENCARGADO,
        self::ROLE_TRABAJADOR,
        self::ROLE_SOLO_LECTURA,
    ];

    // Compatibilidad con código anterior
    public const ROLE_ADMIN = self::ROLE_ADMINISTRADOR;
    public const ROLE_USER  = self::ROLE_TRABAJADOR;

    // ─── Planes ───────────────────────────────────────────────────────────────

    public const PLAN_NORMAL  = 'normal';
    public const PLAN_PREMIUM = 'premium';

    // ─── Permisos por rol ─────────────────────────────────────────────────────

    protected static array $permisos = [
        'animales' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'lotes' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'salud' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado', 'trabajador'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador', 'encargado'],
        ],
        'costos' => [
            'ver'      => ['administrador', 'encargado', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'alimentacion' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado', 'trabajador'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador', 'encargado'],
        ],
        'producciones' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado', 'trabajador'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'reproduccion' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'pesajes' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado', 'trabajador'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'ventas' => [
            'ver'      => ['administrador', 'encargado', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'faenas' => [
            'ver'      => ['administrador', 'encargado', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'sacrificios' => [
            'ver'      => ['administrador', 'encargado', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'trabajadores' => [
            'ver'      => ['administrador', 'encargado'],
            'crear'    => ['administrador'],
            'editar'   => ['administrador'],
            'eliminar' => ['administrador'],
        ],
        'inventario' => [
            'ver'      => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
            'crear'    => ['administrador', 'encargado'],
            'editar'   => ['administrador', 'encargado'],
            'eliminar' => ['administrador'],
        ],
        'genealogias' => [
            'ver' => ['administrador', 'encargado', 'trabajador', 'solo_lectura'],
        ],
        'predicciones' => [
            'ver' => ['administrador', 'encargado'],
        ],
        'historial' => [
            'ver' => ['administrador'],
        ],
        'admin' => [
            'ver'      => ['administrador'],
            'crear'    => ['administrador'],
            'editar'   => ['administrador'],
            'eliminar' => ['administrador'],
        ],
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'role',
        'plan',
        'activo',
        'creado_por',
        'ultimo_login',
        'email_verified_at',
        'must_change_password',
        'stripe_checkout_session_id',
        'premium_activated_at',
        'premium_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_checkout_session_id',
    ];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'ultimo_login'         => 'datetime',
        'premium_activated_at' => 'datetime',
        'premium_expires_at'   => 'datetime',
        'activo'               => 'boolean',
        'must_change_password' => 'boolean',
        'password'             => 'hashed',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function historialActividad(): HasMany
    {
        return $this->hasMany(HistorialActividad::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function trabajadoresCreados(): HasMany
    {
        return $this->hasMany(User::class, 'creado_por');
    }

    // ─── Helpers de rol ───────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMINISTRADOR;
    }

    public function isEncargado(): bool
    {
        return $this->role === self::ROLE_ENCARGADO;
    }

    public function isTrabajador(): bool
    {
        return $this->role === self::ROLE_TRABAJADOR;
    }

    public function isSoloLectura(): bool
    {
        return $this->role === self::ROLE_SOLO_LECTURA;
    }

    public function isUser(): bool
    {
        return in_array($this->role, [self::ROLE_TRABAJADOR, self::ROLE_SOLO_LECTURA], true);
    }

    public function puede(string $modulo, string $accion): bool
    {
        if (!$this->activo) {
            return false;
        }

        $roles = self::$permisos[$modulo][$accion] ?? [];

        return in_array($this->role, $roles, true);
    }

    public function permisosArray(): array
    {
        $resultado = [];

        foreach (self::$permisos as $modulo => $acciones) {
            foreach ($acciones as $accion => $roles) {
                if (in_array($this->role, $roles, true)) {
                    $resultado[] = "{$modulo}.{$accion}";
                }
            }
        }

        return $resultado;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMINISTRADOR => 'Administrador',
            self::ROLE_ENCARGADO     => 'Encargado',
            self::ROLE_TRABAJADOR    => 'Trabajador',
            self::ROLE_SOLO_LECTURA  => 'Solo lectura',
            default                  => 'Desconocido',
        };
    }

    // ─── Helpers de plan ──────────────────────────────────────────────────────

    public function isPremium(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->plan !== self::PLAN_PREMIUM) {
            return false;
        }

        if ($this->premium_expires_at !== null) {
            return $this->premium_expires_at->isFuture();
        }

        return true;
    }

    public function activatePremium(string $stripeSessionId, ?Carbon $expiresAt = null): void
    {
        $this->update([
            'plan'                       => self::PLAN_PREMIUM,
            'stripe_checkout_session_id' => $stripeSessionId,
            'premium_activated_at'       => now(),
            'premium_expires_at'         => $expiresAt,
        ]);
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            self::PLAN_PREMIUM => 'Premium',
            default            => 'Normal',
        };
    }
}