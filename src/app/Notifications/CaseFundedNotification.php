<?php

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseFundedNotification extends Notification implements ShouldQueue
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
            ->subject('¡Meta alcanzada! - ' . $this->case->title)
            ->greeting('¡Excelente noticia!')
            ->line('El caso "' . $this->case->title . '" ha alcanzado su meta de financiamiento.')
            ->line('Monto total recaudado: $' . number_format($this->case->current_funding, 0, ',', '.'))
            ->line('Número de inversionistas: ' . $this->case->investments()->count())
            ->line('Los procedimientos legales comenzarán pronto. Te mantendremos informado de cada paso.')
            ->action('Ver caso', url('/casos/' . $this->case->id))
            ->line('¡Gracias por ser parte de este camino hacia la justicia!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'case_title' => $this->case->title,
            'funding_goal' => $this->case->funding_goal,
            'current_funding' => $this->case->current_funding,
            'investors_count' => $this->case->investments()->count(),
            'message' => 'El caso ha alcanzado su meta de financiamiento',
            'type' => 'case_funded'
        ];
    }
}
