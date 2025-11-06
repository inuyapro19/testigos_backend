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
            'amount' => 'required|numeric|min:10000', // Minimum 10K CLP
            'payment_data' => 'nullable|array',
        ], [
            'case_id.required' => 'El caso es requerido',
            'case_id.exists' => 'El caso seleccionado no existe',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto mínimo de inversión es $10.000',
            'payment_data.array' => 'Los datos de pago deben ser un arreglo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
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
        ], [
            'status.required' => 'El estado es requerido',
            'status.in' => 'El estado debe ser: pendiente, confirmado, activo, completado o cancelado',
            'actual_return.numeric' => 'El retorno real debe ser un número',
            'actual_return.min' => 'El retorno real debe ser al menos 0',
            'notes.string' => 'Las notas deben ser texto',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['status', 'actual_return', 'notes']);

            // Calculate actual_return_percentage if actual_return is provided
            if ($request->has('actual_return') && $request->actual_return !== null) {
                $actualReturnPercentage = $investment->amount > 0
                    ? round((($request->actual_return - $investment->amount) / $investment->amount) * 100, 2)
                    : 0;
                $updateData['actual_return_percentage'] = $actualReturnPercentage;
            }

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
     * Get investment opportunities (published cases with lawyer assigned).
     */
    public function opportunities(Request $request): JsonResponse
    {
        if (!$request->user()->isInvestor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Solo casos PUBLISHED con abogado asignado
        $query = CaseModel::forInvestors()
            ->with([
                'lawyer.lawyerProfile',
                'victim:id,name',
                'winningBid' // Ver la propuesta ganadora
            ])
            ->where('current_funding', '<', DB::raw('funding_goal'))
            ->select([
                'id', 'title', 'description', 'category', 'company',
                'victim_id', 'lawyer_id', 'status',
                'funding_goal', 'current_funding', 'expected_return', 'success_rate',
                'deadline', 'created_at', 'published_at'
            ]);

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

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('company', 'LIKE', '%' . $request->search . '%');
            });
        }

        $opportunities = $query->orderBy('published_at', 'desc')->paginate(12);

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
