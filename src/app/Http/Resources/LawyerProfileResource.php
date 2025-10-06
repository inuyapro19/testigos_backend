<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LawyerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_number' => $this->license_number,
            'law_firm' => $this->law_firm,
            'specializations' => $this->specializations,
            'years_experience' => $this->years_experience,
            'bio' => $this->bio,
            'success_rate' => $this->success_rate,
            'cases_handled' => $this->cases_handled,
            'total_recovered' => $this->total_recovered,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}