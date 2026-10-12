<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Algo que le pasó al ejemplar en una parte concreta del cuerpo.
 */
class MarcaCorporal extends Model
{
    use HasFactory;

    protected $table = 'marcas_corporales';

    // ── Zonas anatómicas ──────────────────────────────────────────────────
    //
    // Se guarda la clave, no una coordenada: «pata trasera derecha» significa
    // lo mismo aunque cambie la figura que se dibuje en pantalla.
    public const ZONAS = [
        'cabeza' => 'Cabeza',
        'oreja_izquierda' => 'Oreja izquierda',
        'oreja_derecha' => 'Oreja derecha',
        'hocico' => 'Hocico',
        'cuello' => 'Cuello',
        'cruz' => 'Cruz',
        'lomo' => 'Lomo',
        'grupa' => 'Grupa',
        'costillar_izquierdo' => 'Costillar izquierdo',
        'costillar_derecho' => 'Costillar derecho',
        'flanco_izquierdo' => 'Flanco izquierdo',
        'flanco_derecho' => 'Flanco derecho',
        'pecho' => 'Pecho',
        'vientre' => 'Vientre',
        'ubre' => 'Ubre',
        'pata_delantera_izquierda' => 'Pata delantera izquierda',
        'pata_delantera_derecha' => 'Pata delantera derecha',
        'pata_trasera_izquierda' => 'Pata trasera izquierda',
        'pata_trasera_derecha' => 'Pata trasera derecha',
        'cola' => 'Cola',
    ];

    /** Zonas donde se palpa para evaluar la condición corporal. */
    public const ZONAS_CONDICION = ['lomo', 'costillar_izquierdo', 'costillar_derecho', 'grupa', 'cruz'];

    // ── Tipos ─────────────────────────────────────────────────────────────
    public const LESION = 'lesion';
    public const VACUNA = 'vacuna';
    public const TRATAMIENTO = 'tratamiento';
    public const ARETE = 'arete';
    public const CONDICION = 'condicion';
    public const OBSERVACION = 'observacion';

    public const TIPOS = [
        self::LESION => 'Lesión o herida',
        self::VACUNA => 'Aplicación de vacuna',
        self::TRATAMIENTO => 'Tratamiento',
        self::ARETE => 'Identificador colocado',
        self::CONDICION => 'Punto de palpación',
        self::OBSERVACION => 'Observación',
    ];

    public const ACTIVA = 'activa';
    public const RESUELTA = 'resuelta';

    public const ESTADOS = [
        self::ACTIVA => 'Activa',
        self::RESUELTA => 'Resuelta',
    ];

    protected $fillable = [
        'owner_id',
        'animal_id',
        'zona',
        'tipo',
        'descripcion',
        'fecha',
        'estado',
        'fecha_resolucion',
        'origen_tipo',
        'origen_id',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_resolucion' => 'date',
    ];

    protected $appends = ['zona_legible', 'tipo_legible'];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** Evento de salud o tratamiento del que salió la marca, si lo hubo. */
    public function origen(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'origen_tipo', 'origen_id');
    }

    public function getZonaLegibleAttribute(): string
    {
        return self::ZONAS[$this->zona] ?? $this->zona;
    }

    public function getTipoLegibleAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function scopeActiva($query)
    {
        return $query->where('estado', self::ACTIVA);
    }

    public function scopeDeTipo($query, ?string $tipo)
    {
        return $query->when($tipo, fn ($q) => $q->where('tipo', $tipo));
    }
}
