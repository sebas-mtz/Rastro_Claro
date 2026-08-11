<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pajilla;

class Termo extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'ubicacion',
        'capacidad',
        'estado',
        'descripcion',
        'capacidad',
'numero_canastillas',
'capacidad_canastilla',
    ];
public static function conOcupacion($termos)
{
    return $termos->map(function ($termo) {
        $ocupacion = Pajilla::where('termo_id', $termo->id)
            ->where('estado', '!=', 'utilizada')
            ->whereNotNull('canastilla_numero')
            ->groupBy('canastilla_numero')
            ->selectRaw('canastilla_numero, count(*) as total')
            ->pluck('total', 'canastilla_numero');

        $canastillas = [];
        $espaciosLibres = 0;

        for ($c = 1; $c <= $termo->numero_canastillas; $c++) {
            $ocupadas = $ocupacion[$c] ?? 0;
            $libres = max(0, $termo->capacidad_canastilla - $ocupadas);

            $canastillas[] = [
                'numero' => $c,
                'ocupadas' => $ocupadas,
                'libres' => $libres,
            ];

            $espaciosLibres += $libres;
        }

        $termo->canastillas_detalle = $canastillas;
        $termo->espacios_libres_total = $espaciosLibres;

        return $termo;
    });
}
    public function pajillas()
    {
        return $this->hasMany(Pajilla::class);
    }

    public function pajillasDisponibles()
    {
        return $this->hasMany(Pajilla::class)
            ->where('estado', 'disponible');
    }
}