<?php

namespace App\Actions\Cases;

use App\Models\CaseModel;
use Illuminate\Support\Facades\DB;

class CloseCaseAction
{
    /**
     * Close a case with outcome.
     */
    public function execute(
        CaseModel $case,
        string $outcome,
        ?float $amountRecovered = null,
        ?float $legalCosts = null,
        ?string $outcomeDescription = null,
        ?string $resolutionDate = null
    ): CaseModel {
        if (!$case->canBeClosed()) {
            throw new \InvalidArgumentException(
                "Case must be funded or in_progress to be closed. Current status: {$case->status}"
            );
        }

        if (!in_array($outcome, ['won', 'lost', 'settled', 'dismissed'])) {
            throw new \InvalidArgumentException(
                "Invalid outcome: {$outcome}. Must be one of: won, lost, settled, dismissed"
            );
        }

        return DB::transaction(function () use (
            $case,
            $outcome,
            $amountRecovered,
            $legalCosts,
            $outcomeDescription,
            $resolutionDate
        ) {
            $updateData = [
                'status' => 'completed',
                'outcome' => $outcome,
                'amount_recovered' => $amountRecovered ?? 0,
                'legal_costs' => $legalCosts ?? 0,
                'outcome_description' => $outcomeDescription,
                'resolution_date' => $resolutionDate ?? now()->toDateString(),
                'closed_at' => now(),
            ];

            $case->update($updateData);

            return $case->fresh();
        });
    }
}
