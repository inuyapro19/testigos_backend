<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Investment;
use App\Models\CaseModel;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create a transaction for an investment.
     */
    public function createInvestmentTransaction(Investment $investment): Transaction
    {
        return DB::transaction(function () use ($investment) {
            // Create investment transaction
            $transaction = Transaction::create([
                'type' => 'investment',
                'case_id' => $investment->case_id,
                'investment_id' => $investment->id,
                'user_id' => $investment->investor_id,
                'amount' => $investment->amount,
                'currency' => 'CLP',
                'direction' => 'in',
                'status' => 'completed',
                'description' => "Inversión en caso #{$investment->case_id}",
                'completed_at' => now(),
            ]);

            // Create platform commission transaction
            if ($investment->platform_commission_amount > 0) {
                Transaction::create([
                    'type' => 'platform_commission',
                    'case_id' => $investment->case_id,
                    'investment_id' => $investment->id,
                    'user_id' => null, // Platform revenue
                    'amount' => $investment->platform_commission_amount,
                    'currency' => 'CLP',
                    'direction' => 'in',
                    'status' => 'completed',
                    'description' => "Comisión de plataforma ({$investment->platform_commission_percentage}%)",
                    'completed_at' => now(),
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Create a transaction for lawyer payment.
     */
    public function createLawyerPaymentTransaction(CaseModel $case, float $amount): Transaction
    {
        return Transaction::create([
            'type' => 'lawyer_payment',
            'case_id' => $case->id,
            'user_id' => $case->lawyer_id,
            'amount' => $amount,
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'completed',
            'description' => "Pago de honorarios - Caso #{$case->id}",
            'completed_at' => now(),
        ]);
    }

    /**
     * Create a transaction for investor return.
     */
    public function createInvestorReturnTransaction(Investment $investment, float $actualReturn): Transaction
    {
        return Transaction::create([
            'type' => 'investor_return',
            'case_id' => $investment->case_id,
            'investment_id' => $investment->id,
            'user_id' => $investment->investor_id,
            'amount' => $actualReturn,
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'completed',
            'description' => "Retorno de inversión - Caso #{$investment->case_id}",
            'completed_at' => now(),
        ]);
    }

    /**
     * Create a transaction for success commission.
     */
    public function createSuccessCommissionTransaction(Investment $investment, float $commissionAmount): Transaction
    {
        return Transaction::create([
            'type' => 'success_commission',
            'case_id' => $investment->case_id,
            'investment_id' => $investment->id,
            'user_id' => null, // Platform revenue
            'amount' => $commissionAmount,
            'currency' => 'CLP',
            'direction' => 'in',
            'status' => 'completed',
            'description' => "Comisión por éxito - Caso #{$investment->case_id}",
            'completed_at' => now(),
        ]);
    }

    /**
     * Create a transaction for withdrawal.
     */
    public function createWithdrawalTransaction(
        int $userId,
        float $amount,
        ?int $investmentId = null,
        ?int $caseId = null
    ): Transaction {
        return Transaction::create([
            'type' => 'withdrawal',
            'case_id' => $caseId,
            'investment_id' => $investmentId,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'pending',
            'description' => 'Solicitud de retiro',
        ]);
    }

    /**
     * Create a transaction for gateway fee.
     */
    public function createGatewayFeeTransaction(
        string $gateway,
        float $fee,
        ?int $investmentId = null,
        ?int $caseId = null
    ): Transaction {
        return Transaction::create([
            'type' => 'gateway_fee',
            'case_id' => $caseId,
            'investment_id' => $investmentId,
            'amount' => $fee,
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'completed',
            'payment_gateway' => $gateway,
            'description' => "Comisión de pasarela de pago ({$gateway})",
            'completed_at' => now(),
        ]);
    }

    /**
     * Get transaction statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_investments' => Transaction::type('investment')->completed()->sum('amount'),
            'total_platform_commissions' => Transaction::type('platform_commission')->completed()->sum('amount'),
            'total_success_commissions' => Transaction::type('success_commission')->completed()->sum('amount'),
            'total_lawyer_payments' => Transaction::type('lawyer_payment')->completed()->sum('amount'),
            'total_investor_returns' => Transaction::type('investor_return')->completed()->sum('amount'),
            'total_withdrawals' => Transaction::type('withdrawal')->completed()->sum('amount'),
            'total_gateway_fees' => Transaction::type('gateway_fee')->completed()->sum('amount'),
            'net_platform_revenue' => $this->calculateNetPlatformRevenue(),
        ];
    }

    /**
     * Calculate net platform revenue.
     */
    private function calculateNetPlatformRevenue(): float
    {
        $income = Transaction::whereIn('type', ['platform_commission', 'success_commission'])
            ->where('direction', 'in')
            ->completed()
            ->sum('amount');

        $expenses = Transaction::whereIn('type', ['lawyer_payment', 'investor_return', 'withdrawal', 'gateway_fee'])
            ->where('direction', 'out')
            ->completed()
            ->sum('amount');

        return $income - $expenses;
    }

    /**
     * Get transactions for a specific user.
     */
    public function getUserTransactions(int $userId, array $filters = [])
    {
        $query = Transaction::where('user_id', $userId)
            ->with(['case', 'investment'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(20);
    }

    /**
     * Get transactions for a specific case.
     */
    public function getCaseTransactions(int $caseId)
    {
        return Transaction::where('case_id', $caseId)
            ->with(['user', 'investment'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
