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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
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
                  ->orderByRaw("FIELD(status, 'submitted', 'under_review', 'accepted', 'rejected', 'withdrawn')")
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

        if ($case->status !== 'receiving_bids') {
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
     * Evaluar una licitación específica.
     */
    public function reviewBid(Request $request, $bidId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_score' => 'required|integer|min:1|max:10',
            'admin_feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
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
}
