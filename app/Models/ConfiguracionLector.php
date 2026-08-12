<?php

namespace App\Models;

use App\Support\CodigoIso11784;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes del lector de aretes de un rancho.
 *
 * Existe para que un cliente con un lector poco común pueda adaptarlo desde la
 * interfaz, sin que haya que tocar el código del sistema.
 */
class ConfiguracionLector extends Model
{
    use HasFactory;

    protected $table = 'configuracion_lectores';

    public const CONEXION_TECLADO = 'teclado';
    public const CONEXION_SERIAL = 'serial';
    public const CONEXION_BLUETOOTH = 'bluetooth';

    public const CONEXIONES = [
        self::CONEXION_TECLADO => 'Modo teclado (recomendado)',
        self::CONEXION_SERIAL => 'Puerto serie por cable',
        self::CONEXION_BLUETOOTH => 'Bluetooth emparejado como puerto serie',
    ];

    /** Velocidades habituales en lectores de arete. */
    public const BAUD_RATES = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200];

    protected $fillable = [
        'owner_id',
        'prefijo_descartar',
        'sufijo_descartar',
        'solo_digitos',
        'longitud_esperada',
        'tipo_conexion',
        'baud_rate',
        'modelo_lector',
        'notas',
    ];

    protected $casts = [
        'solo_digitos' => 'boolean',
        'longitud_esperada' => 'integer',
        'baud_rate' => 'integer',
    ];

    /**
     * La configuración del rancho activo, creando la de arranque si aún no
     * existe. Los valores por omisión son los que funcionan con un lector
     * corriente en modo teclado, así que un cliente que nunca entre aquí no
     * nota que esta pantalla existe.
     */
    public static function delRancho(): self
    {
        return static::firstOrCreate([], [
            'tipo_conexion' => self::CONEXION_TECLADO,
            'baud_rate' => 9600,
            'solo_digitos' => false,
        ]);
    }

    /** Longitud contra la que se compara: la configurada o la de la norma. */
    public function longitud(): int
    {
        return $this->longitud_esperada ?: CodigoIso11784::LONGITUD;
    }

    /** Lo que necesita el navegador para aplicar las mismas reglas en vivo. */
    public function paraNavegador(): array
    {
        return [
            'prefijo_descartar' => $this->prefijo_descartar,
            'sufijo_descartar' => $this->sufijo_descartar,
            'solo_digitos' => (bool) $this->solo_digitos,
            'longitud_esperada' => $this->longitud(),
            'tipo_conexion' => $this->tipo_conexion,
            'baud_rate' => $this->baud_rate,
        ];
    }
}
