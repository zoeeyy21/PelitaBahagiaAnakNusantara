<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'contact.'.$this->faker->unique()->randomElement(array_keys(SiteSetting::CONTACT_DEFAULTS)),
            'value' => $this->faker->sentence(3),
        ];
    }
}
