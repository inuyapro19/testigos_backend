<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use App\Models\CaseModel;
use App\Models\Investment;
use Illuminate\Console\Command;

class SendNotifications extends Command
{
    protected $signature = 'testigo:send-notifications';
    protected $description = 'Send pending notifications to users';

    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending pending notifications...');

        // Notificar casos que necesitan atención
        $this->notifyCasesPendingReview();
        
        // Notificar inversiones que necesitan confirmación
        $this->notifyPendingInvestments();
        
        // Notificar casos próximos a vencer
        $this->notifyExpiringCases();

        $this->info('Notifications sent successfully!');
        return 0;
    }

    private function notifyCasesPendingReview(): void
    {
        $pendingCases = CaseModel::where('status', 'submitted')
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        if ($pendingCases > 0) {
            $this->notificationService->notifyAdmins(
                'Casos pendientes de revisión',
                "Hay {$pendingCases} casos esperando revisión por más de 24 horas"
            );
        }
    }

    private function notifyPendingInvestments(): void
    {
        $pendingInvestments = Investment::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(2))
            ->count();

        if ($pendingInvestments > 0) {
            $this->notificationService->notifyAdmins(
                'Inversiones pendientes',
                "Hay {$pendingInvestments} inversiones pendientes de confirmación"
            );
        }
    }

    private function notifyExpiringCases(): void
    {
        $expiringCases = CaseModel::where('status', 'published')
            ->where('deadline', '<=', now()->addDays(7))
            ->where('deadline', '>', now())
            ->get();

        foreach ($expiringCases as $case) {
            $daysLeft = now()->diffInDays($case->deadline);
            
            $this->notificationService->notifyUser(
                $case->victim_id,
                'Caso próximo a vencer',
                "Tu caso '{$case->title}' vence en {$daysLeft} días"
            );
        }
    }
}