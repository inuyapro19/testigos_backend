<?php

namespace Database\Factories;

use App\Models\InvestorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestorProfileFactory extends Factory
{
    protected $model = InvestorProfile::class;

    public function definition(): array
    {
        $investorTypes = ['individual', 'institutional', 'accredited'];
        $preferences = [
            ['laboral', 'despido_injustificado'],
            ['discriminacion', 'acoso_laboral'],
            ['horas_extras', 'accidente_trabajo'],
            ['laboral'],
        ];

        return [
            'user_id' => User::factory(),
            'investor_type' => $this->faker->randomElement($investorTypes),
            'total_invested' => $this->faker->numberBetween(500000, 10000000),
            'total_returns' => $this->faker->numberBetween(0, 12000000),
            'active_investments' => $this->faker->numberBetween(0, 15),
            'completed_investments' => $this->faker->numberBetween(0, 30),
            'average_return_rate' => $this->faker->randomFloat(2, 10, 45),
            'investment_preferences' => $this->faker->randomElement($preferences),
            'minimum_investment' => $this->faker->randomElement([50000, 100000, 200000, 500000]),
            'maximum_investment' => $this->faker->randomElement([1000000, 2000000, 5000000, 10000000]),
            'is_accredited' => $this->faker->boolean(40),
            'accredited_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    public function accredited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_accredited' => true,
            'accredited_at' => now(),
        ]);
    }
}
