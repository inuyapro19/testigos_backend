<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investor_type' => $this->investor_type,
            'total_invested' => $this->total_invested,
            'total_returns' => $this->total_returns,
            'net_profit' => $this->net_profit,
            'active_investments' => $this->active_investments,
            'completed_investments' => $this->completed_investments,
            'average_return_rate' => $this->average_return_rate,
            'investment_preferences' => $this->investment_preferences,
            'minimum_investment' => $this->minimum_investment,
            'maximum_investment' => $this->maximum_investment,
            'is_accredited' => $this->is_accredited,
            'accredited_at' => $this->accredited_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}