<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Data\CaseData;
use App\Actions\Cases\CreateCaseAction;
use App\Actions\Cases\EvaluateCaseAction;
use App\Actions\Cases\PublishCaseAction;
use App\Enums\CaseStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class CaseService
{
    public function __construct(
        private CreateCaseAction $createCaseAction,
        private EvaluateCaseAction $evaluateCaseAction,
        private PublishCaseAction $publishCaseAction,
        private DocumentService $documentService,
        private NotificationService $notificationService,
    ) {}

    public function createCase(CaseData $data, array $documents = []): CaseModel
    {
        $case = $this->createCaseAction->execute($data);
        
        if (!empty($documents)) {
            $this->documentService->uploadDocuments($case, $documents);
        }
        
        $this->notificationService->notifyAdmins(
            'Nuevo caso creado',
            "Se ha creado un nuevo caso: {$case->title}"
        );
        
        return $case;
    }

    public function evaluateCase(CaseModel $case, CaseStatus $status, array $evaluationData = []): CaseModel
    {
        $evaluatedCase = $this->evaluateCaseAction->execute($case, $status, $evaluationData);
        
        $this->notificationService->notifyUser(
            $case->victim_id,
            'Estado del caso actualizado',
            "Tu caso '{$case->title}' ha sido {$status->label()}"
        );
        
        return $evaluatedCase;
    }

    public function publishCase(CaseModel $case, array $publishData): CaseModel
    {
        $publishedCase = $this->publishCaseAction->execute($case, $publishData);
        
        $this->notificationService->notifyInvestors(
            'Nueva oportunidad de inversión',
            "Nuevo caso disponible: {$case->title}"
        );
        
        return $publishedCase;
    }

    public function getCasesForUser(int $userId, string $role, array $filters = []): LengthAwarePaginator
    {
        $query = CaseModel::with(['victim', 'lawyer', 'documents', 'investments']);

        // Aplicar filtros según el rol
        switch ($role) {
            case 'victim':
                $query->where('victim_id', $userId);
                break;
            case 'lawyer':
                $query->where(function($q) use ($userId) {
                    $q->where('lawyer_id', $userId)
                      ->orWhereIn('status', ['submitted', 'under_review']);
                });
                break;
            case 'investor':
                $query->where('status', 'published');
                break;
            case 'admin':
                // Admin puede ver todos los casos
                break;
        }

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('company', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getCaseStatistics(): array
    {
        return [
            'total' => CaseModel::count(),
            'by_status' => CaseModel::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_category' => CaseModel::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'total_funding_goal' => CaseModel::sum('funding_goal'),
            'total_current_funding' => CaseModel::sum('current_funding'),
        ];
    }
}