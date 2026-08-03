<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    /** Tipos de evidencia del manejo ovino. */
    public const TIPOS = [
        'fotografia'             => 'Fotografía',
        'certificado_pureza'     => 'Certificado de pureza',
        'registro_asociacion'    => 'Registro de asociación',
        'comprobante_compra'     => 'Comprobante de compra',
        'comprobante_venta'      => 'Comprobante de venta',
        'estudio_veterinario'    => 'Estudio veterinario',
        'comprobante_vacunacion' => 'Comprobante de vacunación',
        'evidencia_parto'        => 'Evidencia de parto',
        'documento_traslado'     => 'Documento de traslado',
        'otro'                   => 'Otro documento',
    ];

    /** Extensiones aceptadas y tamaño máximo (5 MB). */
    public const EXTENSIONES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const TAMANO_MAXIMO_KB = 5120;

    /** Disco privado: los archivos no son accesibles por URL directa. */
    public const DISCO = 'local';

    protected $fillable = [
        'owner_id',
        'documentable_type',
        'documentable_id',
        'tipo',
        'nombre',
        'ruta',
        'nombre_original',
        'mime',
        'tamano',
        'fecha_documento',
        'observaciones',
        'subido_por',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'tamano' => 'integer',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function getTipoLegibleAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    /** Tamaño legible: 1.4 MB, 320 KB… */
    public function getTamanoLegibleAttribute(): ?string
    {
        if (! $this->tamano) {
            return null;
        }

        return $this->tamano >= 1048576
            ? round($this->tamano / 1048576, 1) . ' MB'
            : round($this->tamano / 1024) . ' KB';
    }
}
