<?php

namespace App\Actions\Cases;

use App\Models\CaseModel;
use Illuminate\Support\Facades\DB;

class StartCaseAction
{
    /**
     * Start a funded case (change status to in_progress).
     */
    public function execute(CaseModel $case): CaseModel
    {
        if ($case->status !== 'funded') {
            throw new \InvalidArgumentException(
                "Case must be funded to start. Current status: {$case->status}"
            );
        }

        return DB::transaction(function () use ($case) {
            $case->update([
                'status' => 'in_progress',
            ]);

            return $case->fresh();
        });
    }
}
