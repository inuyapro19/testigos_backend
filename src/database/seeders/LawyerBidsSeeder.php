<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use App\Models\LawyerBid;
use App\Models\User;
use Illuminate\Database\Seeder;

class LawyerBidsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener abogados
        $lawyers = User::role('lawyer')->get();

        if ($lawyers->isEmpty()) {
            $this->command->warn('No hay abogados en el sistema. Ejecuta UsersSeeder primero.');
            return;
        }

        // Obtener casos en estado de licitación
        $casesForBidding = CaseModel::whereIn('status', ['approved_for_bidding', 'receiving_bids', 'bids_closed'])
            ->get();

        if ($casesForBidding->isEmpty()) {
            $this->command->info('No hay casos disponibles para licitación.');
            return;
        }

        $this->command->info('Creando licitaciones de ejemplo...');

        foreach ($casesForBidding as $case) {
            // Crear entre 2 y 4 licitaciones por caso
            $numBids = rand(2, min(4, $lawyers->count()));
            $selectedLawyers = $lawyers->random($numBids);

            foreach ($selectedLawyers as $index => $lawyer) {
                $isWinningBid = $index === 0 && in_array($case->status, ['bids_closed', 'lawyer_assigned']);

                $bid = LawyerBid::create([
                    'case_id' => $case->id,
                    'lawyer_id' => $lawyer->id,

                    // Propuesta económica variada
                    'funding_goal_proposed' => rand(5000000, 20000000), // 5M - 20M CLP
                    'expected_return_percentage' => rand(15, 40), // 15% - 40%
                    'lawyer_evaluation_fee' => rand(500000, 2000000), // 500K - 2M
                    'lawyer_success_fee_percentage' => rand(10, 30), // 10% - 30%
                    'lawyer_fixed_fee' => rand(2000000, 5000000), // 2M - 5M

                    // Propuesta técnica
                    'success_probability' => rand(50, 95), // 50% - 95%
                    'estimated_duration_months' => rand(6, 24), // 6-24 meses
                    'legal_strategy' => $this->generateLegalStrategy(),
                    'experience_summary' => $this->generateExperienceSummary(),
                    'why_best_candidate' => $this->generateWhyBestCandidate(),
                    'similar_cases_won' => rand(0, 15),
                    'similar_cases_description' => $this->generateSimilarCasesDescription(),

                    // Estado según si es ganadora
                    'status' => $isWinningBid ? 'accepted' : $this->getRandomBidStatus($case->status),

                    // Evaluación del admin (si fue revisada)
                    'admin_score' => $isWinningBid ? rand(8, 10) : rand(5, 9),
                    'admin_feedback' => $isWinningBid
                        ? 'Excelente propuesta. Experiencia comprobada y estrategia sólida.'
                        : $this->generateAdminFeedback(),
                    'reviewed_at' => now()->subDays(rand(1, 5)),
                ]);

                $this->command->info("✓ Licitación creada: {$lawyer->name} para caso #{$case->id} ({$bid->status})");
            }
        }

        $this->command->info('✓ Licitaciones creadas exitosamente');
    }

    private function generateLegalStrategy(): string
    {
        $strategies = [
            'Plantearemos una demanda por incumplimiento contractual fundamentada en los artículos 1545 y 1556 del Código Civil. La estrategia se centra en demostrar el daño patrimonial y moral sufrido por nuestro representado mediante peritajes económicos y testimonios de expertos. Solicitaremos medidas precautorias para garantizar el pago de la indemnización.',
            'Nuestra estrategia se basa en tres pilares: 1) Recopilación exhaustiva de evidencia documental, 2) Constitución de peritos especializados en evaluación de daños, 3) Negociación previa con la contraparte para evitar juicio largo. Si no hay acuerdo, procederemos con demanda civil por responsabilidad extracontractual según artículos 2314 y siguientes del Código Civil.',
            'Aplicaremos la Ley de Protección al Consumidor (Ley 19.496) dado que se trata de una relación de consumo. Documentaremos todas las comunicaciones con la empresa, buscaremos casos similares resueltos favorablemente, y presentaremos demanda colectiva si identificamos otros afectados.',
        ];
        return $strategies[array_rand($strategies)];
    }

    private function generateExperienceSummary(): string
    {
        $experiences = [
            'Abogado con 12 años de experiencia en litigios civiles y comerciales. He representado a más de 100 clientes en casos contra grandes empresas. Especializado en derecho del consumidor y responsabilidad civil.',
            'Licenciado en Derecho de la Universidad de Chile, Magíster en Derecho de los Negocios. 8 años de experiencia en estudios boutique especializados en litigios complejos. Tasa de éxito del 78% en casos similares.',
            'Abogado litigante con amplia experiencia en Cortes de Apelaciones y Corte Suprema. He ganado casos emblemáticos contra empresas de retail, telecomunicaciones y servicios financieros.',
        ];
        return $experiences[array_rand($experiences)];
    }

    private function generateWhyBestCandidate(): string
    {
        $reasons = [
            'Mi ventaja competitiva está en mi enfoque personalizado y agresivo en la negociación. No acepto acuerdos desfavorables. Además, cuento con un equipo de apoyo que trabaja casos complejos, lo que permite dedicación exclusiva sin descuidar calidad.',
            'Soy el candidato ideal porque combino experiencia técnica con habilidades de negociación. He logrado acuerdos extrajudiciales en el 60% de mis casos, ahorrando tiempo y costos. Cuando es necesario litigar, lo hago con total preparación.',
            'Me destaco por mi disponibilidad y comunicación constante con clientes. Entiendo que estos procesos generan ansiedad, por eso mantengo informado al cliente semanalmente.',
        ];
        return $reasons[array_rand($reasons)];
    }

    private function generateSimilarCasesDescription(): string
    {
        $descriptions = [
            'He ganado casos contra Falabella por publicidad engañosa (2022), Movistar por cobros indebidos (2021), Banco de Chile por cláusulas abusivas (2020). Todos con sentencias favorables.',
            'Experiencia en: demanda colectiva contra empresa de seguros (15 clientes, ganado), caso contra automotora por vicio oculto (ganado), caso contra inmobiliaria por incumplimiento (transado favorablemente).',
            'Portfolio incluye 8 casos ganados contra empresas del IPSA, 12 transacciones favorables en etapa prejudicial, 3 sentencias publicadas en revista de jurisprudencia.',
        ];
        return $descriptions[array_rand($descriptions)];
    }

    private function generateAdminFeedback(): string
    {
        $feedbacks = [
            'Buena propuesta, pero el monto solicitado es elevado.',
            'Estrategia legal sólida. Falta mayor detalle en timeline.',
            'Experiencia comprobable, pero estimación de éxito parece optimista.',
            'Propuesta interesante aunque poco detallada en aspectos económicos.',
            'Buen perfil profesional. Propuesta económica competitiva.',
        ];
        return $feedbacks[array_rand($feedbacks)];
    }

    private function getRandomBidStatus(string $caseStatus): string
    {
        return match ($caseStatus) {
            'approved_for_bidding', 'receiving_bids' => ['submitted', 'under_review'][array_rand(['submitted', 'under_review'])],
            'bids_closed' => ['under_review', 'rejected'][array_rand(['under_review', 'rejected'])],
            default => 'submitted',
        };
    }
}
