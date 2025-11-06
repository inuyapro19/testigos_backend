<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\LawyerBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminCaseController extends Controller
{
    /**
     * Casos pendientes de revisión por admin.
     */
    public function pendingReview(Request $request): JsonResponse
    {
        $cases = CaseModel::whereIn('status', ['submitted', 'under_admin_review'])
            ->with(['victim:id,name,email,phone'])
            ->withCount('documents')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($cases);
    }

    /**
     * Aprobar caso para licitación.
     */
    public function approveForBidding(Request $request, $caseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bid_deadline' => 'required|date|after:now',
            'is_public_marketplace' => 'required|boolean',
            'admin_notes' => 'nullable|string',
        ], [
            'bid_deadline.required' => 'La fecha límite para licitar es requerida',
            'bid_deadline.date' => 'La fecha límite debe ser una fecha válida',
            'bid_deadline.after' => 'La fecha límite debe ser posterior a la fecha actual',
            'is_public_marketplace.required' => 'La visibilidad en marketplace es requerida',
            'is_public_marketplace.boolean' => 'La visibilidad en marketplace debe ser verdadero o falso',
            'admin_notes.string' => 'Las notas del administrador deben ser texto',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $case = CaseModel::findOrFail($caseId);

        if (!in_array($case->status, ['submitted', 'under_admin_review'])) {
            return response()->json([
                'message' => 'Solo casos en revisión pueden ser aprobados. Estado actual: ' . $case->status
            ], 400);
        }

        try {
            $case->update([
                'status' => 'approved_for_bidding',
                'bid_deadline' => $request->bid_deadline,
                'is_public_marketplace' => $request->is_public_marketplace,
                'admin_review_notes' => $request->admin_notes,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            // TODO: Notificar víctima
            // event(new CaseApprovedForBidding($case));

            return response()->json([
                'message' => 'Caso aprobado para licitación',
                'data' => $case->fresh(['victim', 'reviewer'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aprobar caso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar caso.
     */
    public function rejectCase(Request $request, $caseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:20',
        ], [
            'rejection_reason.required' => 'La razón de rechazo es requerida',
            'rejection_reason.string' => 'La razón de rechazo debe ser texto',
            'rejection_reason.min' => 'La razón de rechazo debe tener al menos 20 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $case = CaseModel::findOrFail($caseId);

        try {
            $case->update([
                'status' => 'rejected',
                'admin_review_notes' => $request->rejection_reason,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            // TODO: Notificar víctima
            // event(new CaseRejected($case));

            return response()->json([
                'message' => 'Caso rechazado',
                'data' => $case
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar caso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver todas las licitaciones de un caso.
     */
    public function getCaseBids(Request $request, $caseId): JsonResponse
    {
        $case = CaseModel::with([
            'lawyerBids' => function($q) {
                $q->with(['lawyer.lawyerProfile', 'reviewer:id,name,email'])
                  ->orderByRaw("CASE
                      WHEN status = 'submitted' THEN 1
                      WHEN status = 'under_review' THEN 2
                      WHEN status = 'accepted' THEN 3
                      WHEN status = 'rejected' THEN 4
                      WHEN status = 'withdrawn' THEN 5
                      ELSE 6
                  END")
                  ->orderBy('admin_score', 'desc')
                  ->orderBy('created_at', 'desc');
            },
            'victim:id,name,email'
        ])->findOrFail($caseId);

        $bidsSummary = [
            'total' => $case->lawyerBids->count(),
            'submitted' => $case->lawyerBids->where('status', 'submitted')->count(),
            'under_review' => $case->lawyerBids->where('status', 'under_review')->count(),
            'accepted' => $case->lawyerBids->where('status', 'accepted')->count(),
            'rejected' => $case->lawyerBids->where('status', 'rejected')->count(),
            'withdrawn' => $case->lawyerBids->where('status', 'withdrawn')->count(),
        ];

        return response()->json([
            'data' => $case->lawyerBids,
            'case' => $case,
            'bids_summary' => $bidsSummary
        ]);
    }

    /**
     * Cerrar licitación (no recibir más ofertas).
     */
    public function closeBidding(Request $request, $caseId): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);

        if (!in_array($case->status, ['approved_for_bidding', 'receiving_bids'])) {
            return response()->json([
                'message' => 'El caso no está recibiendo licitaciones. Estado actual: ' . $case->status
            ], 400);
        }

        $case->update(['status' => 'bids_closed']);

        return response()->json([
            'message' => 'Licitación cerrada. Ahora puedes evaluar y asignar abogado',
            'data' => $case
        ]);
    }

    /**
     * Reabrir licitación para recibir más ofertas.
     */
    public function reopenBidding(Request $request, $caseId): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);

        if ($case->status !== 'bids_closed') {
            return response()->json([
                'message' => 'Solo se pueden reabrir licitaciones cerradas. Estado actual: ' . $case->status
            ], 400);
        }

        $case->update(['status' => 'approved_for_bidding']);

        return response()->json([
            'message' => 'Licitación reabierta. Los abogados pueden enviar nuevas propuestas',
            'data' => $case
        ]);
    }

    /**
     * Evaluar una licitación específica.
     */
    public function reviewBid(Request $request, $bidId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_score' => 'required|integer|min:1|max:10',
            'admin_feedback' => 'nullable|string',
        ], [
            'admin_score.required' => 'La puntuación del administrador es requerida',
            'admin_score.integer' => 'La puntuación debe ser un número entero',
            'admin_score.min' => 'La puntuación mínima es 1',
            'admin_score.max' => 'La puntuación máxima es 10',
            'admin_feedback.string' => 'El feedback del administrador debe ser texto',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $bid = LawyerBid::with(['case', 'lawyer.lawyerProfile'])->findOrFail($bidId);

        try {
            $bid->update([
                'status' => 'under_review',
                'admin_score' => $request->admin_score,
                'admin_feedback' => $request->admin_feedback,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Licitación evaluada exitosamente',
                'data' => $bid->fresh(['case', 'lawyer.lawyerProfile', 'reviewer'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al evaluar licitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar abogado ganador al caso.
     */
    public function assignLawyer(Request $request, $caseId, $bidId): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);
        $winningBid = LawyerBid::with(['lawyer.lawyerProfile'])->findOrFail($bidId);

        // Validaciones
        if ($winningBid->case_id !== $case->id) {
            return response()->json([
                'message' => 'La licitación no pertenece a este caso'
            ], 400);
        }

        if (!in_array($case->status, ['receiving_bids', 'bids_closed', 'approved_for_bidding'])) {
            return response()->json([
                'message' => 'El caso no está en estado para asignar abogado. Estado actual: ' . $case->status
            ], 400);
        }

        try {
            DB::transaction(function () use ($case, $winningBid, $request) {
                // Actualizar caso con datos del abogado ganador
                $case->update([
                    'lawyer_id' => $winningBid->lawyer_id,
                    'status' => 'lawyer_assigned',
                    'funding_goal' => $winningBid->funding_goal_proposed,
                    'expected_return' => $winningBid->expected_return_percentage,
                    'success_rate' => $winningBid->success_probability,
                    'lawyer_evaluation_fee' => $winningBid->lawyer_evaluation_fee,
                    'lawyer_success_fee_percentage' => $winningBid->lawyer_success_fee_percentage,
                    'lawyer_fixed_fee' => $winningBid->lawyer_fixed_fee,
                ]);

                // Marcar licitación ganadora
                $winningBid->update([
                    'status' => 'accepted',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                // Rechazar otras licitaciones
                LawyerBid::where('case_id', $case->id)
                    ->where('id', '!=', $winningBid->id)
                    ->whereNotIn('status', ['rejected', 'withdrawn'])
                    ->update([
                        'status' => 'rejected',
                        'admin_feedback' => 'Otra propuesta fue seleccionada',
                        'reviewed_by' => $request->user()->id,
                        'reviewed_at' => now(),
                    ]);
            });

            // TODO: Notificar abogado ganador y perdedores
            // event(new LawyerAssigned($case, $winningBid));

            return response()->json([
                'message' => 'Abogado asignado exitosamente',
                'data' => $case->fresh(['lawyer.lawyerProfile', 'winningBid', 'victim'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar abogado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publicar caso para inversores.
     */
    public function publishForInvestors(Request $request, $caseId): JsonResponse
    {
        $case = CaseModel::with(['lawyer.lawyerProfile'])->findOrFail($caseId);

        if ($case->status !== 'lawyer_assigned') {
            return response()->json([
                'message' => 'El caso debe tener un abogado asignado para publicarse. Estado actual: ' . $case->status
            ], 400);
        }

        if (!$case->lawyer_id) {
            return response()->json([
                'message' => 'El caso no tiene abogado asignado'
            ], 400);
        }

        try {
            $case->update([
                'status' => 'published',
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);

            // TODO: Notificar inversores
            // event(new CasePublishedForInvestors($case));

            return response()->json([
                'message' => 'Caso publicado para inversores exitosamente',
                'data' => $case->fresh(['lawyer.lawyerProfile', 'victim'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al publicar caso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle visibilidad en marketplace público.
     */
    public function togglePublicMarketplace(Request $request, $caseId): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);

        // Solo permitir toggle si el caso está en estados de licitación
        if (!in_array($case->status, ['approved_for_bidding', 'receiving_bids'])) {
            return response()->json([
                'message' => 'Solo casos en licitación pueden cambiar visibilidad pública'
            ], 400);
        }

        try {
            $case->update([
                'is_public_marketplace' => !$case->is_public_marketplace
            ]);

            $status = $case->is_public_marketplace ? 'visible' : 'oculto';

            return response()->json([
                'message' => "Caso ahora es {$status} en marketplace público",
                'is_public' => $case->is_public_marketplace,
                'data' => $case
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar visibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las licitaciones con filtros opcionales.
     */
    public function getAllBids(Request $request): JsonResponse
    {
        $query = LawyerBid::with([
            'lawyer.lawyerProfile',
            'case:id,title,status,funding_goal,current_funding',
            'reviewer:id,name,email'
        ]);

        // Filtrar por caso específico
        if ($request->has('case_id')) {
            $query->where('case_id', $request->case_id);
        }

        // Filtrar por abogado específico
        if ($request->has('lawyer_id')) {
            $query->where('lawyer_id', $request->lawyer_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        // Paginación
        $perPage = $request->get('per_page', 20);
        $bids = $query->paginate($perPage);

        return response()->json([
            'data' => $bids->items(),
            'meta' => [
                'current_page' => $bids->currentPage(),
                'last_page' => $bids->lastPage(),
                'per_page' => $bids->perPage(),
                'total' => $bids->total(),
            ]
        ]);
    }

    /**
     * Obtener estadísticas generales de licitaciones.
     */
    public function getBidStatistics(Request $request): JsonResponse
    {
        $totalBids = LawyerBid::count();
        $submittedBids = LawyerBid::where('status', 'submitted')->count();
        $underReviewBids = LawyerBid::where('status', 'under_review')->count();
        $acceptedBids = LawyerBid::where('status', 'accepted')->count();
        $rejectedBids = LawyerBid::where('status', 'rejected')->count();
        $withdrawnBids = LawyerBid::where('status', 'withdrawn')->count();

        // Calcular tasa de aceptación
        $acceptanceRate = $totalBids > 0
            ? round(($acceptedBids / $totalBids) * 100, 2)
            : 0;

        // Calcular puntuación promedio de admin
        $averageAdminScore = LawyerBid::whereNotNull('admin_score')
            ->avg('admin_score');
        $averageAdminScore = $averageAdminScore ? round($averageAdminScore, 2) : null;

        return response()->json([
            'data' => [
                'total_bids' => $totalBids,
                'submitted_bids' => $submittedBids,
                'under_review_bids' => $underReviewBids,
                'accepted_bids' => $acceptedBids,
                'rejected_bids' => $rejectedBids,
                'withdrawn_bids' => $withdrawnBids,
                'acceptance_rate' => $acceptanceRate,
                'average_admin_score' => $averageAdminScore,
            ]
        ]);
    }
}
