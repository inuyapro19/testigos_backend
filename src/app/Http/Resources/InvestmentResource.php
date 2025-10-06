<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\InvestmentStatus;

class InvestmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = InvestmentStatus::from($this->status);
        
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'expected_return' => [
                'percentage' => $this->expected_return_percentage,
                'amount' => $this->expected_return_amount,
                'total' => $this->expected_total_return,
            ],
            'actual_return' => [
                'amount' => $this->actual_return,
                'percentage' => $this->actual_return_percentage,
            ],
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'payment_data' => $this->payment_data,
            'notes' => $this->notes,
            'case' => new CaseResource($this->whenLoaded('case')),
            'investor' => new UserResource($this->whenLoaded('investor')),
            'confirmed_at' => $this->confirmed_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}