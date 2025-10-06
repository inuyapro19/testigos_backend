<?php

namespace App\Actions\Cases;

use App\Models\CaseModel;
use App\Enums\CaseStatus;
use App\Events\CasePublished;
use Illuminate\Support\Facades\DB;

class PublishCaseAction
{
    public function execute(CaseModel $case, array $publishData): CaseModel
    {
        return DB::transaction(function () use ($case, $publishData) {
            $case->update([
                'status' => CaseStatus::PUBLISHED->value,
                'funding_goal' => $publishData['funding_goal'],
                'success_rate' => $publishData['success_rate'],
                'expected_return' => $publishData['expected_return'],
                'deadline' => $publishData['deadline'],
                'legal_analysis' => $publishData['legal_analysis'],
                'lawyer_id' => auth()->id(),
            ]);

            event(new CasePublished($case));

            return $case->fresh();
        });
    }
}