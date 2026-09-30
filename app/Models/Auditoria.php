<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un movimiento de la bitácora. Solo se escribe; nunca se edita ni se borra
 * desde la aplicación (no hay ruta que lo permita).
 */
class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    /** La bitácora no se actualiza: solo lleva fecha de creación. */
    public const UPDATED_AT = null;

    // ── Acciones sobre usuarios ──────────────────────────────────────────
    public const USUARIO_CREADO = 'usuario_creado';
    public const USUARIO_EDITADO = 'usuario_editado';
    public const ROL_CAMBIADO = 'rol_cambiado';
    public const USUARIO_ACTIVADO = 'usuario_activado';
    public const USUARIO_DESACTIVADO = 'usuario_desactivado';
    public const PASSWORD_RESTABLECIDA = 'password_restablecida';
    public const PERMISOS_CAMBIADOS = 'permisos_cambiados';

    // ── Acciones sobre la configuración del sistema ──────────────────────
    public const CONFIGURACION_CAMBIADA = 'configuracion_cambiada';
    public const FORMULA_COSTOS_MODIFICADA = 'formula_costos_modificada';
    public const VALOR_VALUACION_MODIFICADO = 'valor_valuacion_modificado';
    public const CATALOGO_MODIFICADO = 'catalogo_modificado';

    public const ACCIONES = [
        self::USUARIO_CREADO => 'Creación de usuario',
        self::USUARIO_EDITADO => 'Edición de usuario',
        self::ROL_CAMBIADO => 'Cambio de rol',
        self::USUARIO_ACTIVADO => 'Activación',
        self::USUARIO_DESACTIVADO => 'Desactivación',
        self::PASSWORD_RESTABLECIDA => 'Restablecimiento de contraseña',
        self::PERMISOS_CAMBIADOS => 'Cambio de permisos',
        self::CONFIGURACION_CAMBIADA => 'Cambio de configuración',
        self::FORMULA_COSTOS_MODIFICADA => 'Modificación de fórmulas de costos',
        self::VALOR_VALUACION_MODIFICADO => 'Modificación de valores de valuación',
        self::CATALOGO_MODIFICADO => 'Modificación de catálogo',
    ];

    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'afectado_id',
        'afectado_nombre',
        'accion',
        'descripcion',
        'valor_anterior',
        'valor_nuevo',
        'entidad_tipo',
        'entidad_id',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'valor_anterior' => 'array',
        'valor_nuevo' => 'array',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'accion_legible',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function afectado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'afectado_id');
    }

    public function getAccionLegibleAttribute(): string
    {
        return self::ACCIONES[$this->accion] ?? $this->accion;
    }

    public function scopeDeAccion($query, ?string $accion)
    {
        return $query->when($accion, fn ($q) => $q->where('accion', $accion));
    }

    public function scopeEntreFechas($query, ?string $desde, ?string $hasta)
    {
        return $query
            ->when($desde, fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('created_at', '<=', $hasta));
    }
}
