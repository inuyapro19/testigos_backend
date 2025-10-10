<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvestmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $investors = User::where('role', 'investor')->get();

        // Investments for Case 3 (PUBLISHED - Consalud) - Partially funded
        // current_funding = 3,500,000
        DB::table('investments')->insert([
            'case_id' => 3,
            'investor_id' => $investors[0]->id,
            'amount' => 2000000,
            'expected_return_percentage' => 18.5,
            'expected_return_amount' => 370000,
            'status' => 'confirmed',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '1234']),
            'confirmed_at' => now()->subDays(8),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 150000,
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        DB::table('investments')->insert([
            'case_id' => 3,
            'investor_id' => $investors[1]->id,
            'amount' => 1500000,
            'expected_return_percentage' => 18.5,
            'expected_return_amount' => 277500,
            'status' => 'confirmed',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '5678']),
            'confirmed_at' => now()->subDays(6),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 112500,
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        // Investments for Case 4 (PUBLISHED - Falabella) - Partially funded
        // current_funding = 1,200,000
        DB::table('investments')->insert([
            'case_id' => 4,
            'investor_id' => $investors[2]->id,
            'amount' => 1200000,
            'expected_return_percentage' => 22.0,
            'expected_return_amount' => 264000,
            'status' => 'confirmed',
            'payment_data' => json_encode(['method' => 'mercadopago', 'reference' => 'MP-123456']),
            'confirmed_at' => now()->subDays(2),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 90000,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Investments for Case 5 (FUNDED - Constructora ABC) - Fully funded
        // current_funding = 12,000,000
        DB::table('investments')->insert([
            'case_id' => 5,
            'investor_id' => $investors[0]->id,
            'amount' => 5000000,
            'expected_return_percentage' => 15.0,
            'expected_return_amount' => 750000,
            'status' => 'active',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '1234']),
            'confirmed_at' => now()->subDays(14),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 375000,
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('investments')->insert([
            'case_id' => 5,
            'investor_id' => $investors[1]->id,
            'amount' => 4000000,
            'expected_return_percentage' => 15.0,
            'expected_return_amount' => 600000,
            'status' => 'active',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '5678']),
            'confirmed_at' => now()->subDays(13),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 300000,
            'created_at' => now()->subDays(13),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('investments')->insert([
            'case_id' => 5,
            'investor_id' => $investors[2]->id,
            'amount' => 3000000,
            'expected_return_percentage' => 15.0,
            'expected_return_amount' => 450000,
            'status' => 'active',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '9012']),
            'confirmed_at' => now()->subDays(12),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 225000,
            'created_at' => now()->subDays(12),
            'updated_at' => now()->subDay(),
        ]);

        // Investments for Case 6 (IN_PROGRESS - Inmobiliaria) - Fully funded, active
        // current_funding = 7,000,000
        DB::table('investments')->insert([
            'case_id' => 6,
            'investor_id' => $investors[0]->id,
            'amount' => 3500000,
            'expected_return_percentage' => 20.0,
            'expected_return_amount' => 700000,
            'status' => 'active',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '1234']),
            'confirmed_at' => now()->subDays(28),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 262500,
            'created_at' => now()->subDays(28),
            'updated_at' => now()->subDays(4),
        ]);

        DB::table('investments')->insert([
            'case_id' => 6,
            'investor_id' => $investors[1]->id,
            'amount' => 3500000,
            'expected_return_percentage' => 20.0,
            'expected_return_amount' => 700000,
            'status' => 'active',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '5678']),
            'confirmed_at' => now()->subDays(27),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 262500,
            'created_at' => now()->subDays(27),
            'updated_at' => now()->subDays(4),
        ]);

        // Investments for Case 7 (COMPLETED - Cencosud) - Case won, returns paid
        // current_funding = 4,500,000, amount_recovered = 8,500,000
        DB::table('investments')->insert([
            'case_id' => 7,
            'investor_id' => $investors[0]->id,
            'amount' => 2500000,
            'expected_return_percentage' => 25.0,
            'expected_return_amount' => 625000,
            'status' => 'completed',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '1234']),
            'confirmed_at' => now()->subMonths(5),
            'completed_at' => now()->subDays(2),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 187500,
            'success_commission_percentage' => 5.0,
            'success_commission_amount' => 106250, // 5% of (2500000 + 625000)
            'actual_return' => 1180555, // Proportional share of recovered amount
            'actual_return_percentage' => -52.78, // ((1180555 - 2500000) / 2500000) * 100
            'notes' => 'Caso ganado. Retorno calculado proporcionalmente según monto recuperado.',
            'created_at' => now()->subMonths(5),
            'updated_at' => now()->subDays(2),
        ]);

        DB::table('investments')->insert([
            'case_id' => 7,
            'investor_id' => $investors[2]->id,
            'amount' => 2000000,
            'expected_return_percentage' => 25.0,
            'expected_return_amount' => 500000,
            'status' => 'completed',
            'payment_data' => json_encode(['method' => 'transbank', 'card_last4' => '9012']),
            'confirmed_at' => now()->subMonths(5),
            'completed_at' => now()->subDays(2),
            'platform_commission_percentage' => 7.5,
            'platform_commission_amount' => 150000,
            'success_commission_percentage' => 5.0,
            'success_commission_amount' => 85000, // 5% of (2000000 + 500000)
            'actual_return' => 944444, // Proportional share of recovered amount
            'actual_return_percentage' => -52.78, // ((944444 - 2000000) / 2000000) * 100
            'notes' => 'Caso ganado. Retorno calculado proporcionalmente según monto recuperado.',
            'created_at' => now()->subMonths(5),
            'updated_at' => now()->subDays(2),
        ]);
    }
}
