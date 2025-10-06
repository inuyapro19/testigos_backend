<?php

namespace App\Actions\Users;

use App\Data\UserData;
use App\Models\User;
use App\Models\LawyerProfile;
use App\Models\InvestorProfile;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(UserData $data, array $profileData = []): User
    {
        return DB::transaction(function () use ($data, $profileData) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'rut' => $data->rut,
                'birth_date' => $data->birth_date,
                'address' => $data->address,
                'phone' => $data->phone,
                'role' => $data->role->value,
                'avatar' => $data->avatar,
                'is_active' => $data->is_active,
            ]);

            // Crear perfil específico según el rol
            if ($data->role->value === 'lawyer' && !empty($profileData)) {
                LawyerProfile::create([
                    'user_id' => $user->id,
                    'license_number' => $profileData['license_number'],
                    'law_firm' => $profileData['law_firm'] ?? null,
                    'specializations' => $profileData['specializations'] ?? [],
                    'years_experience' => $profileData['years_experience'] ?? 0,
                    'bio' => $profileData['bio'] ?? null,
                ]);
            } elseif ($data->role->value === 'investor' && !empty($profileData)) {
                InvestorProfile::create([
                    'user_id' => $user->id,
                    'investor_type' => $profileData['investor_type'] ?? 'individual',
                    'minimum_investment' => $profileData['minimum_investment'] ?? 1000000,
                    'maximum_investment' => $profileData['maximum_investment'] ?? null,
                    'investment_preferences' => $profileData['investment_preferences'] ?? [],
                ]);
            }

            event(new UserRegistered($user));

            return $user->fresh();
        });
    }
}