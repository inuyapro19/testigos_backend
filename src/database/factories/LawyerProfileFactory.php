<?php

namespace Database\Factories;

use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LawyerProfileFactory extends Factory
{
    protected $model = LawyerProfile::class;

    public function definition(): array
    {
        $specializations = [
            ['Derecho Laboral', 'Despidos', 'Negociación Colectiva'],
            ['Derecho Civil', 'Indemnizaciones', 'Contratos'],
            ['Derecho Laboral', 'Acoso Laboral', 'Discriminación'],
            ['Derecho del Trabajo', 'Horas Extras', 'Finiquitos'],
        ];

        $lawFirms = [
            'Estudio Jurídico González & Asociados',
            'Abogados Laborales Chile',
            'Bufete Legal del Trabajo',
            'Asesoría Legal Integral',
            null,
        ];

        return [
            'user_id' => User::factory(),
            'license_number' => 'CHL-' . $this->faker->unique()->numerify('######'),
            'law_firm' => $this->faker->randomElement($lawFirms),
            'specializations' => $this->faker->randomElement($specializations),
            'years_experience' => $this->faker->numberBetween(2, 25),
            'bio' => $this->faker->paragraphs(2, true),
            'success_rate' => $this->faker->randomFloat(2, 70, 98),
            'cases_handled' => $this->faker->numberBetween(10, 150),
            'total_recovered' => $this->faker->numberBetween(5000000, 50000000),
            'is_verified' => $this->faker->boolean(80),
            'verified_at' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
