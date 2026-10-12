<?php

namespace App\Observers;

use App\Models\EventoSalud;
use App\Models\MarcaCorporal;
use App\Models\Tratamiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Mantiene la marca corporal en sincronía con la atención sanitaria.
 *
 * Cuando se registra una vacuna, un tratamiento o una lesión indicando en qué
 * parte del cuerpo fue, aparece sola la marca sobre la figura del ejemplar. Sin
 * esto habría que capturar lo mismo dos veces, que es la forma más segura de
 * que la segunda no se capture nunca.
 *
 * Tres reglas que evitan sorpresas:
 *
 *   · Solo se tocan las marcas que nacieron de este registro. Las capturadas a
 *     mano no se modifican jamás, aunque coincidan en zona y fecha.
 *   · Solo hay marca cuando la atención es de UN ejemplar. Un evento de lote
 *     no marca el cuerpo de cuarenta animales: sería ruido, no información.
 *   · Si alguien resolvió la marca a mano, una edición posterior del evento no
 *     la reabre. El estado lo manda quien lo cambió, no el sincronizador.
 */
class MarcaDesdeSanidadObserver
{
    public function created(Model $model): void
    {
        $this->sincronizar($model);
    }

    public function updated(Model $model): void
    {
        $this->sincronizar($model);
    }

    public function deleted(Model $model): void
    {
        // Si desaparece la atención, desaparece la marca que salió de ella.
        $this->marcaDe($model)?->delete();
    }

    private function sincronizar(Model $model): void
    {
        $marca = $this->marcaDe($model);
        $zona = $this->zona($model);

        // Sin zona no hay nada que señalar. Si antes la había, la marca
        // automática se retira: dejarla mentiría sobre dónde ocurrió.
        if (! $zona || ! $model->animal_id) {
            $marca?->delete();

            return;
        }

        $datos = [
            'animal_id' => $model->animal_id,
            'zona' => $zona,
            'tipo' => $this->tipo($model),
            'descripcion' => $this->descripcion($model),
            'fecha' => $this->fecha($model),
            'origen_tipo' => $model::class,
            'origen_id' => $model->getKey(),
        ];

        if (! $marca) {
            MarcaCorporal::create(array_merge($datos, [
                'estado' => $this->estado($model),
                'fecha_resolucion' => $this->estado($model) === MarcaCorporal::RESUELTA
                    ? $this->fechaCierre($model)
                    : null,
                'registrado_por' => $model->user_id ?? Auth::id(),
            ]));

            return;
        }

        // Al actualizar no se toca el estado salvo que la atención se haya
        // dado por terminada: quien resolvió la marca a mano tenía sus motivos.
        if ($this->estado($model) === MarcaCorporal::RESUELTA) {
            $datos['estado'] = MarcaCorporal::RESUELTA;
            $datos['fecha_resolucion'] = $marca->fecha_resolucion ?? $this->fechaCierre($model);
        }

        $marca->update($datos);
    }

    /** La marca que nació de este registro, si existe. */
    private function marcaDe(Model $model): ?MarcaCorporal
    {
        return MarcaCorporal::where('origen_tipo', $model::class)
            ->where('origen_id', $model->getKey())
            ->first();
    }

    private function zona(Model $model): ?string
    {
        $zona = $model->zona_corporal;

        // Una zona que el catálogo no reconoce no se dibuja: mejor no crear
        // la marca que ponerla en un lugar inventado.
        return $zona && array_key_exists($zona, MarcaCorporal::ZONAS) ? $zona : null;
    }

    /**
     * Qué representa la marca, según la atención de la que viene.
     *
     * Las claves de tipo de evento y las de marca se parecen pero no son las
     * mismas, así que la correspondencia se declara aquí en vez de suponerla.
     */
    private function tipo(Model $model): string
{
    if ($model instanceof Tratamiento) {
        return MarcaCorporal::TRATAMIENTO;
    }

    return match ($model->tipo) {
        EventoSalud::TIPO_VACUNACION => MarcaCorporal::VACUNA,

        EventoSalud::TIPO_CONSULTA,
        EventoSalud::TIPO_REVISION,
        EventoSalud::TIPO_EMERGENCIA => MarcaCorporal::OBSERVACION,
    };
}

    private function descripcion(Model $model): ?string
    {
        if ($model instanceof Tratamiento) {
            return $model->nombre;
        }

        return $model->diagnostico
            ?: $model->vacuna?->nombre
            ?: $model->tipo_legible ?? null;
    }

    private function fecha(Model $model): string
    {
        $fecha = $model instanceof Tratamiento
            ? $model->fecha_inicio
            : ($model->fecha_aplicacion ?: $model->fecha_programada);

        return ($fecha ?: now())->toDateString();
    }

    /** Una atención terminada deja la marca resuelta. */
    private function estado(Model $model): string
    {
        $terminada = $model instanceof Tratamiento
            ? $model->estado === Tratamiento::ESTADO_COMPLETADO
            : $model->estado === 'completada';

        return $terminada ? MarcaCorporal::RESUELTA : MarcaCorporal::ACTIVA;
    }

    private function fechaCierre(Model $model): string
    {
        $fecha = $model instanceof Tratamiento
            ? ($model->fecha_fin ?: now())
            : ($model->fecha_aplicacion ?: now());

        return $fecha->toDateString();
    }
}
