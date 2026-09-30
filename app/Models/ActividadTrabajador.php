<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trabajo realizado por una persona sobre el rebaño.
 *
 * El costo de mano de obra vive aquí y se refleja en la tabla `costos` mediante
 * el morph origen_tipo/origen_id, que es lo que impide contarlo dos veces en la
 * valuación de un ejemplar.
 */
class ActividadTrabajador extends Model
{
    use HasFactory;

    protected $table = 'actividades_trabajador';

    /** Jornada estándar del campo, usada para convertir entre hora y jornada. */
    public const HORAS_POR_JORNADA = 8;

    public const PAGO_HORA = 'hora';
    public const PAGO_JORNADA = 'jornada';

    public const MODALIDADES_PAGO = [
        self::PAGO_HORA => 'Por hora',
        self::PAGO_JORNADA => 'Por jornada',
    ];

    public const TIPOS = [
        'vacunacion' => 'Aplicación de vacunas',
        'tratamiento' => 'Tratamiento',
        'desparasitacion' => 'Desparasitación',
        'alimentacion' => 'Alimentación',
        'limpieza' => 'Limpieza',
        'pesaje' => 'Pesaje',
        'revision' => 'Revisión de animales',
        'monta' => 'Monta',
        'confirmacion_gestacion' => 'Confirmación de gestación',
        'atencion_parto' => 'Atención de parto',
        'movimiento_lote' => 'Movimiento entre lotes',
        'faena' => 'Faena',
        'sacrificio' => 'Sacrificio',
        'transporte' => 'Transporte',
        'venta' => 'Venta',
        'mantenimiento' => 'Mantenimiento',
        'otra' => 'Otra actividad',
    ];

    protected $fillable = [
        'owner_id',
        'trabajador_id',
        'tipo_actividad',
        'animal_id',
        'lote_id',
        'faena_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'modalidad_pago',
        'horas_trabajadas',
        'jornadas',
        'costo_hora',
        'costo_jornada',
        'costo_total',
        'animales_atendidos',
        'costo_por_animal',
        'distribuir_entre_animales',
        'metodo_distribucion',
        'descripcion',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas_trabajadas' => 'decimal:2',
        'jornadas' => 'decimal:2',
        'costo_hora' => 'decimal:2',
        'costo_jornada' => 'decimal:2',
        'costo_total' => 'decimal:2',
        'costo_por_animal' => 'decimal:2',
        'animales_atendidos' => 'integer',
        'distribuir_entre_animales' => 'boolean',
    ];

    protected $appends = [
        'tipo_legible',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function faena(): BelongsTo
    {
        return $this->belongsTo(Faena::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** Costos generados por esta actividad (uno por ejemplar si se distribuyó). */
    public function costosGenerados(): MorphMany
    {
        return $this->morphMany(Costo::class, 'origen', 'origen_tipo', 'origen_id');
    }

    // ─── Accesores ────────────────────────────────────────────────────────

    public function getTipoLegibleAttribute(): string
    {
        return self::TIPOS[$this->tipo_actividad] ?? $this->tipo_actividad;
    }

    public function getModalidadLegibleAttribute(): string
    {
        return self::MODALIDADES_PAGO[$this->modalidad_pago] ?? $this->modalidad_pago;
    }

    // ─── Consultas ────────────────────────────────────────────────────────

    public function scopeEntreFechas($query, ?string $desde, ?string $hasta)
    {
        return $query
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));
    }

    public function scopeDeTipo($query, ?string $tipo)
    {
        return $query->when($tipo, fn ($q) => $q->where('tipo_actividad', $tipo));
    }
}
