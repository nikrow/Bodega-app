<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BackupFailed extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('Fallo en el respaldo de la aplicación')
            ->line('El respaldo programado de la aplicación falló.')
            ->line('Por favor, revisa los logs para más detalles.')
            ->action('Ver logs', url('/logs'));
    }
}