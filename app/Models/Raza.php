<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raza extends Model
{
    use HasFactory;

    protected $table = 'razas';

    /**
     * Semilla del catálogo ovino. Solo es el punto de partida: las razas se
     * administran desde base de datos, no desde el código.
     *
     * [nombre, origen, aptitud]
     */
    public const CATALOGO_INICIAL = [
        ['Dorper', 'Sudáfrica', 'Carne'],
        ['Katahdin', 'Estados Unidos', 'Carne (pelo)'],
        ['Pelibuey', 'Caribe', 'Carne (pelo)'],
        ['Suffolk', 'Inglaterra', 'Carne'],
        ['Hampshire', 'Inglaterra', 'Carne'],
        ['Dorset', 'Inglaterra', 'Carne'],
        ['Texel', 'Países Bajos', 'Carne'],
        ['Charollais', 'Francia', 'Carne'],
        ['Blackbelly', 'Barbados', 'Carne (pelo)'],
        ['Rambouillet', 'Francia', 'Lana'],
        ['Merino', 'España', 'Lana'],
        ['Criollo', 'México', 'Doble propósito'],
        ['Otra raza', null, null],
    ];

    protected $fillable = [
        'owner_id',
        'nombre',
        'origen',
        'aptitud',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function animales(): HasMany
    {
        return $this->hasMany(Animal::class, 'raza_id');
    }

    public function animalesComoSecundaria(): HasMany
    {
        return $this->hasMany(Animal::class, 'raza_secundaria_id');
    }

    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Normaliza un nombre de raza para poder compararlo: sin acentos, sin
     * espacios extra y en minúsculas. Permite reconocer "Kathadin" como
     * "Katahdin" pese al error de captura.
     */
    public static function normalizarNombre(string $nombre): string
    {
        $limpio = mb_strtolower(trim($nombre));

        $limpio = strtr($limpio, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $limpio);
    }

    /**
     * Errores de captura conocidos en los datos históricos, mapeados al
     * nombre correcto del catálogo.
     */
    public const EQUIVALENCIAS = [
        'kathadin' => 'Katahdin',
        'katadhin' => 'Katahdin',
        'katahdín' => 'Katahdin',
        'pelibuey' => 'Pelibuey',
        'peliguey' => 'Pelibuey',
        'dorper' => 'Dorper',
        'suffolk' => 'Suffolk',
        'merino' => 'Merino',
        'otra' => 'Otra raza',
    ];
}
