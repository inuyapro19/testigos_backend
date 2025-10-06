<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Transaction for Investment 1 (Case 3, Investment 1)
        DB::table('transactions')->insert([
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'type' => 'investment',
            'case_id' => 3,
            'investment_id' => 1,
            'user_id' => 4, // Patricia González (investor)
            'amount' => 2000000,
            'currency' => 'CLP',
            'direction' => 'in',
            'status' => 'completed',
            'payment_gateway' => 'transbank',
            'gateway_transaction_id' => 'TBK-' . rand(100000, 999999),
            'gateway_fee' => 60000, // 3% gateway fee
            'description' => 'Inversión en caso Consalud - Isapre',
            'processed_at' => now()->subDays(8),
            'completed_at' => now()->subDays(8),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        // Platform commission transaction for Investment 1
        DB::table('transactions')->insert([
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'type' => 'platform_commission',
            'case_id' => 3,
            'investment_id' => 1,
            'user_id' => null,
            'amount' => 150000, // 7.5% of 2,000,000
            'currency' => 'CLP',
            'direction' => 'in',
            'status' => 'completed',
            'description' => 'Comisión de plataforma (7.5%) - Inversión caso Consalud',
            'completed_at' => now()->subDays(8),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        // Transaction for completed case (Case 7) - Investor return
        DB::table('transactions')->insert([
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'type' => 'investor_return',
            'case_id' => 7,
            'investment_id' => 9, // First investment in completed case
            'user_id' => 4, // Patricia González
            'amount' => 1180555,
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'completed',
            'description' => 'Retorno de inversión - Caso Cencosud ganado',
            'completed_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Success commission transaction for completed case
        DB::table('transactions')->insert([
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'type' => 'success_commission',
            'case_id' => 7,
            'investment_id' => 9,
            'user_id' => null,
            'amount' => 106250, // 5% success commission
            'currency' => 'CLP',
            'direction' => 'in',
            'status' => 'completed',
            'description' => 'Comisión de éxito (5%) - Caso Cencosud ganado',
            'completed_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Lawyer payment transaction for completed case
        DB::table('transactions')->insert([
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'type' => 'lawyer_payment',
            'case_id' => 7,
            'investment_id' => null,
            'user_id' => 3, // Carlos Rojas (lawyer)
            'amount' => 2110000, // Total lawyer compensation
            'currency' => 'CLP',
            'direction' => 'out',
            'status' => 'completed',
            'description' => 'Pago a abogado - Caso Cencosud completado',
            'completed_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
    }
}
