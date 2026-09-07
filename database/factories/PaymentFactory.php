<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'provider' => 'sandbox',
            'authority' => fake()->unique()->uuid(),
            'amount' => 300_000,
            'currency' => 'IRR',
            'status' => PaymentStatus::Pending,
            'transaction_id' => null,
            'paid_at' => null,
            'payload' => null,
        ];
    }
}
