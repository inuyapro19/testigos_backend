<?php

namespace App\Jobs;

use App\Models\CaseModel;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCaseUpdateNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private CaseModel $case,
        private string $title,
        private string $message
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        // Notificar a la víctima
        $notificationService->notifyUser(
            $this->case->victim_id,
            $this->title,
            $this->message,
            'case_update',
            ['case_id' => $this->case->id]
        );

        // Si el caso tiene inversionistas, notificarlos también
        if ($this->case->investments()->exists()) {
            $investorIds = $this->case->investments()->pluck('investor_id')->unique();
            
            foreach ($investorIds as $investorId) {
                $notificationService->notifyUser(
                    $investorId,
                    $this->title,
                    $this->message,
                    'investment_update',
                    ['case_id' => $this->case->id]
                );
            }
        }
    }
}