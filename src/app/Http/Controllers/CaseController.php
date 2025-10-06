<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseDocument;
use App\Models\CaseUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller
{
    /**
     * Get all cases (with filters).
     */
    public function index(Request $request): JsonResponse
    {
        $query = CaseModel::with(['victim', 'lawyer', 'documents', 'investments']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('victim_id')) {
            $query->where('victim_id', $request->victim_id);
        }

        if ($request->has('lawyer_id')) {
            $query->where('lawyer_id', $request->lawyer_id);
        }

        // For investors - only show published cases
        if ($request->user()->isInvestor()) {
            $query->where('status', 'published');
        }

        // For lawyers - show cases assigned to them or pending review
        if ($request->user()->isLawyer()) {
            $query->where(function($q) use ($request) {
                $q->where('lawyer_id', $request->user()->id)
                  ->orWhereIn('status', ['submitted', 'under_review']);
            });
        }

        // For victims - only show their own cases
        if ($request->user()->isVictim()) {
            $query->where('victim_id', $request->user()->id);
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($cases);
    }

    /**
     * Store a new case.
     */
    public function store(Request $request): JsonResponse
    {
        // Only victims can create cases
        if (!$request->user()->isVictim()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'company' => 'required|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,mp4,mp3|max:10240', // 10MB max
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
                'status' => 'submitted',
            ]);

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('case_documents', 'public');
                    
                    CaseDocument::create([
                        'case_id' => $case->id,
                        'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'document_type' => 'evidence',
                    ]);
                }
            }

            // Create initial case update
            CaseUpdate::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'title' => 'Caso creado',
                'description' => 'El caso ha sido enviado para revisión legal.',
                'type' => 'status_change',
                'new_status' => 'submitted',
            ]);

            return response()->json([
                'message' => 'Case created successfully',
                'case' => $case->load(['victim', 'documents', 'updates'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Case creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific case.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $case = CaseModel::with([
            'victim', 
            'lawyer', 
            'documents', 
            'investments.investor', 
            'updates.user'
        ])->findOrFail($id);

        // Check permissions
        $user = $request->user();
        if ($user->isVictim() && $case->victim_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['case' => $case]);
    }

    /**
     * Update case (for lawyers).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        // Only lawyers can update cases
        if (!$request->user()->isLawyer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:under_review,approved,published,rejected',
            'legal_analysis' => 'sometimes|string',
            'funding_goal' => 'sometimes|numeric|min:0',
            'success_rate' => 'sometimes|integer|min:0|max:100',
            'expected_return' => 'sometimes|numeric|min:0',
            'deadline' => 'sometimes|date|after:today',
            'evaluation_data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldStatus = $case->status;
            $updateData = $request->only([
                'status', 'legal_analysis', 'funding_goal', 
                'success_rate', 'expected_return', 'deadline', 'evaluation_data'
            ]);

            // Assign lawyer if not already assigned
            if (!$case->lawyer_id) {
                $updateData['lawyer_id'] = $request->user()->id;
            }

            $case->update($updateData);

            // Create case update if status changed
            if (isset($updateData['status']) && $oldStatus !== $updateData['status']) {
                CaseUpdate::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'title' => 'Estado del caso actualizado',
                    'description' => "El caso ha cambiado de '{$oldStatus}' a '{$updateData['status']}'.",
                    'type' => 'status_change',
                    'previous_status' => $oldStatus,
                    'new_status' => $updateData['status'],
                    'notify_victim' => true,
                    'notify_investors' => in_array($updateData['status'], ['published', 'funded']),
                ]);
            }

            return response()->json([
                'message' => 'Case updated successfully',
                'case' => $case->fresh()->load(['victim', 'lawyer', 'documents', 'updates'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Case update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cases for lawyer review.
     */
    public function pendingReview(Request $request): JsonResponse
    {
        if (!$request->user()->isLawyer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cases = CaseModel::with(['victim', 'documents'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return response()->json($cases);
    }

    /**
     * Get published cases for investors.
     */
    public function published(Request $request): JsonResponse
    {
        $query = CaseModel::with(['victim', 'lawyer', 'investments'])
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

        $cases = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json($cases);
    }

    /**
     * Add document to case.
     */
    public function addDocument(Request $request, $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        // Check permissions
        $user = $request->user();
        if ($user->isVictim() && $case->victim_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isLawyer() && $case->lawyer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,mp4,mp3|max:10240',
            'document_type' => 'required|in:evidence,contract,correspondence,receipt,photo,video,audio,other',
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
            $path = $file->store('case_documents', 'public');
            
            $document = CaseDocument::create([
                'case_id' => $case->id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'document_type' => $request->document_type,
                'description' => $request->description,
            ]);

            // Create case update
            CaseUpdate::create([
                'case_id' => $case->id,
                'user_id' => $user->id,
                'title' => 'Documento agregado',
                'description' => "Se ha agregado un nuevo documento: {$document->original_name}",
                'type' => 'document_added',
                'metadata' => ['document_id' => $document->id],
            ]);

            return response()->json([
                'message' => 'Document added successfully',
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Document upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}