<?php

namespace App\Services;

use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class WithdrawalService
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Create a withdrawal request.
     */
    public function createWithdrawal(
        int $userId,
        float $amount,
        string $paymentMethod,
        array $paymentDetails,
        ?int $investmentId = null,
        ?string $userNotes = null
    ): Withdrawal {
        return DB::transaction(function () use (
            $userId,
            $amount,
            $paymentMethod,
            $paymentDetails,
            $investmentId,
            $userNotes
        ) {
            // Calculate withdrawal fee (e.g., 2%)
            $feePercentage = 2;
            $fee = ($amount * $feePercentage) / 100;
            $netAmount = $amount - $fee;

            $withdrawal = Withdrawal::create([
                'user_id' => $userId,
                'investment_id' => $investmentId,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'currency' => 'CLP',
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_details' => $paymentDetails,
                'user_notes' => $userNotes,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Approve a withdrawal request.
     */
    public function approveWithdrawal(int $withdrawalId, int $approvedBy): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $approvedBy) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if (!$withdrawal->canBeApproved()) {
                throw new \Exception('Withdrawal cannot be approved in current status');
            }

            $withdrawal->approve($approvedBy);

            return $withdrawal;
        });
    }

    /**
     * Reject a withdrawal request.
     */
    public function rejectWithdrawal(int $withdrawalId, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $reason) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if (!$withdrawal->canBeApproved()) {
                throw new \Exception('Withdrawal cannot be rejected in current status');
            }

            $withdrawal->reject($reason);

            return $withdrawal;
        });
    }

    /**
     * Process an approved withdrawal.
     */
    public function processWithdrawal(
        int $withdrawalId,
        ?string $transferReference = null
    ): Withdrawal {
        return DB::transaction(function () use ($withdrawalId, $transferReference) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if (!$withdrawal->canBeProcessed()) {
                throw new \Exception('Withdrawal cannot be processed in current status');
            }

            $withdrawal->markAsProcessing();

            // Create transaction record
            $transaction = $this->transactionService->createWithdrawalTransaction(
                $withdrawal->user_id,
                $withdrawal->net_amount,
                $withdrawal->investment_id,
                null
            );

            $transaction->update([
                'status' => 'processing',
                'processed_at' => now(),
            ]);

            // Link transaction to withdrawal
            $withdrawal->update([
                'transaction_id' => $transaction->id,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Complete a withdrawal.
     */
    public function completeWithdrawal(
        int $withdrawalId,
        string $transferReference
    ): Withdrawal {
        return DB::transaction(function () use ($withdrawalId, $transferReference) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            $withdrawal->markAsCompleted($transferReference);

            // Update transaction status
            if ($withdrawal->transaction) {
                $withdrawal->transaction->markAsCompleted();
            }

            return $withdrawal;
        });
    }

    /**
     * Cancel a withdrawal.
     */
    public function cancelWithdrawal(int $withdrawalId): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->isCompleted()) {
                throw new \Exception('Cannot cancel a completed withdrawal');
            }

            $withdrawal->cancel();

            // Cancel associated transaction if exists
            if ($withdrawal->transaction) {
                $withdrawal->transaction->update(['status' => 'cancelled']);
            }

            return $withdrawal;
        });
    }

    /**
     * Get withdrawals for a specific user.
     */
    public function getUserWithdrawals(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Withdrawal::where('user_id', $userId)
            ->with(['investment', 'approver', 'transaction'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15);
    }

    /**
     * Get all pending withdrawals (for admins).
     */
    public function getPendingWithdrawals(): LengthAwarePaginator
    {
        return Withdrawal::pending()
            ->with(['user', 'investment'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);
    }

    /**
     * Get withdrawal statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_pending' => Withdrawal::pending()->count(),
            'total_approved' => Withdrawal::approved()->count(),
            'total_completed' => Withdrawal::completed()->count(),
            'total_amount_pending' => Withdrawal::pending()->sum('amount'),
            'total_amount_completed' => Withdrawal::completed()->sum('net_amount'),
            'total_fees_collected' => Withdrawal::completed()->sum('fee'),
        ];
    }

    /**
     * Get available balance for withdrawal (investor).
     */
    public function getAvailableBalance(int $userId): float
    {
        $user = User::findOrFail($userId);

        // Sum all completed investments actual returns
        $totalReturns = $user->investments()
            ->where('status', 'completed')
            ->sum('actual_return');

        // Subtract already withdrawn amounts
        $totalWithdrawn = $user->withdrawals()
            ->completed()
            ->sum('amount');

        return max(0, $totalReturns - $totalWithdrawn);
    }

    /**
     * Get available balance for withdrawal (lawyer).
     */
    public function getLawyerAvailableBalance(int $userId): float
    {
        $user = User::findOrFail($userId);

        // Sum all lawyer compensations from completed cases
        $totalCompensation = $user->lawyerCases()
            ->where('status', 'completed')
            ->whereNotNull('lawyer_paid_at')
            ->sum('lawyer_total_compensation');

        // Subtract already withdrawn amounts
        $totalWithdrawn = $user->withdrawals()
            ->completed()
            ->sum('amount');

        return max(0, $totalCompensation - $totalWithdrawn);
    }
}
