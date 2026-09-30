<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Reporte extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'modulo',
        'filtros',
        'resumen',
        'formato',
        'publico',
        'uuid',
    ];

    protected $casts = [
        'filtros'  => 'array',
        'resumen'  => 'array',
        'publico'  => 'boolean',
    ];

    // ─── Generar UUID automáticamente ─────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Reporte $reporte) {
            if (empty($reporte->uuid)) {
                $reporte->uuid = Str::uuid();
            }
        });
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────
    public function scopeDelUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDeModulo($query, string $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    /**
     * Reconstruye la URL de consulta a partir de los filtros guardados,
     * útil para el botón "Ver de nuevo" en el historial.
     */
    public function urlRegeneracion(): string
    {
        $params = collect($this->filtros ?? [])
            ->filter(fn($v) => $v !== null && $v !== '')
            ->toArray();

        return route('reportes.index') . '?' . http_build_query($params);
    }

    /**
     * Etiqueta legible del módulo para mostrar en vistas.
     */
    public function etiquetaModulo(): string
    {
        return match ($this->modulo) {
            'general'      => 'General',
            'animales'     => 'Animales',
            'salud'        => 'Salud',
            'vacunacion'   => 'Vacunación',
            'tratamientos' => 'Tratamientos',
            'pesajes'      => 'Pesajes',
            'alimentacion' => 'Alimentación',
            'inventario'   => 'Inventario',
            'reproduccion' => 'Reproducción',
            'produccion'   => 'Producción',
            'ventas'       => 'Ventas',
            default        => ucfirst($this->modulo),
        };
    }
}