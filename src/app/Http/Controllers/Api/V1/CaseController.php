<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CaseDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CaseController extends Controller
{
    /**
     * Display a listing of cases.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CaseModel::with(['victim', 'lawyer', 'documents', 'investments']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('company', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Role-based filtering
        $user = $request->user();
        if ($user->role === 'victim') {
            $query->where('victim_id', $user->id);
        } elseif ($user->role === 'lawyer') {
            $query->where('lawyer_id', $user->id);
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
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
     * Store a newly created case.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'company' => 'nullable|string',
            'funding_goal' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $case = CaseModel::create([
                'title' => $request->title,
                'description' => $request->description,
                'victim_id' => $request->user()->id,
                'category' => $request->category,
                'company' => $request->company,
                'funding_goal' => $request->funding_goal ?? 0,
                'status' => 'submitted',  // Initial status when victim submits case
            ]);

            return response()->json([
                'message' => 'Case created successfully',
                'data' => $case->load(['victim', 'documents']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create case',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified case.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $case = CaseModel::with([
            'victim',
            'lawyer.lawyerProfile',
            'documents',
            'investments.investor',
            'updates.user'
        ])->findOrFail($id);

        // Authorization check
        $user = $request->user();
        if ($case->status === 'submitted' && $case->victim_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $case,
        ]);
    }

    /**
     * Update the specified case.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        // Authorization
        $user = $request->user();
        if ($case->victim_id !== $user->id && $case->lawyer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string',
            'company' => 'sometimes|string',
            'status' => 'sometimes|in:submitted,under_review,approved,published,funded,in_progress,completed,rejected',
            'funding_goal' => 'sometimes|numeric|min:0',
            'success_rate' => 'sometimes|numeric|min:0|max:100',
            'expected_return' => 'sometimes|numeric|min:0|max:100',
            'deadline' => 'sometimes|date',
            'legal_analysis' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $case->update($request->only([
                'title',
                'description',
                'category',
                'company',
                'status',
                'funding_goal',
                'success_rate',
                'expected_return',
                'deadline',
                'legal_analysis'
            ]));

            return response()->json([
                'message' => 'Case updated successfully',
                'data' => $case->fresh()->load(['victim', 'lawyer', 'documents']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update case',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified case.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        // Only victim or admin can delete
        $user = $request->user();
        if ($case->victim_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can't delete if has investments
        if ($case->investments()->confirmed()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete case with confirmed investments'
            ], 400);
        }

        try {
            // Delete associated documents from storage
            foreach ($case->documents as $document) {
                Storage::disk('public')->delete($document->file_path);
            }

            $case->delete();

            return response()->json([
                'message' => 'Case deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete case',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cases pending review (for lawyers/admin).
     */
    public function pendingReview(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['lawyer', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cases = CaseModel::with(['victim', 'documents'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return response()->json([
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
     * Get published cases (investment opportunities).
     */
    public function published(Request $request): JsonResponse
    {
        $query = CaseModel::with(['victim', 'lawyer.lawyerProfile'])
            ->where('status', 'published')
            ->needsFunding();

        // Filters
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('min_return')) {
            $query->where('expected_return', '>=', $request->min_return);
        }

        if ($request->has('min_success_rate')) {
            $query->where('success_rate', '>=', $request->min_success_rate);
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
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
     * Add document to case.
     */
    public function addDocument(Request $request, $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        // Authorization
        $user = $request->user();
        if ($case->victim_id !== $user->id && $case->lawyer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'document_type' => 'required|in:contrato,liquidacion,carta_despido,certificado_medico,prueba,otro',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $filePath = $file->store('documents', 'public');

            $document = CaseDocument::create([
                'case_id' => $case->id,
                'name' => pathinfo($originalName, PATHINFO_FILENAME),
                'original_name' => $originalName,
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'document_type' => $request->document_type,
                'description' => $request->description,
            ]);

            return response()->json([
                'message' => 'Document uploaded successfully',
                'data' => $document,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign lawyer to case (lawyer takes the case).
     */
    public function assignLawyer(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only lawyers can assign themselves
        if (!$user->isLawyer()) {
            return response()->json(['message' => 'Only lawyers can take cases'], 403);
        }

        $case = CaseModel::findOrFail($id);

        // Case must be submitted or under_review
        if (!in_array($case->status, ['submitted', 'under_review'])) {
            return response()->json([
                'message' => 'Case is not available for assignment'
            ], 400);
        }

        // Check if already has a lawyer
        if ($case->lawyer_id && $case->lawyer_id !== $user->id) {
            return response()->json([
                'message' => 'Case already has an assigned lawyer'
            ], 400);
        }

        try {
            $case->update([
                'lawyer_id' => $user->id,
                'status' => 'under_review',
            ]);

            return response()->json([
                'message' => 'Lawyer assigned successfully',
                'data' => $case->fresh()->load(['victim', 'lawyer', 'documents']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign lawyer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Evaluate case (lawyer approves/rejects with details).
     */
    public function evaluate(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only lawyers and admins can evaluate
        if (!in_array($user->role, ['lawyer', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $case = CaseModel::findOrFail($id);

        // Check if lawyer is assigned to this case (or is admin)
        if ($user->role === 'lawyer' && $case->lawyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not assigned to this case'
            ], 403);
        }

        // Case must be under_review
        if ($case->status !== 'under_review') {
            return response()->json([
                'message' => 'Case must be under review to evaluate'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'legal_analysis' => 'required_if:action,approve|string',
            'success_rate' => 'required_if:action,approve|numeric|min:0|max:100',
            'funding_goal' => 'required_if:action,approve|numeric|min:0',
            'expected_return' => 'required_if:action,approve|numeric|min:0|max:100',
            'deadline' => 'required_if:action,approve|date|after:today',
            'rejection_reason' => 'required_if:action,reject|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->action === 'approve') {
                $case->update([
                    'status' => 'approved',
                    'legal_analysis' => $request->legal_analysis,
                    'success_rate' => $request->success_rate,
                    'funding_goal' => $request->funding_goal,
                    'expected_return' => $request->expected_return,
                    'deadline' => $request->deadline,
                    'evaluation_data' => array_merge($case->evaluation_data ?? [], [
                        'evaluated_at' => now()->toDateTimeString(),
                        'evaluated_by' => $user->id,
                        'action' => 'approved',
                    ]),
                ]);

                return response()->json([
                    'message' => 'Case approved successfully',
                    'data' => $case->fresh()->load(['victim', 'lawyer', 'documents']),
                ]);

            } else {
                $case->update([
                    'status' => 'rejected',
                    'evaluation_data' => array_merge($case->evaluation_data ?? [], [
                        'evaluated_at' => now()->toDateTimeString(),
                        'evaluated_by' => $user->id,
                        'action' => 'rejected',
                        'rejection_reason' => $request->rejection_reason,
                    ]),
                ]);

                return response()->json([
                    'message' => 'Case rejected',
                    'data' => $case->fresh()->load(['victim', 'lawyer', 'documents']),
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to evaluate case',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish approved case (make it visible to investors).
     */
    public function publish(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only lawyers and admins can publish
        if (!in_array($user->role, ['lawyer', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $case = CaseModel::findOrFail($id);

        // Check if lawyer is assigned to this case (or is admin)
        if ($user->role === 'lawyer' && $case->lawyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not assigned to this case'
            ], 403);
        }

        // Case must be approved
        if ($case->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved cases can be published'
            ], 400);
        }

        try {
            $case->update(['status' => 'published']);

            return response()->json([
                'message' => 'Case published successfully',
                'data' => $case->fresh()->load(['victim', 'lawyer', 'documents']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to publish case',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a funded case (change to in_progress).
     */
    public function start(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only lawyers and admins can start cases
        if (!in_array($user->role, ['lawyer', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $case = CaseModel::findOrFail($id);

        // Check if lawyer is assigned to this case (or is admin)
        if ($user->role === 'lawyer' && $case->lawyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not assigned to this case'
            ], 403);
        }

        try {
            $startCaseAction = app(\App\Actions\Cases\StartCaseAction::class);
            $case = $startCaseAction->execute($case);

            return response()->json([
                'message' => 'Case started successfully',
                'data' => $case->fresh()->load(['victim', 'lawyer', 'investments'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start case',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Close a case with outcome.
     */
    public function close(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only lawyers and admins can close cases
        if (!in_array($user->role, ['lawyer', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $case = CaseModel::findOrFail($id);

        // Check if lawyer is assigned to this case (or is admin)
        if ($user->role === 'lawyer' && $case->lawyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not assigned to this case'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'outcome' => 'required|in:won,lost,settled,dismissed',
            'amount_recovered' => 'nullable|numeric|min:0',
            'legal_costs' => 'nullable|numeric|min:0',
            'outcome_description' => 'nullable|string',
            'resolution_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $closeCaseAction = app(\App\Actions\Cases\CloseCaseAction::class);
            $case = $closeCaseAction->execute(
                $case,
                $request->outcome,
                $request->amount_recovered,
                $request->legal_costs,
                $request->outcome_description,
                $request->resolution_date
            );

            return response()->json([
                'message' => 'Case closed successfully',
                'data' => $case->fresh()->load(['victim', 'lawyer', 'investments'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to close case',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Distribute returns to investors after a case is won.
     */
    public function distributeReturns(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Only admins can distribute returns
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized - Only admins can distribute returns'], 403);
        }

        $case = CaseModel::findOrFail($id);

        try {
            $distributeReturnsAction = app(\App\Actions\Cases\DistributeReturnsAction::class);
            $results = $distributeReturnsAction->execute($case);

            return response()->json([
                'message' => 'Returns distributed successfully',
                'data' => [
                    'case_id' => $case->id,
                    'lawyer_payment' => $results['lawyer_payment'],
                    'investor_returns_count' => count($results['investor_returns']),
                    'investor_returns' => $results['investor_returns'],
                    'total_platform_commission' => $results['platform_commission'],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to distribute returns',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
