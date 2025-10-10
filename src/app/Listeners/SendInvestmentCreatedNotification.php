<?php

namespace App\Listeners;

use App\Events\InvestmentCreated;
use App\Notifications\InvestmentCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvestmentCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvestmentCreated $event): void
    {
        // Notificar al inversionista que su inversión fue creada
        $event->investment->investor->notify(
            new InvestmentCreatedNotification($event->investment, 'investor')
        );

        // Notificar a la víctima que su caso recibió una inversión
        $event->investment->case->victim->notify(
            new InvestmentCreatedNotification($event->investment, 'victim')
        );
    }
}
