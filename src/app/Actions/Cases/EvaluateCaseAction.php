<?php

namespace App\Actions\Cases;

use App\Models\CaseModel;
use App\Enums\CaseStatus;
use App\Events\CaseStatusChanged;
use Illuminate\Support\Facades\DB;

class EvaluateCaseAction
{
    public function execute(CaseModel $case, CaseStatus $newStatus, array $evaluationData = []): CaseModel
    {
        return DB::transaction(function () use ($case, $newStatus, $evaluationData) {
            $oldStatus = CaseStatus::from($case->status);
            
            if (!$oldStatus->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException(
                    "Cannot transition from {$oldStatus->value} to {$newStatus->value}"
                );
            }

            $case->update([
                'status' => $newStatus->value,
                'evaluation_data' => array_merge($case->evaluation_data ?? [], $evaluationData),
            ]);

            event(new CaseStatusChanged($case, $oldStatus, $newStatus));

            return $case->fresh();
        });
    }
}