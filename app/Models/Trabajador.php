<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persona que trabaja en el rancho.
 *
 * Un trabajador NO es una cuenta del sistema. Puede tener una (`user`), pero la
 * mayoría del personal de campo no inicia sesión y aun así su mano de obra debe
 * quedar registrada y costeada.
 */
class Trabajador extends Model
{
    use HasFactory;

    protected $table = 'trabajadores';

    /** Modalidades de contratación más comunes en el campo mexicano. */
    public const TIPOS_CONTRATACION = [
        'permanente' => 'Permanente',
        'temporal' => 'Temporal',
        'eventual' => 'Eventual',
        'por_jornada' => 'Por jornada',
        'destajo' => 'A destajo',
        'honorarios' => 'Honorarios',
        'familiar' => 'Apoyo familiar',
    ];

    /**
     * Campos con datos personales o salariales. El controlador los retira de la
     * respuesta cuando el usuario no tiene permiso para verlos.
     */
    public const CAMPOS_SENSIBLES = [
        'curp',
        'rfc',
        'direccion',
        'fecha_nacimiento',
        'sueldo',
        'costo_jornada',
        'costo_hora',
        'contacto_emergencia',
        'telefono_emergencia',
    ];

    protected $fillable = [
        'owner_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'curp',
        'rfc',
        'telefono',
        'email',
        'direccion',
        'fecha_nacimiento',
        'fecha_contratacion',
        'puesto_id',
        'area',
        'tipo_contratacion',
        'sueldo',
        'costo_jornada',
        'costo_hora',
        'horario',
        'activo',
        'fecha_baja',
        'motivo_baja',
        'contacto_emergencia',
        'telefono_emergencia',
        'observaciones',
        'user_id',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_contratacion' => 'date',
        'fecha_baja' => 'date',
        'activo' => 'boolean',
        'sueldo' => 'decimal:2',
        'costo_jornada' => 'decimal:2',
        'costo_hora' => 'decimal:2',
    ];

    protected $appends = [
        'nombre_completo',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajador::class, 'puesto_id');
    }

    /** Cuenta del sistema, cuando la persona tiene acceso. Opcional. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadTrabajador::class, 'trabajador_id');
    }

    public function costos(): HasMany
    {
        return $this->hasMany(Costo::class, 'trabajador_id');
    }

    // ─── Accesores ────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));
    }

    public function getPuestoNombreAttribute(): ?string
    {
        return $this->puesto?->nombre;
    }

    // ─── Consultas ────────────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, ?string $termino)
    {
        return $query->when($termino, function ($q) use ($termino) {
            $like = '%' . $termino . '%';

            $q->where(function ($sub) use ($like) {
                $sub->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('telefono', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        });
    }

    /**
     * Registros que impiden borrar físicamente al trabajador.
     * Devuelve el conteo por tipo para poder explicarle al usuario por qué.
     */
    public function registrosRelacionados(): array
    {
        return [
            'actividades' => $this->actividades()->count(),
            'costos' => $this->costos()->count(),
        ];
    }

    public function tieneRegistrosRelacionados(): bool
    {
        return array_sum($this->registrosRelacionados()) > 0;
    }

    /**
     * Tarifa por hora utilizable. Si no se capturó explícitamente pero sí el
     * costo por jornada, se deriva sobre una jornada de 8 horas.
     */
    public function tarifaHora(): ?float
    {
        if ($this->costo_hora !== null) {
            return (float) $this->costo_hora;
        }

        if ($this->costo_jornada !== null) {
            return round((float) $this->costo_jornada / ActividadTrabajador::HORAS_POR_JORNADA, 2);
        }

        return null;
    }

    public function tarifaJornada(): ?float
    {
        if ($this->costo_jornada !== null) {
            return (float) $this->costo_jornada;
        }

        if ($this->costo_hora !== null) {
            return round((float) $this->costo_hora * ActividadTrabajador::HORAS_POR_JORNADA, 2);
        }

        return null;
    }
}
