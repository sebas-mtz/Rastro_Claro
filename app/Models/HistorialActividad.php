<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class HistorialActividad extends Model
{
    protected $table = 'historial_actividad';
    public $timestamps = false; // Solo created_at

    protected $fillable = [
        'user_id', 'user_nombre',
        'modulo', 'accion', 'descripcion',
        'modelo', 'modelo_id',
        'datos_antes', 'datos_despues',
        'ip', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'datos_antes'   => 'array',
        'datos_despues' => 'array',
        'created_at'    => 'datetime',
    ];

    // ─── Relaciones ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Método principal ────────────────────────────────────────

    /**
     * Registrar actividad en el historial.
     *
     * Ejemplos:
     *   HistorialActividad::registrar('animales', 'crear', 'Creó borrega #4521', $animal);
     *   HistorialActividad::registrar('login', 'login', 'Inició sesión');
     *   HistorialActividad::registrar('animales', 'editar', 'Editó borrega', $animal,
     *       $animal->getOriginal(), $animal->toArray());
     */
    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $modelo       = null,
        array  $datosAntes   = [],
        array  $datosDespues = []
    ): void {
        try {
            $user = Auth::user();

            static::create([
                'user_id'       => $user?->id,
                'user_nombre'   => $user?->name ?? 'Sistema',
                'modulo'        => $modulo,
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'modelo'        => $modelo ? class_basename($modelo) : null,
                'modelo_id'     => $modelo?->getKey(),
                'datos_antes'   => !empty($datosAntes)   ? $datosAntes   : null,
                'datos_despues' => !empty($datosDespues) ? $datosDespues : null,
                'ip'            => Request::ip(),
                'user_agent'    => mb_substr(Request::userAgent() ?? '', 0, 200),
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // Nunca romper el flujo principal
            \Log::warning('[HistorialActividad] Error al registrar: ' . $e->getMessage());
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Íconos por acción para el frontend */
    public static function iconoPorAccion(string $accion): string
    {
        return match ($accion) {
            'crear'    => '✅',
            'editar'   => '✏️',
            'eliminar' => '🗑️',
            'login'    => '🔑',
            'logout'   => '🚪',
            'ver'      => '👁️',
            default    => '📋',
        };
    }
}
