<?php

namespace App\Listeners;

use App\Events\CaseStatusChanged;
use App\Notifications\CaseStatusChangedNotification;
use App\Notifications\CaseFundedNotification;
use App\Enums\CaseStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCaseStatusChangedNotification implements ShouldQueue
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
    public function handle(CaseStatusChanged $event): void
    {
        // Notificar a la víctima del cambio de estado
        $event->case->victim->notify(
            new CaseStatusChangedNotification($event->case, $event->oldStatus, $event->newStatus)
        );

        // Si el caso alcanzó la meta de financiamiento, notificar también a todos los inversionistas
        if ($event->newStatus === CaseStatus::FUNDED) {
            $investors = $event->case->investments()
                ->with('investor')
                ->get()
                ->pluck('investor')
                ->unique('id');

            foreach ($investors as $investor) {
                $investor->notify(new CaseFundedNotification($event->case));
            }
        }
    }
}
