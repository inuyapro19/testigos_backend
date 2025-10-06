<?php

namespace Database\Factories;

use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseModelFactory extends Factory
{
    protected $model = CaseModel::class;

    public function definition(): array
    {
        $categories = ['laboral', 'despido_injustificado', 'discriminacion', 'acoso_laboral', 'horas_extras', 'accidente_trabajo'];
        $statuses = ['submitted', 'under_review', 'approved', 'published', 'funded', 'in_progress', 'completed', 'rejected'];
        $companies = ['Empresa ABC S.A.', 'Corporación XYZ Ltda.', 'Retail Chile S.A.', 'Constructora del Sur', 'Servicios Generales SpA', 'Minera del Norte'];

        $fundingGoal = $this->faker->numberBetween(500000, 5000000);
        $currentFunding = $this->faker->numberBetween(0, $fundingGoal);

        return [
            'title' => $this->faker->randomElement([
                'Despido injustificado en empresa de retail',
                'Discriminación por embarazo en el trabajo',
                'No pago de horas extras en constructora',
                'Acoso laboral por parte de supervisor',
                'Accidente laboral sin indemnización',
                'Despido durante licencia médica',
                'Vulneración de derechos sindicales',
            ]),
            'description' => $this->faker->paragraphs(3, true),
            'victim_id' => User::factory(),
            'lawyer_id' => User::factory(),
            'status' => $this->faker->randomElement($statuses),
            'category' => $this->faker->randomElement($categories),
            'company' => $this->faker->randomElement($companies),
            'funding_goal' => $fundingGoal,
            'current_funding' => $currentFunding,
            'success_rate' => $this->faker->numberBetween(60, 95),
            'expected_return' => $this->faker->numberBetween(15, 40),
            'deadline' => $this->faker->dateTimeBetween('now', '+6 months'),
            'legal_analysis' => $this->faker->paragraphs(2, true),
            'evaluation_data' => [
                'case_strength' => $this->faker->randomElement(['alta', 'media', 'baja']),
                'estimated_duration' => $this->faker->randomElement(['6 meses', '1 año', '18 meses']),
                'success_probability' => $this->faker->numberBetween(60, 95) . '%',
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function funded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'funded',
            'current_funding' => $attributes['funding_goal'],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'current_funding' => $attributes['funding_goal'],
        ]);
    }
}
