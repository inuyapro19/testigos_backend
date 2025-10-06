<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\UserRole;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = UserRole::from($this->role);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rut' => $this->rut,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'phone' => $this->phone,
            'role' => [
                'value' => $role->value,
                'label' => $role->label(),
                'permissions' => $role->permissions(),
            ],
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'lawyer_profile' => new LawyerProfileResource($this->whenLoaded('lawyerProfile')),
            'investor_profile' => new InvestorProfileResource($this->whenLoaded('investorProfile')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}