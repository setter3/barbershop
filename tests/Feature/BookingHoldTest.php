<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SlotClaim;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingHoldTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_customer_can_create_a_priced_hold_without_an_account(): void
    {
        [$barber, $service, $startsAt] = $this->bookingFixture();

        $response = $this->postJson(route('booking.holds.store'), [
            'barber_id' => $barber->getKey(),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'service_ids' => [$service->getKey()],
            'full_name' => 'مشتری آزمایشی',
            'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', ReservationStatus::PendingPayment->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Unpaid->value)
            ->assertJsonPath('data.total_amount', 150_000)
            ->assertJsonPath('data.deposit_percentage', 25)
            ->assertJsonPath('data.deposit_amount', 37_500)
            ->assertJsonPath('data.redirect_url', fn (string $url): bool => str_contains($url, '/booking/'));

        $this->assertGuest();
        $this->assertDatabaseHas('customers', ['mobile' => '+989121234567']);
        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('slot_claims', 2);
        $this->assertDatabaseHas('reservation_service', [
            'service_id' => $service->getKey(),
            'name_snapshot' => $service->name,
            'price_amount' => 50_000,
            'duration_minutes' => 15,
        ]);
    }

    public function test_availability_endpoint_returns_slots_and_deposit_quote(): void
    {
        [$barber, $service, $startsAt] = $this->bookingFixture();

        $this->getJson(route('booking.availability', [
            'barber_id' => $barber->getKey(),
            'date' => $startsAt->toDateString(),
            'service_ids' => [$service->getKey()],
        ]))
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 45)
            ->assertJsonPath('data.slots.0', $startsAt->toIso8601String())
            ->assertJsonPath('data.quote.total_amount', 150_000)
            ->assertJsonPath('data.quote.deposit_amount', 37_500);
    }

    public function test_second_request_for_same_slot_is_rejected_without_residue(): void
    {
        [$barber, $service, $startsAt] = $this->bookingFixture();
        $payload = [
            'barber_id' => $barber->getKey(),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'service_ids' => [$service->getKey()],
            'full_name' => 'مشتری اول',
            'mobile' => '09121234567',
        ];

        $this->postJson(route('booking.holds.store'), $payload)->assertCreated();

        $this->postJson(route('booking.holds.store'), [
            ...$payload,
            'full_name' => 'مشتری دوم',
            'mobile' => '09351234567',
        ])->assertConflict()->assertJsonPath('code', 'slot_unavailable');

        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('slot_claims', 2);
    }

    public function test_database_unique_constraint_guards_slot_claims(): void
    {
        [$barber, , $startsAt] = $this->bookingFixture();
        $first = Reservation::factory()->for($barber)->create();
        $second = Reservation::factory()->for($barber)->create();

        SlotClaim::query()->create([
            'barber_id' => $barber->getKey(),
            'reservation_id' => $first->getKey(),
            'slot_start' => $startsAt,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->expectException(QueryException::class);

        SlotClaim::query()->create([
            'barber_id' => $barber->getKey(),
            'reservation_id' => $second->getKey(),
            'slot_start' => $startsAt,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function test_expiration_command_releases_claims_and_marks_hold_expired(): void
    {
        [$barber, $service, $startsAt] = $this->bookingFixture();

        $this->postJson(route('booking.holds.store'), [
            'barber_id' => $barber->getKey(),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'service_ids' => [$service->getKey()],
            'full_name' => 'مشتری آزمایشی',
            'mobile' => '09121234567',
        ])->assertCreated();

        CarbonImmutable::setTestNow(now()->addMinutes(11));

        $this->artisan('bookings:expire-holds')
            ->expectsOutput('Expired 1 booking hold(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('reservations', ['status' => ReservationStatus::Expired->value]);
        $this->assertDatabaseCount('slot_claims', 0);
    }

    public function test_service_must_be_offered_by_selected_barber(): void
    {
        [$barber, , $startsAt] = $this->bookingFixture();
        $unavailableService = Service::factory()->create();

        $this->getJson(route('booking.availability', [
            'barber_id' => $barber->getKey(),
            'date' => $startsAt->toDateString(),
            'service_ids' => [$unavailableService->getKey()],
        ]))->assertUnprocessable()->assertJsonValidationErrors('service_ids');
    }

    /**
     * @return array{Barber, Service, CarbonImmutable}
     */
    private function bookingFixture(): array
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 08:00:00', 'Asia/Tehran'));
        Setting::factory()->create(['key' => 'base_price', 'value' => '100000', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'deposit_percentage', 'value' => '25', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'slot_duration_minutes', 'value' => '30', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'hold_minutes', 'value' => '10', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'currency', 'value' => 'IRR', 'type' => 'string']);

        $startsAt = CarbonImmutable::now('Asia/Tehran')->addDay()->setTime(9, 0);
        $barber = Barber::factory()->create(['slot_duration_minutes' => 30]);
        $service = Service::factory()->create(['price_amount' => 50_000, 'duration_minutes' => 15]);
        $barber->services()->attach($service);
        BarberSchedule::factory()->for($barber)->create([
            'weekday' => $startsAt->dayOfWeek,
            'starts_at' => '09:00:00',
            'ends_at' => '12:00:00',
        ]);

        return [$barber, $service, $startsAt];
    }
}
