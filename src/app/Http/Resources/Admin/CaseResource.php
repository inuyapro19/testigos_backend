<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\CaseStatus;

class CaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = CaseStatus::from($this->status);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'victim' => [
                'id' => $this->victim->id,
                'name' => $this->victim->name,
                'email' => $this->victim->email,
            ],
            'lawyer' => $this->lawyer ? [
                'id' => $this->lawyer->id,
                'name' => $this->lawyer->name,
                'email' => $this->lawyer->email,
            ] : null,
            'funding_goal' => $this->funding_goal,
            'current_funding' => $this->current_funding,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
