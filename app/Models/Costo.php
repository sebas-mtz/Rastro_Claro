<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Costo extends Model
{
    protected $table = 'costos';

    protected $fillable = [
        'animal_id',
        'user_id',
        'fecha',
        'etapa',
        'categoria',
        'concepto',
        'costo',
        'observaciones',
        'origen',
        'origen_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'costo' => 'float',
    ];

    const ETAPAS = [
        'lactante'               => 'Lactante',
        'destete'                => 'Destete',
        'desarrollo_crecimiento' => 'Desarrollo / Crecimiento',
        'primer_empadre_monta'   => 'Primer Empadre / Monta',
        'gestacion'              => 'Gestación',
        'lactacion'              => 'Lactación',
        'periodo_seco'           => 'Período Seco',
        'adulta_mantenimiento'   => 'Borrega adulta en mantenimiento',
    ];

    const CATEGORIAS = [
        'alimentacion' => 'Alimentación',
        'salud'        => 'Salud',
        'manejo'       => 'Manejo',
        'otros'        => 'Otros',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeDeAnimal(Builder $query, int $animalId): Builder
    {
        return $query->where('animal_id', $animalId);
    }

    public function scopeDeUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorEtapa(Builder $query, string $etapa): Builder
    {
        return $query->where('etapa', $etapa);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Importa un gasto de salud (EventoSalud) si no fue importado antes.
     * Retorna el Costo creado o null si ya existía.
     */
    public static function importarDesdeSalud(
        \App\Models\EventoSalud $evento
    ): ?self {
        if ((float) $evento->costo <= 0) return null;

        // Verificar si ya fue importado
        $existe = self::where('origen', 'salud')
            ->where('origen_id', $evento->id)
            ->exists();

        if ($existe) return null;

        $concepto = match ($evento->tipo) {
            'vacunacion' => 'Vacuna: ' . ($evento->vacuna?->nombre ?? 'Vacunación'),
            'consulta'   => 'Consulta médica: ' . ($evento->diagnostico ?? ''),
            'revision'   => 'Revisión: ' . ($evento->diagnostico ?? ''),
            'emergencia' => 'Emergencia: ' . ($evento->diagnostico ?? ''),
            default      => 'Evento de salud: ' . ($evento->diagnostico ?? ''),
        };

        return self::create([
            'animal_id'    => $evento->animal_id,
            'user_id'      => $evento->user_id,
            'fecha'        => $evento->fecha_aplicacion ?? $evento->fecha_programada,
            'etapa'        => $evento->etapa_animal ?? 'adulta_mantenimiento',
            'categoria'    => 'salud',
            'concepto'     => $concepto,
            'costo'        => $evento->costo,
            'observaciones'=> $evento->observaciones,
            'origen'       => 'salud',
            'origen_id'    => $evento->id,
        ]);
    }

    /**
     * Importa un gasto de tratamiento si no fue importado antes.
     */
    public static function importarDesdeTratamiento(
        \App\Models\Tratamiento $tratamiento
    ): ?self {
        if ((float) $tratamiento->costo <= 0) return null;

        $existe = self::where('origen', 'tratamiento')
            ->where('origen_id', $tratamiento->id)
            ->exists();

        if ($existe) return null;

        return self::create([
            'animal_id'    => $tratamiento->animal_id,
            'user_id'      => $tratamiento->user_id,
            'fecha'        => $tratamiento->fecha_inicio,
            'etapa'        => $tratamiento->etapa_animal ?? 'adulta_mantenimiento',
            'categoria'    => 'salud',
            'concepto'     => 'Tratamiento: ' . $tratamiento->nombre,
            'costo'        => $tratamiento->costo,
            'observaciones'=> $tratamiento->notas,
            'origen'       => 'tratamiento',
            'origen_id'    => $tratamiento->id,
        ]);
    }
}
