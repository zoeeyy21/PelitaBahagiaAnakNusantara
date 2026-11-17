<?php

namespace Database\Factories;

use App\Models\DonationPackage;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationPackage>
 */
class DonationPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'name' => 'Paket '.$this->faker->word(),
            'amount' => $this->faker->randomElement([50000, 100000, 250000]),
            'sort_order' => 0,
        ];
    }
}
