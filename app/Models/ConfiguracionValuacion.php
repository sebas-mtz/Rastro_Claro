<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionValuacion extends Model
{
    use HasFactory;

    protected $table = 'configuracion_valuaciones';

    /**
     * Valores iniciales del plus reproductivo. Son solo la semilla: quedan
     * en base de datos y se editan desde la interfaz, no desde el código.
     */
    public const PLUS_POR_DEFECTO = [
        'plus_joven_sin_edad_reproductiva' => [0, 'Borrega joven, sin edad reproductiva'],
        'plus_abierta' => [0, 'Borrega abierta o vacía'],
        'plus_cargada_semental_comercial' => [2500, 'Cargada por semental comercial'],
        'plus_cargada_semental_registro' => [6000, 'Cargada por semental de registro'],
        'plus_con_cria_al_pie' => [4000, 'Con cría al pie'],
        'plus_con_cria_hembra_al_pie' => [4000, 'Con cría hembra al pie'],
        'plus_con_cria_macho_al_pie' => [4000, 'Con cría macho al pie'],
        'plus_parto_multiple' => [8000, 'Con parto múltiple'],
        'plus_otro' => [0, 'Otro estado reproductivo'],
    ];

    protected $fillable = [
        'owner_id',
        'clave',
        'valor',
        'descripcion',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    /**
     * Plus configurado para un estado reproductivo de valuación.
     * Si la cuenta todavía no tiene fila propia, cae al valor semilla.
     */
    public static function plusPara(?string $estadoReproductivo): float
    {
        if (! $estadoReproductivo) {
            return 0.0;
        }

        $clave = 'plus_' . $estadoReproductivo;

        $configurado = static::where('clave', $clave)->value('valor');

        if ($configurado !== null) {
            return (float) $configurado;
        }

        return (float) (self::PLUS_POR_DEFECTO[$clave][0] ?? 0);
    }
}
