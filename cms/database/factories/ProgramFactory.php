<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->paragraph(),
            'description' => $this->faker->paragraphs(3, true),
            'goal' => $this->faker->sentence(),
            'beneficiaries' => $this->faker->words(4, true),
            'cover_image' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=900&q=80',
            'status' => 'active',
            'is_featured' => true,
            'accepts_donation' => true,
            'donation_target' => 10000000,
            'min_donation_amount' => 10000,
            'published_at' => now(),
        ];
    }
}
