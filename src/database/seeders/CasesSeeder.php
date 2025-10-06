<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $victims = User::where('role', 'victim')->get();
        $lawyers = User::where('role', 'lawyer')->get();

        // Case 1: SUBMITTED (no lawyer assigned yet)
        DB::table('cases')->insert([
            'title' => 'Cobro indebido por servicio no contratado - Movistar',
            'description' => 'Me cobraron durante 8 meses un servicio que nunca contraté. A pesar de múltiples reclamos, Movistar se niega a devolver el dinero.',
            'victim_id' => $victims[0]->id,
            'lawyer_id' => null,
            'status' => 'submitted',
            'category' => 'Telecomunicaciones',
            'company' => 'Movistar Chile',
            'funding_goal' => 0,
            'current_funding' => 0,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Case 2: UNDER_REVIEW (lawyer assigned, evaluating)
        DB::table('cases')->insert([
            'title' => 'Cláusulas abusivas en contrato de crédito - Banco de Chile',
            'description' => 'El banco incluyó cláusulas abusivas en mi crédito hipotecario que me impiden prepagar sin penalización excesiva.',
            'victim_id' => $victims[1]->id,
            'lawyer_id' => $lawyers[0]->id,
            'status' => 'under_review',
            'category' => 'Servicios Financieros',
            'company' => 'Banco de Chile',
            'funding_goal' => 0,
            'current_funding' => 0,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDay(),
        ]);

        // Case 3: PUBLISHED (approved by lawyer, needs funding)
        DB::table('cases')->insert([
            'title' => 'Negativa de cobertura de seguro por enfermedad preexistente - Consalud',
            'description' => 'Mi isapre se niega a cubrir tratamiento médico alegando una enfermedad preexistente que nunca declaré porque la desconocía.',
            'victim_id' => $victims[2]->id,
            'lawyer_id' => $lawyers[1]->id,
            'status' => 'published',
            'category' => 'Salud',
            'company' => 'Consalud',
            'funding_goal' => 8000000,
            'current_funding' => 3500000,
            'success_rate' => 75.5,
            'expected_return' => 18.5,
            'deadline' => now()->addMonths(3)->format('Y-m-d'),
            'legal_analysis' => 'Caso sólido con alta probabilidad de éxito. La jurisprudencia reciente favorece al consumidor en casos similares.',
            'evaluation_data' => json_encode([
                'estimated_duration_months' => 8,
                'complexity' => 'medium',
                'precedents' => 'Favorable',
            ]),
            'lawyer_evaluation_fee' => 500000,
            'lawyer_success_fee_percentage' => 15,
            'lawyer_fixed_fee' => 2000000,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(3),
        ]);

        // Case 4: PUBLISHED (another case needing funding)
        DB::table('cases')->insert([
            'title' => 'Venta de producto defectuoso sin derecho a devolución - Falabella',
            'description' => 'Compré un televisor que resultó defectuoso. Falabella se niega a hacer válida la garantía legal y no acepta devolución.',
            'victim_id' => $victims[3]->id,
            'lawyer_id' => $lawyers[0]->id,
            'status' => 'published',
            'category' => 'Retail',
            'company' => 'Falabella',
            'funding_goal' => 5000000,
            'current_funding' => 1200000,
            'success_rate' => 85.0,
            'expected_return' => 22.0,
            'deadline' => now()->addMonths(2)->format('Y-m-d'),
            'legal_analysis' => 'Caso muy sólido con alta probabilidad de éxito. La ley del consumidor es clara en este tipo de casos.',
            'evaluation_data' => json_encode([
                'estimated_duration_months' => 6,
                'complexity' => 'low',
                'precedents' => 'Muy favorable',
            ]),
            'lawyer_evaluation_fee' => 400000,
            'lawyer_success_fee_percentage' => 12,
            'lawyer_fixed_fee' => 1500000,
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(2),
        ]);

        // Case 5: FUNDED (funding goal reached, ready to proceed)
        DB::table('cases')->insert([
            'title' => 'Despido injustificado - Empresa Constructora ABC',
            'description' => 'Fui despedido sin causa justificada después de reportar irregularidades en la obra.',
            'victim_id' => $victims[0]->id,
            'lawyer_id' => $lawyers[1]->id,
            'status' => 'funded',
            'category' => 'Laboral',
            'company' => 'Constructora ABC',
            'funding_goal' => 12000000,
            'current_funding' => 12000000,
            'success_rate' => 72.0,
            'expected_return' => 15.0,
            'deadline' => now()->addMonths(4)->format('Y-m-d'),
            'legal_analysis' => 'Caso con buenas posibilidades. Existen evidencias de represalia por denuncia de irregularidades.',
            'evaluation_data' => json_encode([
                'estimated_duration_months' => 10,
                'complexity' => 'medium',
                'precedents' => 'Favorable',
            ]),
            'lawyer_evaluation_fee' => 600000,
            'lawyer_success_fee_percentage' => 18,
            'lawyer_fixed_fee' => 2500000,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(1),
        ]);

        // Case 6: IN_PROGRESS (legal proceedings started)
        DB::table('cases')->insert([
            'title' => 'Incumplimiento de contrato de arriendo - Inmobiliaria Los Pinos',
            'description' => 'La inmobiliaria no realizó las reparaciones comprometidas y ahora quiere desalojarme.',
            'victim_id' => $victims[1]->id,
            'lawyer_id' => $lawyers[0]->id,
            'status' => 'in_progress',
            'category' => 'Inmobiliario',
            'company' => 'Inmobiliaria Los Pinos',
            'funding_goal' => 7000000,
            'current_funding' => 7000000,
            'success_rate' => 80.0,
            'expected_return' => 20.0,
            'deadline' => now()->addMonths(5)->format('Y-m-d'),
            'legal_analysis' => 'Caso sólido con documentación completa de incumplimientos.',
            'evaluation_data' => json_encode([
                'estimated_duration_months' => 12,
                'complexity' => 'medium',
                'precedents' => 'Favorable',
            ]),
            'lawyer_evaluation_fee' => 500000,
            'lawyer_success_fee_percentage' => 16,
            'lawyer_fixed_fee' => 2000000,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(5),
        ]);

        // Case 7: COMPLETED (case won)
        DB::table('cases')->insert([
            'title' => 'Publicidad engañosa - Cencosud',
            'description' => 'Promoción con precios falsos que no se respetaron al momento de compra.',
            'victim_id' => $victims[2]->id,
            'lawyer_id' => $lawyers[1]->id,
            'status' => 'completed',
            'category' => 'Retail',
            'company' => 'Cencosud',
            'funding_goal' => 4500000,
            'current_funding' => 4500000,
            'success_rate' => 90.0,
            'expected_return' => 25.0,
            'deadline' => now()->subMonths(1)->format('Y-m-d'),
            'legal_analysis' => 'Caso ganador con sentencia favorable.',
            'evaluation_data' => json_encode([
                'estimated_duration_months' => 5,
                'complexity' => 'low',
                'precedents' => 'Muy favorable',
            ]),
            'lawyer_evaluation_fee' => 350000,
            'lawyer_success_fee_percentage' => 12,
            'lawyer_fixed_fee' => 1200000,
            'lawyer_total_compensation' => 2110000, // 350000 + 1200000 + 12% of recovered
            'lawyer_paid_at' => now()->subDays(3),
            'outcome' => 'won',
            'amount_recovered' => 8500000,
            'legal_costs' => 1200000,
            'outcome_description' => 'Sentencia favorable. La empresa fue multada y obligada a compensar al consumidor.',
            'resolution_date' => now()->subDays(10)->format('Y-m-d'),
            'closed_at' => now()->subDays(3),
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subDays(3),
        ]);

        // Case 8: REJECTED (lawyer rejected the case)
        DB::table('cases')->insert([
            'title' => 'Multa de tránsito incorrecta',
            'description' => 'Me pusieron una multa por exceso de velocidad en un lugar donde no estuve.',
            'victim_id' => $victims[3]->id,
            'lawyer_id' => $lawyers[0]->id,
            'status' => 'rejected',
            'category' => 'Tránsito',
            'company' => 'Municipalidad de Santiago',
            'funding_goal' => 0,
            'current_funding' => 0,
            'legal_analysis' => 'Caso rechazado por falta de evidencia sólida y bajo monto en disputa.',
            'evaluation_data' => json_encode([
                'rejection_reason' => 'Evidencia insuficiente y monto no justifica litigio',
            ]),
            'created_at' => now()->subDays(12),
            'updated_at' => now()->subDays(11),
        ]);
    }
}
