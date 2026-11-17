<?php

namespace Database\Factories;

use App\Models\ImpactMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImpactMetric>
 */
class ImpactMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->words(2, true),
            'value' => $this->faker->numberBetween(10, 500),
            'suffix' => '+',
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
