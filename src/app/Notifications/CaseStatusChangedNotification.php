<?php

namespace App\Notifications;

use App\Models\CaseModel;
use App\Enums\CaseStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseModel $case,
        public CaseStatus $oldStatus,
        public CaseStatus $newStatus
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
        $message = (new MailMessage)
            ->subject('Actualización de caso - ' . $this->case->title)
            ->greeting('¡Hola!');

        switch ($this->newStatus) {
            case CaseStatus::UNDER_REVIEW:
                $message->line('Tu caso "' . $this->case->title . '" está siendo revisado por nuestros abogados.')
                    ->line('Pronto recibirás una evaluación detallada.');
                break;

            case CaseStatus::APPROVED:
                $message->line('¡Buenas noticias! Tu caso "' . $this->case->title . '" ha sido aprobado.')
                    ->line('Probabilidad de éxito: ' . $this->case->success_rate . '%')
                    ->line('Monto requerido: $' . number_format($this->case->funding_goal, 0, ',', '.'));
                break;

            case CaseStatus::PUBLISHED:
                $message->line('Tu caso "' . $this->case->title . '" ha sido publicado y está disponible para inversionistas.')
                    ->line('Meta de financiamiento: $' . number_format($this->case->funding_goal, 0, ',', '.'))
                    ->action('Ver caso publicado', url('/casos/' . $this->case->id));
                break;

            case CaseStatus::FUNDED:
                $message->line('¡Excelente! Tu caso "' . $this->case->title . '" ha alcanzado su meta de financiamiento.')
                    ->line('Monto total recaudado: $' . number_format($this->case->current_funding, 0, ',', '.'))
                    ->line('Pronto comenzaremos los procedimientos legales.');
                break;

            case CaseStatus::IN_PROGRESS:
                $message->line('Los procedimientos legales de tu caso "' . $this->case->title . '" han comenzado.')
                    ->line('Te mantendremos informado del progreso.');
                break;

            case CaseStatus::COMPLETED:
                $message->line('Tu caso "' . $this->case->title . '" ha sido completado.')
                    ->line('Revisa los detalles del resultado final.');
                break;

            case CaseStatus::REJECTED:
                $message->line('Lamentablemente, tu caso "' . $this->case->title . '" no fue aprobado.')
                    ->line('Si tienes preguntas, contacta a nuestro equipo de soporte.');
                break;
        }

        return $message->line('Gracias por usar Testigo.cl');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'case_title' => $this->case->title,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'new_status_label' => $this->newStatus->label(),
            'message' => 'El estado de tu caso cambió a: ' . $this->newStatus->label(),
            'type' => 'case_status_changed'
        ];
    }
}
