<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\CaseModel;
use App\Data\InvestmentData;
use App\Actions\Investments\CreateInvestmentAction;
use App\Enums\InvestmentStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class InvestmentService
{
    public function __construct(
        private CreateInvestmentAction $createInvestmentAction,
        private NotificationService $notificationService,
    ) {}

    public function createInvestment(InvestmentData $data): Investment
    {
        $investment = $this->createInvestmentAction->execute($data);
        
        // Notificar a la víctima
        $case = CaseModel::find($data->case_id);
        $this->notificationService->notifyUser(
            $case->victim_id,
            'Nueva inversión recibida',
            "Tu caso ha recibido una inversión de $" . number_format($data->amount, 0, ',', '.')
        );
        
        // Verificar si el caso está completamente financiado
        $case = $case->fresh();
        if ($case->current_funding >= $case->funding_goal) {
            $case->update(['status' => 'funded']);
            
            $this->notificationService->notifyUser(
                $case->victim_id,
                'Caso completamente financiado',
                "Tu caso '{$case->title}' ha alcanzado su objetivo de financiamiento"
            );
        }
        
        return $investment;
    }

    public function getInvestmentsForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Investment::with(['case', 'investor'])
            ->where('investor_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['case_id'])) {
            $query->where('case_id', $filters['case_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getInvestmentOpportunities(array $filters = []): LengthAwarePaginator
    {
        $query = CaseModel::with(['victim', 'lawyer'])
            ->where('status', 'published')
            ->whereColumn('current_funding', '<', 'funding_goal');

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['min_return'])) {
            $query->where('expected_return', '>=', $filters['min_return']);
        }

        if (isset($filters['max_funding'])) {
            $query->where('funding_goal', '<=', $filters['max_funding']);
        }

        if (isset($filters['min_success_rate'])) {
            $query->where('success_rate', '>=', $filters['min_success_rate']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(12);
    }

    public function getInvestorStatistics(int $investorId): array
    {
        $investments = Investment::where('investor_id', $investorId);

        return [
            'total_investments' => $investments->count(),
            'total_invested' => $investments->whereIn('status', ['confirmed', 'active', 'completed'])->sum('amount'),
            'active_investments' => $investments->whereIn('status', ['confirmed', 'active'])->count(),
            'completed_investments' => $investments->where('status', 'completed')->count(),
            'total_returns' => $investments->where('status', 'completed')->sum('actual_return'),
            'average_return_rate' => $investments->where('status', 'completed')->avg('actual_return_percentage') ?? 0,
        ];
    }

    public function processInvestmentReturn(Investment $investment, float $actualReturn): Investment
    {
        $investment->update([
            'actual_return' => $actualReturn,
            'status' => InvestmentStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        $this->notificationService->notifyUser(
            $investment->investor_id,
            'Inversión completada',
            "Tu inversión ha sido completada con un retorno de $" . number_format($actualReturn, 0, ',', '.')
        );

        return $investment;
    }
}