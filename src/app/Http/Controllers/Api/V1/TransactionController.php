<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Get transactions for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Admins can see all transactions
        if ($user->isAdmin()) {
            $transactions = \App\Models\Transaction::with(['case', 'investment', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($transactions);
        }

        // Other users see only their transactions
        $filters = $request->only(['type', 'status']);
        $transactions = $this->transactionService->getUserTransactions($user->id, $filters);

        return response()->json($transactions);
    }

    /**
     * Get a specific transaction.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $transaction = \App\Models\Transaction::with(['case', 'investment', 'user'])
            ->findOrFail($id);

        $user = $request->user();

        // Check permissions
        if (!$user->isAdmin() && $transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['transaction' => $transaction]);
    }

    /**
     * Get transactions for a specific case.
     */
    public function caseTransactions(Request $request, $caseId): JsonResponse
    {
        $user = $request->user();

        // Verify user has access to this case
        $case = \App\Models\CaseModel::findOrFail($caseId);

        if (!$user->isAdmin() &&
            $case->victim_id !== $user->id &&
            $case->lawyer_id !== $user->id) {
            // Check if user is an investor in this case
            $hasInvestment = \App\Models\Investment::where('case_id', $caseId)
                ->where('investor_id', $user->id)
                ->exists();

            if (!$hasInvestment) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $transactions = $this->transactionService->getCaseTransactions($caseId);

        return response()->json(['transactions' => $transactions]);
    }

    /**
     * Get transaction statistics (admin only).
     */
    public function statistics(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statistics = $this->transactionService->getStatistics();

        return response()->json(['statistics' => $statistics]);
    }
}
