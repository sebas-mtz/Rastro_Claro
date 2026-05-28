<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitacionTrabajador extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $trabajador,
        public readonly string $passwordTemporal,
        public readonly User   $invitadoPor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🐄 Invitación a ' . config('app.name') . ' — Tu acceso está listo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion-trabajador',
        );
    }
}
