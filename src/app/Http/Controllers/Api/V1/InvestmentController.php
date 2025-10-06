<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CaseUpdate;
use App\Models\Investment;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvestmentController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Get all investments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Investment::with(['case', 'investor']);

        // Filter by investor for non-admin users
        if ($request->user()->isInvestor()) {
            $query->where('investor_id', $request->user()->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('case_id')) {
            $query->where('case_id', $request->case_id);
        }

        $investments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($investments);
    }

    /**
     * Create a new investment.
     */
    public function store(Request $request): JsonResponse
    {
        // Only investors can create investments
        if (!$request->user()->isInvestor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'case_id' => 'required|exists:cases,id',
            'amount' => 'required|numeric|min:1000000', // Minimum 1M CLP
            'payment_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $case = CaseModel::findOrFail($request->case_id);

            // Check if case is available for investment
            if ($case->status !== 'published') {
                return response()->json([
                    'message' => 'Case is not available for investment'
                ], 400);
            }

            // Check if case is already fully funded
            if ($case->isFullyFunded()) {
                return response()->json([
                    'message' => 'Case is already fully funded'
                ], 400);
            }

            // Check if investment amount doesn't exceed remaining funding needed
            $remainingFunding = $case->remaining_funding;
            $investmentAmount = min($request->amount, $remainingFunding);

            // Calculate expected return
            $expectedReturnPercentage = $case->expected_return;
            $expectedReturnAmount = ($investmentAmount * $expectedReturnPercentage) / 100;

            // Calculate platform commission (default 7.5% on investment amount)
            $platformCommissionPercentage = 7.5;
            $platformCommissionAmount = ($investmentAmount * $platformCommissionPercentage) / 100;

            DB::beginTransaction();

            $investment = Investment::create([
                'case_id' => $case->id,
                'investor_id' => $request->user()->id,
                'amount' => $investmentAmount,
                'expected_return_percentage' => $expectedReturnPercentage,
                'expected_return_amount' => $expectedReturnAmount,
                'status' => 'pending',
                'payment_data' => $request->payment_data,
                'platform_commission_percentage' => $platformCommissionPercentage,
                'platform_commission_amount' => $platformCommissionAmount,
            ]);

            // Update case funding (for demo purposes, we'll confirm immediately)
            $case->increment('current_funding', $investmentAmount);

            // Update investment status to confirmed
            $investment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Check if case is now fully funded
            if ($case->fresh()->isFullyFunded()) {
                $case->update(['status' => 'funded']);

                // Create case update for full funding
                CaseUpdate::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'title' => 'Caso completamente financiado',
                    'description' => 'El caso ha alcanzado su objetivo de financiamiento.',
                    'type' => 'funding_update',
                    'notify_victim' => true,
                    'notify_investors' => true,
                ]);
            } else {
                // Create case update for new investment
                CaseUpdate::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'title' => 'Nueva inversión recibida',
                    'description' => "Se ha recibido una inversión de $" . number_format($investmentAmount, 0, ',', '.'),
                    'type' => 'funding_update',
                    'metadata' => ['investment_id' => $investment->id],
                    'notify_victim' => true,
                ]);
            }

            // Update investor profile statistics
            $investorProfile = $request->user()->investorProfile;
            if ($investorProfile) {
                $investorProfile->updateStatistics();
            }

            // Create transaction records
            $this->transactionService->createInvestmentTransaction($investment);

            DB::commit();

            return response()->json([
                'message' => 'Investment created successfully',
                'investment' => $investment->load(['case', 'investor'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Investment creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific investment.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $investment = Investment::with(['case', 'investor'])->findOrFail($id);

        // Check permissions
        $user = $request->user();
        if ($user->isInvestor() && $investment->investor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['investment' => $investment]);
    }

    /**
     * Update investment status (admin only).
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $investment = Investment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,active,completed,cancelled',
            'actual_return' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['status', 'actual_return', 'notes']);

            if ($request->status === 'completed') {
                $updateData['completed_at'] = now();
            }

            $investment->update($updateData);

            // Update investor profile statistics
            $investorProfile = $investment->investor->investorProfile;
            if ($investorProfile) {
                $investorProfile->updateStatistics();
            }

            return response()->json([
                'message' => 'Investment updated successfully',
                'investment' => $investment->fresh()->load(['case', 'investor'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Investment update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get investment statistics for investor.
     */
    public function statistics(Request $request): JsonResponse
    {
        if (!$request->user()->isInvestor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $investorId = $request->user()->id;

        $stats = [
            'total_investments' => Investment::where('investor_id', $investorId)->count(),
            'total_invested' => Investment::where('investor_id', $investorId)
                ->confirmed()
                ->sum('amount'),
            'active_investments' => Investment::where('investor_id', $investorId)
                ->active()
                ->count(),
            'completed_investments' => Investment::where('investor_id', $investorId)
                ->where('status', 'completed')
                ->count(),
            'total_returns' => Investment::where('investor_id', $investorId)
                ->where('status', 'completed')
                ->sum('actual_return'),
            'average_return_rate' => Investment::where('investor_id', $investorId)
                ->where('status', 'completed')
                ->avg('actual_return_percentage'),
        ];

        $stats['net_profit'] = $stats['total_returns'] - $stats['total_invested'];

        return response()->json(['statistics' => $stats]);
    }

    /**
     * Get investment opportunities (published cases).
     */
    public function opportunities(Request $request): JsonResponse
    {
        if (!$request->user()->isInvestor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = CaseModel::with(['victim', 'lawyer'])
            ->where('status', 'published')
            ->where('current_funding', '<', 'funding_goal');

        // Apply filters
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('min_return')) {
            $query->where('expected_return', '>=', $request->min_return);
        }

        if ($request->has('max_funding')) {
            $query->where('funding_goal', '<=', $request->max_funding);
        }

        if ($request->has('min_success_rate')) {
            $query->where('success_rate', '>=', $request->min_success_rate);
        }

        $opportunities = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json($opportunities);
    }

    /**
     * Cancel an investment (only pending ones).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $investment = Investment::findOrFail($id);
        $user = $request->user();

        // Only investor can cancel their own investment
        if ($investment->investor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only cancel pending investments
        if ($investment->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending investments can be cancelled'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $investment->update(['status' => 'cancelled']);

            // Decrease case funding
            $investment->case->decrement('current_funding', $investment->amount);

            DB::commit();

            return response()->json([
                'message' => 'Investment cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel investment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
