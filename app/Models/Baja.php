<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Baja extends Model
{
    use HasFactory;

    protected $table = 'bajas';

    public const FALLECIMIENTO = 'fallecimiento';
    public const DESCARTE_REPRODUCTIVO = 'descarte_reproductivo';
    public const ROBO = 'robo';
    public const EXTRAVIO = 'extravio';
    public const DONACION = 'donacion';
    public const TRASLADO = 'traslado';
    public const OTRA = 'otra';

    public const TIPOS = [
        self::FALLECIMIENTO => 'Fallecimiento',
        self::DESCARTE_REPRODUCTIVO => 'Descarte reproductivo',
        self::ROBO => 'Robo',
        self::EXTRAVIO => 'Extravío',
        self::DONACION => 'Donación',
        self::TRASLADO => 'Traslado',
        self::OTRA => 'Otra causa',
    ];

    /** Salidas en las que tiene sentido capturar un precio. */
    public const TIPOS_CON_PRECIO = [self::DONACION, self::TRASLADO];

    /**
     * A qué estado_productivo pasa el animal según el tipo de baja.
     * Necesario para que HaciendaService::getAvailableAnimals() y
     * CriaDisponibilidadService dejen de considerarlo disponible.
     */
    public const ESTADO_PRODUCTIVO_POR_TIPO = [
        self::FALLECIMIENTO => 'muerto',
        self::DESCARTE_REPRODUCTIVO => 'descarte_reproductivo',
        self::ROBO => 'robado',
        self::EXTRAVIO => 'extraviado',
        self::DONACION => 'donado',
        self::TRASLADO => 'trasladado',
        self::OTRA => 'baja_otra',
    ];

    protected $fillable = [
        'owner_id', 'animal_id', 'fecha', 'tipo_salida', 'causa',
        'diagnostico', 'responsable_id', 'precio_salida',
        'observaciones', 'documento', 'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio_salida' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getTipoLegibleAttribute(): string
    {
        return self::TIPOS[$this->tipo_salida] ?? $this->tipo_salida;
    }
}