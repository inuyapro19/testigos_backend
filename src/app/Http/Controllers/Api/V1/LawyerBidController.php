<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\LawyerBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LawyerBidController extends Controller
{
    /**
     * Enviar licitación a un caso.
     */
    public function submitBid(Request $request, $caseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'funding_goal_proposed' => 'required|numeric|min:100000',
            'expected_return_percentage' => 'required|numeric|min:0|max:100',
            'lawyer_evaluation_fee' => 'nullable|numeric|min:0',
            'lawyer_success_fee_percentage' => 'nullable|numeric|min:0|max:50',
            'lawyer_fixed_fee' => 'nullable|numeric|min:0',
            'success_probability' => 'required|numeric|min:0|max:100',
            'estimated_duration_months' => 'required|integer|min:1|max:60',
            'legal_strategy' => 'required|string|min:200',
            'experience_summary' => 'required|string|min:100',
            'why_best_candidate' => 'required|string|min:100',
            'similar_cases_won' => 'nullable|integer|min:0',
            'similar_cases_description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que el caso esté abierto
        $case = CaseModel::openForBidding()->findOrFail($caseId);

        // Validar que el abogado no haya licitado antes
        $existingBid = LawyerBid::where('case_id', $caseId)
            ->where('lawyer_id', $request->user()->id)
            ->exists();

        if ($existingBid) {
            return response()->json([
                'message' => 'Ya has enviado una licitación para este caso'
            ], 400);
        }

        try {
            $bid = LawyerBid::create([
                'case_id' => $caseId,
                'lawyer_id' => $request->user()->id,
                'funding_goal_proposed' => $request->funding_goal_proposed,
                'expected_return_percentage' => $request->expected_return_percentage,
                'lawyer_evaluation_fee' => $request->lawyer_evaluation_fee,
                'lawyer_success_fee_percentage' => $request->lawyer_success_fee_percentage,
                'lawyer_fixed_fee' => $request->lawyer_fixed_fee,
                'success_probability' => $request->success_probability,
                'estimated_duration_months' => $request->estimated_duration_months,
                'legal_strategy' => $request->legal_strategy,
                'experience_summary' => $request->experience_summary,
                'why_best_candidate' => $request->why_best_candidate,
                'similar_cases_won' => $request->similar_cases_won ?? 0,
                'similar_cases_description' => $request->similar_cases_description,
                'attachments' => $request->attachments,
                'status' => 'submitted',
            ]);

            // Actualizar estado del caso si es primera licitación
            if ($case->status === 'approved_for_bidding') {
                $case->update(['status' => 'receiving_bids']);
            }

            // TODO: Notificar admin
            // event(new BidSubmitted($bid));

            return response()->json([
                'message' => 'Licitación enviada exitosamente',
                'data' => $bid->load(['case:id,title,status', 'lawyer.lawyerProfile'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar licitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver mis licitaciones.
     */
    public function myBids(Request $request): JsonResponse
    {
        $query = LawyerBid::where('lawyer_id', $request->user()->id)
            ->with(['case.victim:id,name']);

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bids = $query->orderBy('created_at', 'desc')->paginate(15);

        // Estadísticas
        $stats = [
            'total_bids' => LawyerBid::where('lawyer_id', $request->user()->id)->count(),
            'submitted' => LawyerBid::where('lawyer_id', $request->user()->id)->where('status', 'submitted')->count(),
            'under_review' => LawyerBid::where('lawyer_id', $request->user()->id)->where('status', 'under_review')->count(),
            'accepted' => LawyerBid::where('lawyer_id', $request->user()->id)->where('status', 'accepted')->count(),
            'rejected' => LawyerBid::where('lawyer_id', $request->user()->id)->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'bids' => $bids->items(),
            'meta' => [
                'current_page' => $bids->currentPage(),
                'last_page' => $bids->lastPage(),
                'per_page' => $bids->perPage(),
                'total' => $bids->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Ver detalle de mi licitación.
     */
    public function showBid(Request $request, $bidId): JsonResponse
    {
        $bid = LawyerBid::where('id', $bidId)
            ->where('lawyer_id', $request->user()->id)
            ->with(['case.victim', 'reviewer:id,name,email'])
            ->firstOrFail();

        return response()->json(['data' => $bid]);
    }

    /**
     * Actualizar mi licitación (solo si está en draft o submitted).
     */
    public function updateBid(Request $request, $bidId): JsonResponse
    {
        $bid = LawyerBid::where('id', $bidId)
            ->where('lawyer_id', $request->user()->id)
            ->firstOrFail();

        if (!$bid->isEditable()) {
            return response()->json([
                'message' => 'No puedes editar esta licitación en su estado actual: ' . $bid->status
            ], 400);
        }

        // Validación similar a submitBid
        $validator = Validator::make($request->all(), [
            'funding_goal_proposed' => 'sometimes|numeric|min:100000',
            'expected_return_percentage' => 'sometimes|numeric|min:0|max:100',
            'lawyer_evaluation_fee' => 'nullable|numeric|min:0',
            'lawyer_success_fee_percentage' => 'nullable|numeric|min:0|max:50',
            'lawyer_fixed_fee' => 'nullable|numeric|min:0',
            'success_probability' => 'sometimes|numeric|min:0|max:100',
            'estimated_duration_months' => 'sometimes|integer|min:1|max:60',
            'legal_strategy' => 'sometimes|string|min:200',
            'experience_summary' => 'sometimes|string|min:100',
            'why_best_candidate' => 'sometimes|string|min:100',
            'similar_cases_won' => 'nullable|integer|min:0',
            'similar_cases_description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bid->update($request->only([
            'funding_goal_proposed', 'expected_return_percentage',
            'lawyer_evaluation_fee', 'lawyer_success_fee_percentage', 'lawyer_fixed_fee',
            'success_probability', 'estimated_duration_months',
            'legal_strategy', 'experience_summary', 'why_best_candidate',
            'similar_cases_won', 'similar_cases_description', 'attachments'
        ]));

        return response()->json([
            'message' => 'Licitación actualizada exitosamente',
            'data' => $bid->fresh()
        ]);
    }

    /**
     * Retirar licitación.
     */
    public function withdrawBid(Request $request, $bidId): JsonResponse
    {
        $bid = LawyerBid::where('id', $bidId)
            ->where('lawyer_id', $request->user()->id)
            ->firstOrFail();

        if (!$bid->canBeWithdrawn()) {
            return response()->json([
                'message' => 'No puedes retirar esta licitación en su estado actual: ' . $bid->status
            ], 400);
        }

        $bid->markAsWithdrawn();

        return response()->json([
            'message' => 'Licitación retirada exitosamente'
        ]);
    }
}
