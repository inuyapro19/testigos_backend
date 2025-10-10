<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    public function __construct(
        private WithdrawalService $withdrawalService
    ) {}

    /**
     * Get withdrawals.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Admins can see all withdrawals or pending ones
        if ($user->isAdmin()) {
            $withdrawals = \App\Models\Withdrawal::with(['user', 'investment', 'approver', 'transaction'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform to match frontend expected format
            $data = $withdrawals->map(function ($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                    'bank_account' => $withdrawal->payment_details['account_number'] ?? null,
                    'created_at' => $withdrawal->created_at->toISOString(),
                    'user' => $withdrawal->user ? [
                        'id' => $withdrawal->user->id,
                        'name' => $withdrawal->user->name,
                    ] : null,
                ];
            });

            return response()->json(['data' => $data]);
        }

        // Other users see only their withdrawals
        $withdrawals = \App\Models\Withdrawal::where('user_id', $user->id)
            ->with(['user', 'investment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $withdrawals->map(function ($withdrawal) {
            return [
                'id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                'bank_account' => $withdrawal->payment_details['account_number'] ?? null,
                'created_at' => $withdrawal->created_at->toISOString(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Create a withdrawal request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only investors and lawyers can withdraw
        if (!$user->isInvestor() && !$user->isLawyer()) {
            return response()->json(['message' => 'Only investors and lawyers can request withdrawals'], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10000', // Minimum 10,000 CLP
            'payment_method' => 'required|in:bank_transfer,check,paypal,other',
            'payment_details' => 'required|array',
            'payment_details.bank_name' => 'required_if:payment_method,bank_transfer|string',
            'payment_details.account_number' => 'required_if:payment_method,bank_transfer|string',
            'payment_details.account_holder' => 'required_if:payment_method,bank_transfer|string',
            'investment_id' => 'nullable|exists:investments,id',
            'user_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check available balance
            $availableBalance = $user->isInvestor()
                ? $this->withdrawalService->getAvailableBalance($user->id)
                : $this->withdrawalService->getLawyerAvailableBalance($user->id);

            if ($request->amount > $availableBalance) {
                return response()->json([
                    'message' => 'Insufficient balance',
                    'available_balance' => $availableBalance,
                    'requested_amount' => $request->amount
                ], 400);
            }

            $withdrawal = $this->withdrawalService->createWithdrawal(
                $user->id,
                $request->amount,
                $request->payment_method,
                $request->payment_details,
                $request->investment_id,
                $request->user_notes
            );

            return response()->json([
                'message' => 'Withdrawal request created successfully',
                'withdrawal' => $withdrawal->load(['investment', 'user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create withdrawal request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific withdrawal.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $withdrawal = \App\Models\Withdrawal::with(['user', 'investment', 'approver', 'transaction'])
            ->findOrFail($id);

        $user = $request->user();

        // Check permissions
        if (!$user->isAdmin() && $withdrawal->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['withdrawal' => $withdrawal]);
    }

    /**
     * Approve a withdrawal (admin only).
     */
    public function approve(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $withdrawal = $this->withdrawalService->approveWithdrawal($id, $request->user()->id);

            return response()->json([
                'message' => 'Withdrawal approved successfully',
                'data' => [
                    'id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                    'bank_account' => $withdrawal->payment_details['account_number'] ?? null,
                    'created_at' => $withdrawal->created_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve withdrawal',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject a withdrawal (admin only).
     */
    public function reject(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $withdrawal = $this->withdrawalService->rejectWithdrawal($id, $request->reason ?? 'No reason provided');

            return response()->json([
                'message' => 'Withdrawal rejected',
                'data' => [
                    'id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                    'bank_account' => $withdrawal->payment_details['account_number'] ?? null,
                    'created_at' => $withdrawal->created_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject withdrawal',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process a withdrawal (admin only).
     */
    public function process(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $withdrawal = $this->withdrawalService->processWithdrawal($id);

            return response()->json([
                'message' => 'Withdrawal processing started',
                'withdrawal' => $withdrawal->load(['user', 'investment', 'transaction'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process withdrawal',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete a withdrawal (admin only).
     */
    public function complete(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $withdrawal = $this->withdrawalService->completeWithdrawal($id, $request->transaction_id ?? '');

            return response()->json([
                'message' => 'Withdrawal completed successfully',
                'data' => [
                    'id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                    'bank_account' => $withdrawal->payment_details['account_number'] ?? null,
                    'created_at' => $withdrawal->created_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete withdrawal',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel a withdrawal.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $withdrawal = \App\Models\Withdrawal::findOrFail($id);
        $user = $request->user();

        // Only owner or admin can cancel
        if (!$user->isAdmin() && $withdrawal->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $withdrawal = $this->withdrawalService->cancelWithdrawal($id);

            return response()->json([
                'message' => 'Withdrawal cancelled successfully',
                'withdrawal' => $withdrawal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel withdrawal',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get available balance for withdrawal.
     */
    public function availableBalance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInvestor() && !$user->isLawyer()) {
            return response()->json(['message' => 'Only investors and lawyers can check balance'], 403);
        }

        $availableBalance = $user->isInvestor()
            ? $this->withdrawalService->getAvailableBalance($user->id)
            : $this->withdrawalService->getLawyerAvailableBalance($user->id);

        return response()->json([
            'data' => [
                'available_balance' => $availableBalance,
                'currency' => 'CLP'
            ]
        ]);
    }

    /**
     * Get withdrawal statistics (admin only).
     */
    public function statistics(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statistics = $this->withdrawalService->getStatistics();

        return response()->json(['data' => $statistics]);
    }
}
