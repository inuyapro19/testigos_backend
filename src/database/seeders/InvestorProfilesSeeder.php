<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvestorProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $investors = User::where('role', 'investor')->get();

        foreach ($investors as $index => $investor) {
            $investorTypes = ['individual', 'accredited', 'institutional'];
            $type = $investorTypes[$index % count($investorTypes)];

            DB::table('investor_profiles')->insert([
                'user_id' => $investor->id,
                'investor_type' => $type,
                'total_invested' => rand(5000000, 50000000), // CLP
                'total_returns' => rand(500000, 10000000),
                'active_investments' => rand(1, 5),
                'completed_investments' => rand(0, 10),
                'average_return_rate' => rand(5, 25) + (rand(0, 99) / 100),
                'investment_preferences' => json_encode([
                    'min_success_rate' => rand(60, 80),
                    'preferred_categories' => ['Servicios Financieros', 'Telecomunicaciones', 'Retail'],
                    'risk_tolerance' => ['low', 'medium', 'high'][rand(0, 2)],
                ]),
                'minimum_investment' => rand(500000, 2000000),
                'maximum_investment' => rand(10000000, 50000000),
                'is_accredited' => $type === 'accredited' || $type === 'institutional',
                'accredited_at' => ($type === 'accredited' || $type === 'institutional') ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
