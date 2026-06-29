<?php

namespace App\Models;
use App\Models\Tarea;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ----------- ROLES -----------
    public const ROLE_USER  = 'user';
    public const ROLE_ADMIN = 'admin';

    // ----------- PLANES -----------
    public const PLAN_NORMAL  = 'normal';
    public const PLAN_PREMIUM = 'premium';

    /**
     * Campos que se pueden asignar en masa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // permisos / negocio:
        'role',   // 'user' o 'admin'
        'plan',   // 'normal' o 'premium'
        'activo', // 1 / 0
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo'            => 'boolean',
    ];

    // ======== HELPERS DE ROLE ========

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser(): bool
    {
        // todo lo que no es admin lo tratamos como user
        return $this->role === self::ROLE_USER || $this->role === null;
    }

    public function tareasAsignadas()
{
    return $this->hasMany(Tarea::class, 'asignado_a');
}

public function tareasCreadas()
{
    return $this->hasMany(Tarea::class, 'creado_por');
}
    // ======== HELPERS DE PLAN ========

    public function isPremium(): bool
    {
        return $this->plan === self::PLAN_PREMIUM;
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            self::PLAN_PREMIUM => 'Premium',
            default             => 'Normal',
        };
    }
}
