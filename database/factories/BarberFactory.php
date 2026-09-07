<?php

namespace Database\Factories;

use App\Models\Barber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barber>
 */
class BarberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'slug' => fake()->unique()->slug(2),
            'bio' => fake()->optional()->paragraph(),
            'avatar_path' => null,
            'slot_duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
