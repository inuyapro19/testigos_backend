<?php

namespace App\Listeners;

use App\Events\CaseCreated;
use App\Notifications\CaseCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCaseCreatedNotification implements ShouldQueue
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
    public function handle(CaseCreated $event): void
    {
        // Notificar a la víctima que su caso fue creado
        $event->case->victim->notify(new CaseCreatedNotification($event->case));
    }
}
