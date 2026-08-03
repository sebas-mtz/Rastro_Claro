<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CondicionCorporal extends Model
{
    use HasFactory;

    protected $table = 'condiciones_corporales';

    /**
     * Escala de condición corporal en ovinos (1 a 5, con medios puntos).
     * Se documenta aquí y se muestra en la interfaz para que el usuario sepa
     * cómo interpretarla.
     */
    public const ESCALA = [
        '1.0' => 'Muy delgada — apófisis muy prominentes, sin cobertura de grasa',
        '1.5' => 'Delgada a muy delgada',
        '2.0' => 'Delgada — apófisis se sienten fácilmente',
        '2.5' => 'Delgada a óptima',
        '3.0' => 'Óptima — apófisis se sienten con presión moderada',
        '3.5' => 'Óptima a gorda',
        '4.0' => 'Gorda — apófisis difíciles de sentir',
        '4.5' => 'Gorda a obesa',
        '5.0' => 'Obesa — apófisis imposibles de sentir',
    ];

    public const RANGO_OPTIMO = [2.5, 3.5];

    protected $fillable = [
        'owner_id',
        'animal_id',
        'fecha',
        'calificacion',
        'etapa_reproductiva',
        'responsable',
        'observaciones',
        'recomendacion',
        'origen_tipo',
        'origen_id',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'calificacion' => 'decimal:1',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function origen(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'origen_tipo', 'origen_id');
    }

    public function getDescripcionEscalaAttribute(): ?string
    {
        return self::ESCALA[number_format((float) $this->calificacion, 1)] ?? null;
    }

    /**
     * Sugerencia de manejo según la calificación. Es orientativa: el sistema
     * registra y apoya el seguimiento, no diagnostica.
     */
    public function getSugerenciaAttribute(): string
    {
        $cc = (float) $this->calificacion;

        if ($cc < self::RANGO_OPTIMO[0]) {
            return 'Por debajo del rango óptimo: conviene revisar alimentación y descartar parasitosis.';
        }

        if ($cc > self::RANGO_OPTIMO[1]) {
            return 'Por encima del rango óptimo: considerar ajuste de la ración.';
        }

        return 'Dentro del rango óptimo para ovinos.';
    }
}
