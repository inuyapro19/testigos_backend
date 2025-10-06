<?php

namespace App\Actions\Investments;

use App\Data\InvestmentData;
use App\Models\Investment;
use App\Models\CaseModel;
use App\Events\InvestmentCreated;
use App\Enums\InvestmentStatus;
use Illuminate\Support\Facades\DB;

class CreateInvestmentAction
{
    public function execute(InvestmentData $data): Investment
    {
        return DB::transaction(function () use ($data) {
            $case = CaseModel::findOrFail($data->case_id);
            
            // Verificar que el caso esté disponible para inversión
            if ($case->status !== 'published') {
                throw new \InvalidArgumentException('Case is not available for investment');
            }

            // Verificar que no exceda el financiamiento necesario
            $remainingFunding = $case->funding_goal - $case->current_funding;
            if ($data->amount > $remainingFunding) {
                throw new \InvalidArgumentException('Investment amount exceeds remaining funding needed');
            }

            $investment = Investment::create([
                'case_id' => $data->case_id,
                'investor_id' => $data->investor_id,
                'amount' => $data->amount,
                'expected_return_percentage' => $data->expected_return_percentage,
                'expected_return_amount' => $data->expected_return_amount,
                'status' => $data->status->value,
                'payment_data' => $data->payment_data,
                'notes' => $data->notes,
            ]);

            // Actualizar financiamiento del caso
            $case->increment('current_funding', $data->amount);

            // Confirmar inversión automáticamente (para demo)
            $investment->update([
                'status' => InvestmentStatus::CONFIRMED->value,
                'confirmed_at' => now(),
            ]);

            event(new InvestmentCreated($investment));

            return $investment->fresh();
        });
    }
}