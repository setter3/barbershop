<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\BarberSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarberSchedule>
 */
class BarberScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barber_id' => Barber::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'starts_at' => '09:00:00',
            'ends_at' => '18:00:00',
            'is_active' => true,
        ];
    }
}
