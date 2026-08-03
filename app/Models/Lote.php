<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lote extends Model
{
    protected $fillable = ['nombre','tipo','capacidad','corral_potrero','descripcion','responsable_id'];

    /** Tipos de lote según el manejo ovino. */
    public const TIPOS = [
        'crias_lactantes'      => 'Crías lactantes',
        'destetados'           => 'Destetados',
        'borregas_desarrollo'  => 'Borregas en desarrollo',
        'reproductoras'        => 'Borregas reproductoras',
        'gestantes'            => 'Borregas gestantes',
        'lactantes'            => 'Borregas lactantes',
        'sementales'           => 'Sementales',
        'engorda'              => 'Engorda',
        'cuarentena'           => 'Cuarentena',
        'enfermeria'           => 'Enfermería',
        'venta'                => 'Venta',
        'descarte'             => 'Descarte',
    ];

    public function animales() {
        return $this->hasMany(Animal::class);
    }

    /** Solo los ejemplares que siguen en el rebaño. */
    public function animalesActivos() {
        return $this->hasMany(Animal::class)->where('activo', true);
    }

    public function movimientos() {
        return $this->hasMany(MovimientoLote::class, 'lote_nuevo_id');
    }

    public function getTipoLegibleAttribute(): ?string
    {
        return $this->tipo ? (self::TIPOS[$this->tipo] ?? $this->tipo) : null;
    }

    public function ventas(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }
    public function salud() {
        return $this->hasMany(EventoSalud::class);
    }
    public function responsable() {
        return $this->belongsTo(User::class,'responsable_id');
    } /** @use HasFactory<\Database\Factories\LoteFactory> */
    use HasFactory;
}