<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\Reservation;
use App\Models\SlotClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlotClaim>
 */
class SlotClaimFactory extends Factory
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
            'reservation_id' => Reservation::factory(),
            'slot_start' => now()->addDay()->setTime(10, 0),
            'expires_at' => now()->addMinutes(10),
        ];
    }
}
