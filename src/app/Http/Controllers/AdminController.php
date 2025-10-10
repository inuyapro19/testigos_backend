<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Investment;
use App\Models\InvestorProfile;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Calculate pending withdrawals
        $pendingWithdrawals = DB::table('withdrawals')
            ->where('status', 'pending')
            ->count();

        // Calculate platform commission (5% of total invested)
        $totalInvested = Investment::confirmed()->sum('amount');
        $platformCommission = $totalInvested * 0.05;

        $stats = [
            'total_users' => User::count(),
            'total_cases' => CaseModel::count(),
            'total_investments' => Investment::confirmed()->count(),
            'total_invested' => $totalInvested,
            'total_returns' => Investment::where('status', 'completed')->sum('actual_return'),
            'pending_withdrawals' => $pendingWithdrawals,
            'active_cases' => CaseModel::whereIn('status', ['published', 'funded', 'in_progress'])->count(),
            'platform_commission' => $platformCommission,
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get all users with pagination and filters.
     */
    public function users(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::with(['lawyerProfile', 'investorProfile']);

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('rut', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => \App\Http\Resources\Admin\UserResource::collection($users)
        ]);
    }

    /**
     * Get all cases with pagination and filters.
     */
    public function cases(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = CaseModel::with(['victim', 'lawyer', 'investments']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $cases = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => \App\Http\Resources\Admin\CaseResource::collection($cases)
        ]);
    }

    /**
     * Get all investments with pagination and filters.
     */
    public function investments(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Investment::with(['case', 'investor']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('case_id')) {
            $query->where('case_id', $request->case_id);
        }

        if ($request->has('investor_id')) {
            $query->where('investor_id', $request->investor_id);
        }

        $investments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => \App\Http\Resources\Admin\InvestmentResource::collection($investments)
        ]);
    }

    /**
     * Update user status.
     */
    public function updateUserStatus(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'User status updated successfully',
            'data' => new \App\Http\Resources\Admin\UserResource($user)
        ]);
    }

    /**
     * Verify lawyer profile.
     */
    public function verifyLawyer(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lawyerProfile = LawyerProfile::findOrFail($id);

        $lawyerProfile->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Lawyer verified successfully',
            'lawyer_profile' => $lawyerProfile->load('user')
        ]);
    }

    /**
     * Accredit investor profile.
     */
    public function accreditInvestor(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $investorProfile = InvestorProfile::findOrFail($id);

        $investorProfile->update([
            'is_accredited' => true,
            'accredited_at' => now(),
        ]);

        return response()->json([
            'message' => 'Investor accredited successfully',
            'investor_profile' => $investorProfile->load('user')
        ]);
    }

    /**
     * Get platform analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Monthly user registrations
        $monthlyUsers = User::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->limit(12)
        ->get();

        // Monthly case submissions
        $monthlyCases = CaseModel::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->limit(12)
        ->get();

        // Monthly investments
        $monthlyInvestments = Investment::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as count')
        )
        ->where('status', '!=', 'cancelled')
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->limit(12)
        ->get();

        // Case categories distribution
        $caseCategories = CaseModel::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();

        // Top performing lawyers
        $topLawyers = LawyerProfile::with('user')
            ->where('cases_handled', '>', 0)
            ->orderBy('success_rate', 'desc')
            ->orderBy('total_recovered', 'desc')
            ->limit(10)
            ->get();

        // Top investors
        $topInvestors = InvestorProfile::with('user')
            ->where('total_invested', '>', 0)
            ->orderBy('total_invested', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'analytics' => [
                'monthly_users' => $monthlyUsers,
                'monthly_cases' => $monthlyCases,
                'monthly_investments' => $monthlyInvestments,
                'case_categories' => $caseCategories,
                'top_lawyers' => $topLawyers,
                'top_investors' => $topInvestors,
            ]
        ]);
    }
}
