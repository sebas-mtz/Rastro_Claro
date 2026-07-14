<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Tratamiento extends Model
{
    protected $fillable = [
        'animal_id',
        'lote_id',
        'salud_id',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'notas',
        'responsable',
        'user_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    const ESTADO_ACTIVO     = 'activo';
    const ESTADO_VENCIDO    = 'vencido';
    const ESTADO_COMPLETADO = 'completado';

    // ─── Eventos del modelo ───────────────────────────────────────

    protected static function booted()
    {
        // Autocorrige al crear/editar: si sigue "activo" pero fecha_fin
        // ya pasó, se guarda directamente como "vencido".
        static::saving(function (Tratamiento $t) {
            if (
                $t->estado === self::ESTADO_ACTIVO
                && $t->fecha_fin
                && Carbon::parse($t->fecha_fin)->lte(Carbon::today())
            ) {
                $t->estado = self::ESTADO_VENCIDO;
            }
        });
    }

    // ─── Relaciones ───────────────────────────────────────────────

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function eventoSalud(): BelongsTo
    {
        return $this->belongsTo(EventoSalud::class, 'salud_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_VENCIDO);
    }

    public function scopeCompletados(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeDeAnimal(Builder $query, int $animalId): Builder
    {
        return $query->where('animal_id', $animalId);
    }

    public function scopeDeLote(Builder $query, int $loteId): Builder
    {
        return $query->where('lote_id', $loteId);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function marcarCompletado(): void
    {
        $this->update(['estado' => self::ESTADO_COMPLETADO]);
    }

    public function diasRestantes(): ?int
    {
        if (!$this->fecha_fin) return null;

        return max(0, Carbon::today()->diffInDays($this->fecha_fin, false));
    }

    /**
     * Marca como vencidos todos los tratamientos activos cuya fecha_fin
     * ya pasó. Se llama en cada punto de lectura (index, reportes) porque
     * en local no hay scheduler/cron corriendo constantemente.
     */
    public static function sincronizarVencidos(): int
    {
        return static::where('estado', self::ESTADO_ACTIVO)
            ->whereNotNull('fecha_fin')
            ->whereDate('fecha_fin', '<', Carbon::today())
            ->update(['estado' => self::ESTADO_VENCIDO]);
    }
}