<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\LawyerBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawyerCaseController extends Controller
{
    /**
     * Casos disponibles para que el abogado licite.
     * Incluye casos públicos Y privados (solo para autenticados).
     */
    public function availableCases(Request $request): JsonResponse
    {
        $query = CaseModel::openForBidding()
            ->with(['victim:id,name,email'])
            ->withCount('lawyerBids')
            // Agregar flag si el abogado ya licitó
            ->addSelect([
                '*',
                'has_my_bid' => LawyerBid::selectRaw('COUNT(*)')
                    ->whereColumn('case_id', 'cases.id')
                    ->where('lawyer_id', $request->user()->id)
            ]);

        // Filtros
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('only_public')) {
            $query->where('is_public_marketplace', true);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('company', 'LIKE', '%' . $request->search . '%');
            });
        }

        $cases = $query->orderBy('bid_deadline', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'message' => 'Casos disponibles para licitar',
            'data' => $cases->items(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
            ],
        ]);
    }

    /**
     * Detalle completo de un caso (para abogado autenticado).
     * Incluye todos los documentos y detalles para preparar licitación.
     */
    public function caseDetails(Request $request, $id): JsonResponse
    {
        $case = CaseModel::openForBidding()
            ->with([
                'victim',
                'documents', // Todos los documentos para abogados autenticados
            ])
            ->withCount('lawyerBids')
            ->findOrFail($id);

        // Verificar si este abogado ya licitó
        $myBid = LawyerBid::where('case_id', $id)
            ->where('lawyer_id', $request->user()->id)
            ->first();

        // Calcular días restantes para licitar
        $daysRemaining = null;
        if ($case->bid_deadline) {
            $daysRemaining = now()->diffInDays($case->bid_deadline, false);
            $daysRemaining = $daysRemaining < 0 ? 0 : $daysRemaining;
        }

        return response()->json([
            'case' => $case,
            'my_bid' => $myBid,
            'can_bid' => is_null($myBid) && $case->isOpenForBidding(),
            'days_remaining' => $daysRemaining,
            'total_bids' => $case->lawyer_bids_count,
        ]);
    }

    /**
     * Casos donde el abogado fue asignado (ganador).
     * Estos son los casos activos del abogado.
     */
    public function myAssignedCases(Request $request): JsonResponse
    {
        $query = CaseModel::where('lawyer_id', $request->user()->id)
            ->with(['victim', 'investments'])
            ->withCount('investments');

        // Filtros por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calcular estadísticas
        $stats = [
            'total_cases' => CaseModel::where('lawyer_id', $request->user()->id)->count(),
            'active_cases' => CaseModel::where('lawyer_id', $request->user()->id)
                ->whereIn('status', ['published', 'funded', 'in_progress'])
                ->count(),
            'completed_cases' => CaseModel::where('lawyer_id', $request->user()->id)
                ->where('status', 'completed')
                ->count(),
            'won_cases' => CaseModel::where('lawyer_id', $request->user()->id)
                ->where('status', 'completed')
                ->where('outcome', 'won')
                ->count(),
            'total_compensation' => CaseModel::where('lawyer_id', $request->user()->id)
                ->where('status', 'completed')
                ->whereNotNull('lawyer_paid_at')
                ->sum('lawyer_total_compensation'),
        ];

        return response()->json([
            'cases' => $cases->items(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
            ],
            'stats' => $stats,
        ]);
    }
}
