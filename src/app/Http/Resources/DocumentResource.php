<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\DocumentType;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = DocumentType::from($this->document_type);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'human_file_size' => $this->human_file_size,
            'mime_type' => $this->mime_type,
            'document_type' => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            'description' => $this->description,
            'file_url' => $this->file_url,
            'created_at' => $this->created_at,
        ];
    }
}