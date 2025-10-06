<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseDocument;
use App\Enums\DocumentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function uploadDocument(CaseModel $case, UploadedFile $file, DocumentType $type, ?string $description = null): CaseDocument
    {
        // Validar tipo de archivo
        if (!in_array($file->getMimeType(), $type->allowedMimeTypes())) {
            throw new \InvalidArgumentException('File type not allowed for this document type');
        }

        // Generar nombre único
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = "case_documents/{$case->id}/{$fileName}";

        // Subir archivo
        $path = $file->storeAs('case_documents/' . $case->id, $fileName, 'public');

        // Crear registro en base de datos
        return CaseDocument::create([
            'case_id' => $case->id,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'document_type' => $type->value,
            'description' => $description,
        ]);
    }

    public function uploadDocuments(CaseModel $case, array $files): array
    {
        $documents = [];

        foreach ($files as $fileData) {
            $file = $fileData['file'];
            $type = DocumentType::from($fileData['type'] ?? 'evidence');
            $description = $fileData['description'] ?? null;

            $documents[] = $this->uploadDocument($case, $file, $type, $description);
        }

        return $documents;
    }

    public function deleteDocument(CaseDocument $document): bool
    {
        // Eliminar archivo físico
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Eliminar registro de base de datos
        return $document->delete();
    }

    public function getDocumentUrl(CaseDocument $document): string
    {
        return Storage::disk('public')->url($document->file_path);
    }

    public function validateFileSize(UploadedFile $file, int $maxSizeInMB = 10): bool
    {
        return $file->getSize() <= ($maxSizeInMB * 1024 * 1024);
    }

    public function getDocumentsByType(CaseModel $case, DocumentType $type): array
    {
        return $case->documents()
            ->where('document_type', $type->value)
            ->get()
            ->toArray();
    }
}