<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'price_amount' => fake()->numberBetween(0, 5_000_000),
            'duration_minutes' => fake()->randomElement([0, 15, 30, 45]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
