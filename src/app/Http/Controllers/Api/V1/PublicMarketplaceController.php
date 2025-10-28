<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMarketplaceController extends Controller
{
    /**
     * Marketplace público: casos aprobados para recibir licitaciones.
     * Solo muestra casos marcados como is_public_marketplace = true.
     * NO requiere autenticación.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CaseModel::publicMarketplace()
            ->with(['victim:id,name']) // Solo nombre de víctima, sin datos sensibles
            ->withCount('lawyerBids')
            ->select([
                'id', 'title', 'description', 'category', 'company',
                'victim_id', 'status', 'bid_deadline', 'created_at'
            ]);

        // Filtros
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('company', 'LIKE', '%' . $request->search . '%');
            });
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json([
            'message' => 'Casos disponibles para licitación pública',
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
     * Detalle de un caso público.
     * Muestra información completa pero sin datos sensibles.
     */
    public function show($id): JsonResponse
    {
        $case = CaseModel::publicMarketplace()
            ->with([
                'victim:id,name,email',
                'documents' => function($q) {
                    // Solo documentos públicos
                    $q->where('is_public', true);
                }
            ])
            ->withCount('lawyerBids')
            ->findOrFail($id);

        return response()->json([
            'data' => $case
        ]);
    }

    /**
     * Estadísticas públicas de la plataforma (opcional).
     * Para mostrar credibilidad en landing page.
     */
    public function platformStats(): JsonResponse
    {
        return response()->json([
            'total_cases' => CaseModel::count(),
            'completed_cases' => CaseModel::where('status', 'completed')->count(),
            'active_cases' => CaseModel::whereIn('status', ['funded', 'in_progress'])->count(),
            'total_lawyers' => User::role('lawyer')->count(),
            'total_investors' => User::role('investor')->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ]);
    }

    /**
     * Calcular tasa de éxito de casos completados.
     */
    private function calculateSuccessRate(): float
    {
        $completedCases = CaseModel::where('status', 'completed')->count();

        if ($completedCases === 0) {
            return 0;
        }

        $wonCases = CaseModel::where('status', 'completed')
            ->where('outcome', 'won')
            ->count();

        return round(($wonCases / $completedCases) * 100, 2);
    }
}
