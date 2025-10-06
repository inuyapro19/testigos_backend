<?php

namespace App\Actions\Cases;

use App\Models\CaseModel;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;

class DistributeReturnsAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Distribute returns to investors after a case is won.
     */
    public function execute(CaseModel $case): array
    {
        if (!$case->isCompleted()) {
            throw new \InvalidArgumentException('Case must be completed to distribute returns');
        }

        if (!$case->wasWon()) {
            throw new \InvalidArgumentException('Can only distribute returns for won cases');
        }

        if (!$case->amount_recovered || $case->amount_recovered <= 0) {
            throw new \InvalidArgumentException('No amount recovered to distribute');
        }

        return DB::transaction(function () use ($case) {
            $results = [
                'lawyer_payment' => null,
                'investor_returns' => [],
                'platform_commission' => null,
            ];

            // Step 1: Calculate and pay lawyer
            $lawyerCompensation = $case->calculateLawyerCompensation();
            if ($lawyerCompensation > 0 && $case->lawyer_id) {
                $case->update([
                    'lawyer_total_compensation' => $lawyerCompensation,
                    'lawyer_paid_at' => now(),
                ]);

                // Create transaction for lawyer payment
                $results['lawyer_payment'] = $this->transactionService
                    ->createLawyerPaymentTransaction($case, $lawyerCompensation);

                // Update lawyer profile statistics
                if ($case->lawyer && $case->lawyer->lawyerProfile) {
                    $case->lawyer->lawyerProfile->updateStatistics();
                }
            }

            // Step 2: Calculate net amount for distribution
            $netAmountForDistribution = $case->net_amount_for_distribution;

            if ($netAmountForDistribution <= 0) {
                return $results;
            }

            // Step 3: Get all confirmed investments for this case
            $investments = $case->investments()->confirmed()->get();
            $totalInvested = $investments->sum('amount');

            if ($totalInvested == 0) {
                return $results;
            }

            // Step 4: Calculate success commission (e.g., 10% on net returns)
            $successCommissionPercentage = 10;
            $totalSuccessCommission = 0;

            // Step 5: Distribute returns to each investor proportionally
            foreach ($investments as $investment) {
                // Calculate proportional share
                $investmentShare = $investment->amount / $totalInvested;
                $proportionalReturn = $netAmountForDistribution * $investmentShare;

                // Calculate profit (return - original investment)
                $profit = max(0, $proportionalReturn - $investment->amount);

                // Calculate success commission on profit
                $successCommission = ($profit * $successCommissionPercentage) / 100;
                $totalSuccessCommission += $successCommission;

                // Calculate actual return (proportional return - success commission)
                $actualReturn = $proportionalReturn - $successCommission;

                // Update investment
                $investment->update([
                    'actual_return' => $actualReturn,
                    'success_commission_percentage' => $successCommissionPercentage,
                    'success_commission_amount' => $successCommission,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Create transaction for investor return
                $results['investor_returns'][] = [
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'actual_return' => $actualReturn,
                    'transaction' => $this->transactionService
                        ->createInvestorReturnTransaction($investment, $actualReturn),
                ];

                // Create transaction for success commission
                if ($successCommission > 0) {
                    $this->transactionService
                        ->createSuccessCommissionTransaction($investment, $successCommission);
                }

                // Update investor profile statistics
                if ($investment->investor && $investment->investor->investorProfile) {
                    $investment->investor->investorProfile->updateStatistics();
                }
            }

            $results['platform_commission'] = $totalSuccessCommission;

            return $results;
        });
    }
}
