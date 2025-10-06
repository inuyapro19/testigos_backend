<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LawyerProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lawyers = User::where('role', 'lawyer')->get();

        foreach ($lawyers as $index => $lawyer) {
            DB::table('lawyer_profiles')->insert([
                'user_id' => $lawyer->id,
                'license_number' => 'LC-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'law_firm' => $index === 0 ? 'Fernández & Asociados' : 'Rojas Legal',
                'specializations' => json_encode($index === 0
                    ? ['Derecho del Consumidor', 'Derecho Civil', 'Litigios Empresariales']
                    : ['Derecho Laboral', 'Derecho Comercial', 'Protección al Consumidor']
                ),
                'years_experience' => $index === 0 ? 15 : 20,
                'bio' => $index === 0
                    ? 'Abogada especializada en protección al consumidor con amplia experiencia en litigios contra grandes empresas.'
                    : 'Abogado con más de 20 años de experiencia en derecho comercial y protección del consumidor.',
                'success_rate' => $index === 0 ? 78.5 : 82.3,
                'cases_handled' => $index === 0 ? 45 : 67,
                'total_recovered' => $index === 0 ? 125000000 : 189000000, // CLP
                'is_verified' => true,
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
