<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\InvestmentStatus;

class InvestmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = InvestmentStatus::from($this->status);

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'investor' => [
                'id' => $this->investor->id,
                'name' => $this->investor->name,
                'email' => $this->investor->email,
            ],
            'case' => [
                'id' => $this->case->id,
                'title' => $this->case->title,
            ],
            'expected_return' => [
                'percentage' => $this->expected_return_percentage,
                'amount' => $this->expected_return_amount,
            ],
            'actual_return' => $this->actual_return ? [
                'amount' => $this->actual_return,
                'percentage' => $this->actual_return_percentage,
            ] : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
