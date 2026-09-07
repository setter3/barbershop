<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\SlotClaim;
use Illuminate\Database\Seeder;

class SlotClaimSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reservation = Reservation::factory()->create();

        SlotClaim::factory()->create([
            'barber_id' => $reservation->barber_id,
            'reservation_id' => $reservation->getKey(),
            'slot_start' => $reservation->starts_at,
        ]);
    }
}
