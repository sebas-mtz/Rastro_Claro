<?php

namespace App\Models;

use App\Support\ModuloSistema as M;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Catálogo de puestos del rancho ovino. Configurable por cuenta.
 *
 * Es el catálogo ÚNICO: describe tanto el puesto de las personas registradas
 * en Trabajadores como el de las cuentas que entran al sistema. Antes había
 * dos listas —una constante en el código y esta tabla— que podían
 * desincronizarse; la migración 2026_08_06_010100 las unió sin perder ningún
 * puesto ya asignado.
 *
 * Cada puesto lleva además qué módulos puede tocar quien lo ocupa.
 */
class PuestoTrabajador extends Model
{
    use HasFactory;

    protected $table = 'puestos_trabajador';

    protected $fillable = [
        'owner_id',
        'clave',
        'nombre',
        'area',
        'descripcion',
        'permisos',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'permisos' => 'array',
    ];

    /**
     * Puestos base del manejo ovino.
     *
     * Los catorce primeros son los del catálogo original. Los siete últimos
     * vienen de la lista que usaban las cuentas del sistema y se conservan
     * para no perder ninguna asignación existente: el seeder solo los crea si
     * alguien los tiene puestos, para no llenar de opciones un rancho nuevo.
     */
    public const BASE = [
        ['clave' => 'gerente',                  'nombre' => 'Gerente',                      'area' => 'Administración'],
        ['clave' => 'encargado_general',        'nombre' => 'Encargado general',            'area' => 'Administración'],
        ['clave' => 'ganadero',                 'nombre' => 'Ganadero',                     'area' => 'Manejo del rebaño'],
        ['clave' => 'veterinario',              'nombre' => 'Veterinario',                  'area' => 'Sanidad'],
        ['clave' => 'ayudante_veterinario',     'nombre' => 'Ayudante veterinario',         'area' => 'Sanidad'],
        ['clave' => 'alimentador',              'nombre' => 'Alimentador',                  'area' => 'Alimentación'],
        ['clave' => 'responsable_limpieza',     'nombre' => 'Responsable de limpieza',      'area' => 'Instalaciones'],
        ['clave' => 'responsable_reproduccion', 'nombre' => 'Responsable de reproducción',  'area' => 'Reproducción'],
        ['clave' => 'responsable_faena',        'nombre' => 'Responsable de faena',         'area' => 'Faena'],
        ['clave' => 'responsable_sacrificio',   'nombre' => 'Responsable de sacrificio',    'area' => 'Faena'],
        ['clave' => 'responsable_ventas',       'nombre' => 'Responsable de ventas',        'area' => 'Comercial'],
        ['clave' => 'transportista',            'nombre' => 'Transportista',                'area' => 'Logística'],
        ['clave' => 'trabajador_general',       'nombre' => 'Trabajador general',           'area' => 'Manejo del rebaño'],
        ['clave' => 'otro',                     'nombre' => 'Otro',                         'area' => null],
    ];

    /**
     * Puestos que solo existían en la lista de las cuentas del sistema.
     * Se siembran únicamente si alguna cuenta los tiene asignados.
     */
    public const HEREDADOS = [
        ['clave' => 'encargado_rebano',          'nombre' => 'Encargado del rebaño',        'area' => 'Manejo del rebaño'],
        ['clave' => 'pastor',                    'nombre' => 'Pastor',                      'area' => 'Manejo del rebaño'],
        ['clave' => 'encargado_alimentacion',    'nombre' => 'Encargado de alimentación',   'area' => 'Alimentación'],
        ['clave' => 'encargado_reproduccion',    'nombre' => 'Encargado de reproducción',   'area' => 'Reproducción'],
        ['clave' => 'encargado_partos',          'nombre' => 'Encargado de partos',         'area' => 'Reproducción'],
        ['clave' => 'responsable_pesaje',        'nombre' => 'Responsable de pesaje',       'area' => 'Manejo del rebaño'],
        ['clave' => 'responsable_identificacion','nombre' => 'Responsable de identificación','area' => 'Manejo del rebaño'],
    ];

    /** Áreas sugeridas para el filtro; el campo admite texto libre. */
    public const AREAS = [
        'Administración',
        'Manejo del rebaño',
        'Sanidad',
        'Alimentación',
        'Reproducción',
        'Instalaciones',
        'Faena',
        'Comercial',
        'Logística',
    ];

    /**
     * Módulos que puede tocar cada puesto por omisión.
     *
     * Regla que atraviesa toda la tabla: NINGÚN puesto trae los módulos
     * económicos —costos, valuación y ventas— salvo el gerente, que dirige, y
     * el responsable de ventas, que solo recibe Ventas porque es su oficio.
     * Todo lo demás se concede a mano, persona por persona.
     *
     * Estos valores son el punto de partida: quedan guardados en la base de
     * datos y desde ahí se editan.
     */
    public static function permisosPorDefecto(string $clave): array
    {
        $ver = [M::VER];
        $registrar = [M::VER, M::REGISTRAR];
        $editar = [M::VER, M::REGISTRAR, M::EDITAR];
        $todo = M::TODAS;

        return match ($clave) {
            // Dirección: acceso completo, incluido el dinero.
            'gerente' => array_fill_keys(M::claves(), $todo),

            // Mando operativo, sin ver el dinero.
            'encargado_general', 'encargado_rebano' => array_fill_keys(
                array_diff(M::claves(), M::ECONOMICOS),
                $todo
            ),

            'veterinario' => [
                M::SALUD => $todo,
                M::REPRODUCCION => $editar,
                M::ANIMALES => $ver,
                M::PESAJES => $ver,
                M::DOCUMENTOS => $registrar,
                M::TAREAS => $registrar,
            ],

            'ayudante_veterinario' => [
                M::SALUD => $registrar,
                M::ANIMALES => $ver,
                M::TAREAS => $ver,
            ],

            'alimentador', 'encargado_alimentacion' => [
                M::ALIMENTACION => $editar,
                M::LOTES => $ver,
                M::ANIMALES => $ver,
                M::TAREAS => $registrar,
            ],

            'responsable_reproduccion', 'encargado_reproduccion' => [
                M::REPRODUCCION => $todo,
                M::ANIMALES => $ver,
                M::DOCUMENTOS => $registrar,
                M::TAREAS => $registrar,
            ],

            'encargado_partos' => [
                M::REPRODUCCION => $editar,
                M::ANIMALES => $ver,
                M::TAREAS => $registrar,
            ],

            'responsable_pesaje' => [
                M::PESAJES => $todo,
                M::ANIMALES => $ver,
                M::LOTES => $ver,
                M::TAREAS => $ver,
            ],

            'responsable_identificacion' => [
                M::ANIMALES => $editar,
                M::LOTES => $ver,
                M::TAREAS => $ver,
            ],

            'ganadero', 'pastor' => [
                M::ANIMALES => $ver,
                M::PESAJES => $registrar,
                M::LOTES => $ver,
                M::BAJAS => $ver,
                M::TAREAS => $registrar,
            ],

            'responsable_faena' => [
                M::FAENAS => $editar,
                M::ANIMALES => $ver,
                M::TAREAS => $ver,
            ],

            'responsable_sacrificio' => [
                M::FAENAS => $editar,
                M::BAJAS => $registrar,
                M::ANIMALES => $ver,
                M::TAREAS => $ver,
            ],

            // Único puesto no directivo con un módulo económico, porque
            // vender es literalmente su trabajo. Sin costos ni valuación.
            'responsable_ventas' => [
                M::VENTAS => $todo,
                M::ANIMALES => $ver,
                M::REPORTES => $ver,
                M::TAREAS => $ver,
            ],

            'transportista' => [
                M::ANIMALES => $ver,
                M::LOTES => $ver,
                M::BAJAS => $ver,
                M::TAREAS => $ver,
            ],

            'responsable_limpieza' => [
                M::TAREAS => $registrar,
            ],

            'trabajador_general' => [
                M::ANIMALES => $ver,
                M::PESAJES => $registrar,
                M::TAREAS => $registrar,
            ],

            // 'otro' y cualquier puesto que el rancho invente nacen sin
            // permisos: se conceden a conciencia, no por omisión.
            default => [],
        };
    }

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'puesto_id');
    }

    /** Cuentas del sistema que ocupan este puesto. */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'puesto_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /** Permisos guardados, ya normalizados a array de arrays. */
    public function permisosNormalizados(): array
    {
        $permisos = $this->permisos ?? [];

        return is_array($permisos) ? $permisos : [];
    }

    /**
     * Genera una clave estable a partir del nombre para los puestos que el
     * usuario agregue desde la interfaz.
     */
    public static function claveDesdeNombre(string $nombre): string
    {
        return Str::slug($nombre, '_');
    }
}
