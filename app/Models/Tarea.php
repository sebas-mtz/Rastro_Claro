<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asignado_a',
        'creado_por',
        'titulo',
        'descripcion',
        'fecha_recordatorio',
        'estado',
        'completada_en',
        'suspendida_en',
        'notificada_en',
    ];

    protected $casts = [
        'fecha_recordatorio' => 'datetime',
        'completada_en' => 'datetime',
        'suspendida_en' => 'datetime',
        'notificada_en' => 'datetime',
    ];

    protected $appends = [
        'esta_vencida',
    ];

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->estado === 'pendiente'
            && $this->fecha_recordatorio->isPast();
    }
}