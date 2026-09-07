<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(10, 0);

        return [
            'barber_id' => Barber::factory(),
            'customer_id' => Customer::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'base_price_amount' => 1_000_000,
            'services_amount' => 0,
            'total_amount' => 1_000_000,
            'deposit_percentage' => 30,
            'deposit_amount' => 300_000,
            'currency' => 'IRR',
            'expires_at' => null,
            'notes' => null,
        ];
    }
}
