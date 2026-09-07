<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\BarberTimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarberTimeOff>
 */
class BarberTimeOffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 30))->startOfHour();

        return [
            'barber_id' => Barber::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
