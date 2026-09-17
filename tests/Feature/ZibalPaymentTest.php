<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SlotClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZibalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.zibal', [
            'merchant' => 'test-merchant',
            'request_url' => 'https://gateway.zibal.test/v1/request',
            'verify_url' => 'https://gateway.zibal.test/v1/verify',
            'inquiry_url' => 'https://gateway.zibal.test/v1/inquiry',
            'start_url' => 'https://gateway.zibal.test/start',
            'payment_hold_minutes' => 20,
        ]);
    }

    public function test_customer_is_redirected_to_zibal_for_exact_deposit_amount(): void
    {
        $reservation = $this->pendingReservation();
        Http::fake([
            'https://gateway.zibal.test/v1/request' => Http::response([
                'result' => 100,
                'message' => 'success',
                'trackId' => 15966442233311,
            ]),
        ]);

        $this->post(route('booking.payments.zibal.start', $reservation))
            ->assertRedirect('https://gateway.zibal.test/start/15966442233311');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->getKey(),
            'provider' => 'zibal',
            'authority' => '15966442233311',
            'amount' => 300_000,
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertSame(PaymentStatus::Pending, $reservation->fresh()->payment_status);

        Http::assertSent(function (Request $request) use ($reservation): bool {
            return $request->url() === 'https://gateway.zibal.test/v1/request'
                && $request['merchant'] === 'test-merchant'
                && $request['amount'] === 300_000
                && $request['orderId'] === $reservation->reference
                && $request['mobile'] === '09121234567'
                && str_contains($request['callbackUrl'], '/booking/payments/zibal/callback');
        });
    }

    public function test_gateway_rejection_returns_a_safe_diagnostic_code(): void
    {
        $reservation = $this->pendingReservation();
        Http::fake([
            'https://gateway.zibal.test/v1/request' => Http::response([
                'result' => 103,
                'message' => 'authentication error',
            ]),
        ]);

        $this->from(route('booking.create'))
            ->post(route('booking.payments.zibal.start', $reservation))
            ->assertRedirect(route('booking.create'))
            ->assertSessionHas('payment_error', fn (string $message): bool => str_contains($message, 'ZP-RESULT-103'));
    }

    public function test_verified_payment_confirms_reservation_and_permanently_claims_slot(): void
    {
        $reservation = $this->pendingReservation();
        $claim = SlotClaim::factory()->for($reservation)->for($reservation->barber)->create([
            'expires_at' => now()->addMinutes(10),
        ]);
        $payment = Payment::factory()->for($reservation)->create([
            'provider' => 'zibal',
            'authority' => '15966442233311',
            'amount' => $reservation->deposit_amount,
            'currency' => 'IRR',
            'status' => PaymentStatus::Pending,
        ]);
        Http::fake([
            'https://gateway.zibal.test/v1/verify' => Http::response([
                'result' => 100,
                'status' => 1,
                'amount' => 300_000,
                'orderId' => $reservation->reference,
                'refNumber' => 87654321,
                'paidAt' => '2030-01-01T10:00:00.000000',
            ]),
        ]);

        $this->get(route('booking.payments.zibal.callback', [
            'trackId' => $payment->authority,
            'success' => 1,
            'status' => 2,
            'orderId' => $reservation->reference,
        ]))->assertRedirect(route('booking.show', [
            'reservation' => $reservation,
            'payment' => 'success',
        ]));

        $reservation->refresh();
        $payment->refresh();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::Paid, $reservation->payment_status);
        $this->assertNull($reservation->expires_at);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('87654321', $payment->transaction_id);
        $this->assertNull($claim->fresh()->expires_at);
    }

    public function test_paid_amount_mismatch_is_held_for_review_without_allowing_another_charge(): void
    {
        $reservation = $this->pendingReservation();
        SlotClaim::factory()->for($reservation)->for($reservation->barber)->create();
        $payment = Payment::factory()->for($reservation)->create([
            'provider' => 'zibal',
            'authority' => '15966442233311',
            'amount' => $reservation->deposit_amount,
            'status' => PaymentStatus::Pending,
        ]);
        Http::fake([
            'https://gateway.zibal.test/v1/verify' => Http::response([
                'result' => 100,
                'status' => 1,
                'amount' => 299_999,
                'orderId' => $reservation->reference,
            ]),
        ]);

        $this->get(route('booking.payments.zibal.callback', [
            'trackId' => $payment->authority,
            'success' => 1,
            'status' => 2,
            'orderId' => $reservation->reference,
        ]))->assertRedirect(route('booking.show', [
            'reservation' => $reservation,
            'payment' => 'review',
        ]));

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $reservation->fresh()->payment_status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertDatabaseMissing('slot_claims', ['reservation_id' => $reservation->getKey()]);
    }

    public function test_cancelled_gateway_callback_is_failed_without_calling_verify(): void
    {
        $reservation = $this->pendingReservation();
        $payment = Payment::factory()->for($reservation)->create([
            'provider' => 'zibal',
            'authority' => '15966442233311',
            'amount' => $reservation->deposit_amount,
            'status' => PaymentStatus::Pending,
        ]);
        Http::fake();

        $this->get(route('booking.payments.zibal.callback', [
            'trackId' => $payment->authority,
            'success' => 0,
            'status' => 3,
            'orderId' => $reservation->reference,
        ]))->assertRedirect(route('booking.show', [
            'reservation' => $reservation,
            'payment' => 'failed',
        ]));

        Http::assertNothingSent();
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Failed, $reservation->fresh()->payment_status);
    }

    private function pendingReservation(): Reservation
    {
        return Reservation::factory()
            ->for(Customer::factory()->state(['mobile' => '+989121234567']))
            ->create([
                'status' => ReservationStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'total_amount' => 1_000_000,
                'deposit_percentage' => 30,
                'deposit_amount' => 300_000,
                'currency' => 'IRR',
                'expires_at' => now()->addMinutes(10),
            ]);
    }
}
