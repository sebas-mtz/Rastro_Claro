<?php

namespace App\Notifications;

use App\Models\Tarea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RecordatorioTareaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'recordatorio_tarea',
            'tarea_id' => $this->tarea->id,
            'titulo' => $this->tarea->titulo,
            'mensaje' => 'Tienes una tarea programada para este momento.',
            'fecha_recordatorio' => $this->tarea->fecha_recordatorio,
            'url' => route('tareas.index'),
        ];
    }
}