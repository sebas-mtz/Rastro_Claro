<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EventoSalud extends Model
{
    protected $table = 'eventos_salud';

    protected $fillable = [
        'animal_id',
        'lote_id',
        'tipo',
        'fecha_programada',
        'fecha_aplicacion',
        'diagnostico',
        'tratamiento',
        'vacuna_id',
        'dosis',
        'costo',
        'periodo_retiro_dias',
        'fecha_fin_retiro',
        'producto',
        'via_administracion',
        'lote_vacuna',
        'observaciones',
        'estado',
        'responsable',
        'user_id',
    ];

    protected $casts = [
        'fecha_programada'    => 'date',
        'fecha_aplicacion'    => 'date',
        'fecha_fin_retiro'    => 'date',
        'costo'               => 'decimal:2',
        'periodo_retiro_dias' => 'integer',
    ];

    /**
     * Calcula la fecha de fin del periodo de retiro a partir de la aplicación.
     * Se dispara al guardar para que el dato nunca quede desincronizado.
     */
    protected static function booted(): void
    {
        static::saving(function (EventoSalud $evento) {
            $inicio = $evento->fecha_aplicacion ?? $evento->fecha_programada;

            $evento->fecha_fin_retiro = $evento->periodo_retiro_dias && $inicio
                ? \Carbon\Carbon::parse($inicio)->copy()->addDays($evento->periodo_retiro_dias)
                : null;
        });
    }

    /** ¿El ejemplar sigue dentro del periodo de retiro? */
    public function getEnPeriodoRetiroAttribute(): bool
    {
        return $this->fecha_fin_retiro !== null
            && $this->fecha_fin_retiro->gte(now()->startOfDay());
    }

    public function getTipoLegibleAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    // Estados posibles como constantes para no usar strings mágicos en todo el código
    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_APLICADA   = 'aplicada';
    const ESTADO_VENCIDA    = 'vencida';

    const TIPO_CONSULTA     = 'consulta';
    const TIPO_VACUNACION   = 'vacunacion';
    const TIPO_REVISION     = 'revision';
    const TIPO_EMERGENCIA   = 'emergencia';

    // ── Tipos propios del manejo sanitario ovino ──────────────────────────
    const TIPO_DESPARASITACION      = 'desparasitacion';
    const TIPO_VITAMINAS            = 'vitaminas';
    const TIPO_REVISION_PEZUNAS     = 'revision_pezunas';
    const TIPO_RECORTE_PEZUNAS      = 'recorte_pezunas';
    const TIPO_BANO_EXTERNO         = 'bano_externo';
    const TIPO_MASTITIS             = 'mastitis';
    const TIPO_PROBLEMA_RESPIRATORIO = 'problema_respiratorio';
    const TIPO_PROBLEMA_DIGESTIVO   = 'problema_digestivo';
    const TIPO_PROBLEMA_REPRODUCTIVO = 'problema_reproductivo';
    const TIPO_LESION               = 'lesion';
    const TIPO_CIRUGIA              = 'cirugia';
    const TIPO_ESTUDIO              = 'estudio';

    /**
     * Catálogo completo de actividades sanitarias ovinas.
     *
     * El sistema solo registra y da seguimiento: no diagnostica por su cuenta.
     */
    public const TIPOS = [
        self::TIPO_VACUNACION            => 'Vacunación',
        self::TIPO_DESPARASITACION       => 'Desparasitación',
        self::TIPO_VITAMINAS             => 'Vitaminas / minerales',
        self::TIPO_REVISION_PEZUNAS      => 'Revisión de pezuñas',
        self::TIPO_RECORTE_PEZUNAS       => 'Recorte de pezuñas',
        self::TIPO_BANO_EXTERNO          => 'Baño o tratamiento externo',
        self::TIPO_MASTITIS              => 'Mastitis',
        self::TIPO_PROBLEMA_RESPIRATORIO => 'Problema respiratorio',
        self::TIPO_PROBLEMA_DIGESTIVO    => 'Problema digestivo',
        self::TIPO_PROBLEMA_REPRODUCTIVO => 'Problema reproductivo',
        self::TIPO_LESION                => 'Lesión',
        self::TIPO_CIRUGIA               => 'Cirugía',
        self::TIPO_ESTUDIO               => 'Estudio de laboratorio',
        self::TIPO_CONSULTA              => 'Consulta veterinaria',
        self::TIPO_REVISION              => 'Revisión general',
        self::TIPO_EMERGENCIA            => 'Emergencia',
    ];

    /** Vías de administración habituales en ovinos. */
    public const VIAS_ADMINISTRACION = [
        'subcutanea'    => 'Subcutánea',
        'intramuscular' => 'Intramuscular',
        'intravenosa'   => 'Intravenosa',
        'oral'          => 'Oral',
        'topica'        => 'Tópica',
        'intramamaria'  => 'Intramamaria',
        'otra'          => 'Otra',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'salud_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────
    public function scopeDeLote(Builder $query, int $loteId): Builder
    {
        return $query->where('lote_id', $loteId);
    }
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_VENCIDA);
    }

    public function scopeDeAnimal(Builder $query, int $animalId): Builder
    {
        return $query->where('animal_id', $animalId);
    }

    public function scopeVacunaciones(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_VACUNACION);
    }

    public function scopeProximas(Builder $query, int $dias = 7): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
                     ->whereBetween('fecha_programada', [
                         Carbon::today(),
                         Carbon::today()->addDays($dias),
                     ]);
    }
    // app/Models/EventoSalud.php

public static function sincronizarVencidos(): int
{
    return static::where('estado', self::ESTADO_PENDIENTE)
        ->whereDate('fecha_programada', '<', Carbon::today())
        ->update(['estado' => self::ESTADO_VENCIDA]);
}

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Marca el evento como aplicado y guarda la fecha real de aplicación.
     */
    public function marcarAplicada(?Carbon $fechaAplicacion = null): void
    {
        $this->update([
            'estado'           => self::ESTADO_APLICADA,
            'fecha_aplicacion' => $fechaAplicacion ?? Carbon::today(),
        ]);
    }

    /**
     * Determina si la fecha programada ya pasó y el evento sigue pendiente.
     */
    public function estaVencida(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && $this->fecha_programada->isPast();
    }
}