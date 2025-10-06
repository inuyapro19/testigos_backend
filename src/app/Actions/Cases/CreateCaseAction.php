<?php

namespace App\Actions\Cases;

use App\Data\CaseData;
use App\Models\CaseModel;
use App\Events\CaseCreated;
use Illuminate\Support\Facades\DB;

class CreateCaseAction
{
    public function execute(CaseData $data): CaseModel
    {
        return DB::transaction(function () use ($data) {
            $case = CaseModel::create([
                'title' => $data->title,
                'description' => $data->description,
                'victim_id' => $data->victim_id,
                'status' => $data->status->value,
                'category' => $data->category,
                'company' => $data->company,
                'funding_goal' => $data->funding_goal,
                'current_funding' => $data->current_funding,
                'success_rate' => $data->success_rate,
                'expected_return' => $data->expected_return,
                'deadline' => $data->deadline,
                'legal_analysis' => $data->legal_analysis,
                'evaluation_data' => $data->evaluation_data,
            ]);

            event(new CaseCreated($case));

            return $case->fresh();
        });
    }
}