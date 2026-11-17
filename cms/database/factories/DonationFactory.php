<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_code' => 'YPB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'donation_type' => 'general',
            'amount' => $this->faker->randomElement([50000, 100000, 250000]),
            'donor_name' => $this->faker->name(),
            'donor_email' => $this->faker->safeEmail(),
            'donor_phone' => $this->faker->phoneNumber(),
            'is_anonymous' => false,
            'message' => $this->faker->sentence(),
            'payment_method' => 'manual_transfer',
            'status' => 'pending',
        ];
    }
}
