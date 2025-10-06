<?php

namespace Database\Factories;

use App\Models\CaseUpdate;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseUpdateFactory extends Factory
{
    protected $model = CaseUpdate::class;

    public function definition(): array
    {
        $types = ['status_change', 'funding_milestone', 'legal_update', 'general'];
        $statuses = ['draft', 'pending', 'published', 'funding', 'in_progress', 'completed'];

        $previousStatus = $this->faker->randomElement($statuses);
        $newStatus = $this->faker->randomElement(array_diff($statuses, [$previousStatus]));

        return [
            'case_id' => CaseModel::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement([
                'Actualización del estado del caso',
                'Nueva evidencia presentada',
                'Audiencia programada',
                'Acuerdo alcanzado',
                'Meta de financiamiento alcanzada',
                'Documentación adicional requerida',
            ]),
            'description' => $this->faker->paragraphs(2, true),
            'type' => $this->faker->randomElement($types),
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'metadata' => [
                'importance' => $this->faker->randomElement(['low', 'medium', 'high']),
                'public' => $this->faker->boolean(80),
            ],
            'notify_victim' => $this->faker->boolean(70),
            'notify_investors' => $this->faker->boolean(60),
        ];
    }
}
