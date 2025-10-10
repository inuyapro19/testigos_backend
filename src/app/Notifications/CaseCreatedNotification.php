<?php

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseModel $case
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo caso enviado - ' . $this->case->title)
            ->greeting('¡Hola!')
            ->line('Tu caso "' . $this->case->title . '" ha sido enviado exitosamente.')
            ->line('Nuestro equipo de abogados lo revisará pronto y te notificaremos cuando cambie su estado.')
            ->action('Ver mi caso', url('/casos/' . $this->case->id))
            ->line('Gracias por confiar en Testigo.cl para buscar justicia.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'case_title' => $this->case->title,
            'message' => 'Tu caso ha sido enviado y está en revisión',
            'type' => 'case_created'
        ];
    }
}
