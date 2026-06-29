<?php

namespace App\Console\Commands;

use App\Models\Tarea;
use App\Notifications\RecordatorioTareaNotification;
use Illuminate\Console\Command;

class EnviarRecordatoriosTareas extends Command
{
    protected $signature = 'tareas:enviar-recordatorios';

    protected $description = 'Envía las notificaciones de las tareas programadas';

    public function handle(): int
    {
        Tarea::query()
            ->with('asignado')
            ->where('estado', 'pendiente')
            ->whereNull('notificada_en')
            ->where('fecha_recordatorio', '<=', now())
            ->chunkById(100, function ($tareas) {
                foreach ($tareas as $tarea) {
                    if (!$tarea->asignado) {
                        continue;
                    }

                    $tarea->asignado->notify(
                        new RecordatorioTareaNotification($tarea)
                    );

                    $tarea->update([
                        'notificada_en' => now(),
                    ]);
                }
            });

        return self::SUCCESS;
    }
}