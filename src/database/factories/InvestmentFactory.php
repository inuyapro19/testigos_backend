<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(50000, 2000000);
        $returnPercentage = $this->faker->numberBetween(15, 40);
        $expectedReturn = ($amount * $returnPercentage) / 100;

        // Calculate platform commission (5-10%, default 7.5%)
        $platformCommissionPercentage = $this->faker->randomFloat(2, 5, 10);
        $platformCommissionAmount = ($amount * $platformCommissionPercentage) / 100;

        return [
            'case_id' => CaseModel::factory(),
            'investor_id' => User::factory(),
            'amount' => $amount,
            'expected_return_percentage' => $returnPercentage,
            'expected_return_amount' => $expectedReturn,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'active', 'completed', 'cancelled']),
            'payment_data' => [
                'method' => $this->faker->randomElement(['webpay', 'transferencia', 'khipu']),
                'transaction_id' => 'TXN-' . $this->faker->unique()->numerify('##########'),
            ],
            'confirmed_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'completed_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-3 months', 'now') : null,
            'actual_return' => $this->faker->boolean(30) ? $amount + $this->faker->numberBetween($expectedReturn * 0.8, $expectedReturn * 1.2) : null,
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'platform_commission_percentage' => $platformCommissionPercentage,
            'platform_commission_amount' => $platformCommissionAmount,
            'success_commission_percentage' => null,
            'success_commission_amount' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
            'completed_at' => now(),
            'actual_return' => $attributes['amount'] + $attributes['expected_return_amount'],
        ]);
    }
}
