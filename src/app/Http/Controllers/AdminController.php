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

        $stats = [
            'users' => [
                'total' => User::count(),
                'victims' => User::where('role', 'victim')->count(),
                'lawyers' => User::where('role', 'lawyer')->count(),
                'investors' => User::where('role', 'investor')->count(),
                'active' => User::where('is_active', true)->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'cases' => [
                'total' => CaseModel::count(),
                'submitted' => CaseModel::where('status', 'submitted')->count(),
                'under_review' => CaseModel::where('status', 'under_review')->count(),
                'published' => CaseModel::where('status', 'published')->count(),
                'funded' => CaseModel::where('status', 'funded')->count(),
                'completed' => CaseModel::where('status', 'completed')->count(),
                'rejected' => CaseModel::where('status', 'rejected')->count(),
            ],
            'investments' => [
                'total_amount' => Investment::confirmed()->sum('amount'),
                'total_count' => Investment::confirmed()->count(),
                'active_count' => Investment::active()->count(),
                'completed_count' => Investment::where('status', 'completed')->count(),
                'average_amount' => Investment::confirmed()->avg('amount'),
                'total_returns' => Investment::where('status', 'completed')->sum('actual_return'),
            ],
            'financial' => [
                'total_funding_goal' => CaseModel::whereNotNull('funding_goal')->sum('funding_goal'),
                'total_current_funding' => CaseModel::sum('current_funding'),
                'funding_percentage' => 0,
            ],
        ];

        // Calculate funding percentage
        if ($stats['financial']['total_funding_goal'] > 0) {
            $stats['financial']['funding_percentage'] = round(
                ($stats['financial']['total_current_funding'] / $stats['financial']['total_funding_goal']) * 100,
                2
            );
        }

        return response()->json(['statistics' => $stats]);
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

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
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

        $cases = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($cases);
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

        $investments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($investments);
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
            'user' => $user
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
