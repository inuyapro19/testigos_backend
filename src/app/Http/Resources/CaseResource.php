<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\CaseStatus;

class CaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = CaseStatus::from($this->status);
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'category' => $this->category,
            'company' => $this->company,
            'funding' => [
                'goal' => $this->funding_goal,
                'current' => $this->current_funding,
                'percentage' => $this->funding_percentage,
                'remaining' => $this->remaining_funding,
            ],
            'success_rate' => $this->success_rate,
            'expected_return' => $this->expected_return,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'legal_analysis' => $this->legal_analysis,
            'evaluation_data' => $this->evaluation_data,
            'victim' => new UserResource($this->whenLoaded('victim')),
            'lawyer' => new UserResource($this->whenLoaded('lawyer')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'investments' => InvestmentResource::collection($this->whenLoaded('investments')),
            'updates' => CaseUpdateResource::collection($this->whenLoaded('updates')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}