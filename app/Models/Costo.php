<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Costo extends Model
{
    use HasFactory;

    public const CATEGORIAS = [
        'alimentacion',
        'medicamentos',
        'vacunas',
        'consultas_veterinarias',
        'mano_obra',
        'transporte',
        'compra_animales',
        'mantenimiento',
        'insumos',
        'faenas',
        'sacrificios',
        'servicios',
        'administrativos',
        'registro_genetico',
        'otros',
    ];

    public const TIPOS_COSTO = ['directo', 'indirecto'];

    protected $fillable = [
        'owner_id',
        'concepto',
        'descripcion',
        'categoria',
        'tipo_costo',
        'monto',
        'cantidad',
        'unidad_medida',
        'fecha',
        'animal_id',
        'lote_id',
        'faena_id',
        'sacrificio_id',
        'trabajador_id',
        'proveedor',
        'numero_comprobante',
        'observaciones',
        'user_id',
        'origen_tipo',
        'origen_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'cantidad' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function faena(): BelongsTo
    {
        return $this->belongsTo(Faena::class);
    }

    public function sacrificio(): BelongsTo
    {
        return $this->belongsTo(Sacrificio::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Persona cuya mano de obra generó este costo, si aplica. */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    /**
     * Registro del que proviene este costo (un EventoSalud, un Tratamiento...).
     * Lo usa AnimalValuationService para no contar dos veces el mismo gasto.
     */
    public function origen(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'origen_tipo', 'origen_id');
    }

    public function scopeCategoria($query, ?string $categoria)
    {
        return $query->when($categoria, fn ($q) => $q->where('categoria', $categoria));
    }

    public function scopeEntreFechas($query, ?string $desde, ?string $hasta)
    {
        return $query
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));
    }

    public function scopeDeAnimal($query, $animalId)
    {
        return $query->when($animalId, fn ($q) => $q->where('animal_id', $animalId));
    }

    public function scopeDeLote($query, $loteId)
    {
        return $query->when($loteId, fn ($q) => $q->where('lote_id', $loteId));
    }

    public function scopeDeTrabajador($query, $trabajadorId)
    {
        return $query->when($trabajadorId, fn ($q) => $q->where('trabajador_id', $trabajadorId));
    }
}
