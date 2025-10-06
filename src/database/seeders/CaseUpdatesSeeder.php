<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaseUpdatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lawyer1 = User::where('email', 'maria.fernandez@testigo.cl')->first();
        $lawyer2 = User::where('email', 'carlos.rojas@testigo.cl')->first();

        // Updates for Case 3 (PUBLISHED)
        DB::table('case_updates')->insert([
            'case_id' => 3,
            'user_id' => $lawyer2->id,
            'title' => 'Caso aprobado y publicado',
            'description' => 'El caso ha sido evaluado positivamente. Se requiere financiamiento de $8,000,000 CLP para proceder.',
            'type' => 'status_change',
            'previous_status' => 'approved',
            'new_status' => 'published',
            'notify_victim' => true,
            'notify_investors' => true,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        // Updates for Case 6 (IN_PROGRESS)
        DB::table('case_updates')->insert([
            'case_id' => 6,
            'user_id' => $lawyer1->id,
            'title' => 'Demanda presentada ante tribunales',
            'description' => 'Se ha presentado formalmente la demanda ante el tribunal correspondiente. Esperamos notificación de la contraparte en las próximas semanas.',
            'type' => 'progress_update',
            'notify_victim' => true,
            'notify_investors' => true,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        // Updates for Case 7 (COMPLETED)
        DB::table('case_updates')->insert([
            'case_id' => 7,
            'user_id' => $lawyer2->id,
            'title' => 'Sentencia favorable - Caso ganado',
            'description' => 'El tribunal ha fallado a favor del consumidor. Se ha ordenado a Cencosud el pago de $8,500,000 CLP en compensación.',
            'type' => 'status_change',
            'previous_status' => 'in_progress',
            'new_status' => 'completed',
            'metadata' => json_encode([
                'outcome' => 'won',
                'amount_recovered' => 8500000,
                'sentence_number' => 'ROL-12345-2024',
            ]),
            'notify_victim' => true,
            'notify_investors' => true,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        DB::table('case_updates')->insert([
            'case_id' => 7,
            'user_id' => $lawyer2->id,
            'title' => 'Retornos distribuidos a inversionistas',
            'description' => 'Se han calculado y distribuido los retornos correspondientes a todos los inversionistas del caso.',
            'type' => 'financial_update',
            'notify_victim' => false,
            'notify_investors' => true,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }
}
