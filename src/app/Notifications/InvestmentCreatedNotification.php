<?php

namespace App\Notifications;

use App\Models\Investment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Investment $investment,
        public string $recipientRole // 'victim' or 'investor'
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
        if ($this->recipientRole === 'investor') {
            return (new MailMessage)
                ->subject('Inversión confirmada - ' . $this->investment->case->title)
                ->greeting('¡Hola!')
                ->line('Tu inversión de $' . number_format($this->investment->amount, 0, ',', '.') . ' ha sido confirmada.')
                ->line('Caso: ' . $this->investment->case->title)
                ->line('Retorno esperado: ' . $this->investment->expected_return_percentage . '%')
                ->line('Monto esperado de retorno: $' . number_format($this->investment->expected_return_amount, 0, ',', '.'))
                ->action('Ver mi inversión', url('/inversiones/' . $this->investment->id))
                ->line('Gracias por invertir en justicia.');
        } else {
            // Para la víctima
            return (new MailMessage)
                ->subject('Nueva inversión en tu caso - ' . $this->investment->case->title)
                ->greeting('¡Hola!')
                ->line('¡Buenas noticias! Tu caso ha recibido una nueva inversión.')
                ->line('Monto invertido: $' . number_format($this->investment->amount, 0, ',', '.'))
                ->line('Progreso: $' . number_format($this->investment->case->current_funding, 0, ',', '.') . ' de $' . number_format($this->investment->case->funding_goal, 0, ',', '.'))
                ->line('Porcentaje alcanzado: ' . round($this->investment->case->getFundingPercentageAttribute(), 2) . '%')
                ->action('Ver mi caso', url('/casos/' . $this->investment->case_id))
                ->line('Cada día más cerca de lograr justicia.');
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'investment_id' => $this->investment->id,
            'case_id' => $this->investment->case_id,
            'case_title' => $this->investment->case->title,
            'amount' => $this->investment->amount,
            'recipient_role' => $this->recipientRole,
            'message' => $this->recipientRole === 'investor'
                ? 'Tu inversión ha sido confirmada'
                : 'Tu caso ha recibido una nueva inversión',
            'type' => 'investment_created'
        ];
    }
}
