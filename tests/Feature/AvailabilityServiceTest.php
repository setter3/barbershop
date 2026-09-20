<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\BarberTimeOff;
use App\Models\Reservation;
use App\Models\SlotClaim;
use App\Services\Booking\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_availability_combines_schedule_time_off_reservations_and_live_claims(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 08:00:00', 'Asia/Tehran'));
        $date = CarbonImmutable::now('Asia/Tehran')->addDay()->startOfDay();
        $barber = Barber::factory()->create(['slot_duration_minutes' => 30]);

        BarberSchedule::factory()->for($barber)->create([
            'weekday' => $date->dayOfWeek,
            'starts_at' => '09:00:00',
            'ends_at' => '12:00:00',
        ]);
        BarberTimeOff::factory()->for($barber)->create([
            'starts_at' => $date->setTime(10, 0),
            'ends_at' => $date->setTime(10, 30),
        ]);
        Reservation::factory()->for($barber)->create([
            'starts_at' => $date->setTime(11, 0),
            'ends_at' => $date->setTime(11, 30),
            'status' => ReservationStatus::Confirmed,
        ]);

        $claimReservation = Reservation::factory()->for($barber)->create([
            'starts_at' => $date->setTime(9, 30),
            'ends_at' => $date->setTime(10, 0),
            'status' => ReservationStatus::Cancelled,
        ]);
        SlotClaim::query()->create([
            'barber_id' => $barber->getKey(),
            'reservation_id' => $claimReservation->getKey(),
            'slot_start' => $date->setTime(9, 30),
            'expires_at' => now()->addMinutes(10),
        ]);
        SlotClaim::query()->create([
            'barber_id' => $barber->getKey(),
            'reservation_id' => $claimReservation->getKey(),
            'slot_start' => $date->setTime(9, 0),
            'expires_at' => now()->subMinute(),
        ]);

        $slots = app(AvailabilityService::class)
            ->forDate($barber, $date, 30)
            ->map(fn (CarbonImmutable $slot): string => $slot->format('H:i'))
            ->all();

        $this->assertSame(['09:00', '10:30', '11:30'], $slots);
    }

    public function test_expired_pending_reservation_does_not_block_a_slot(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 08:00:00', 'Asia/Tehran'));
        $date = CarbonImmutable::now('Asia/Tehran')->addDay()->startOfDay();
        $barber = Barber::factory()->create();
        BarberSchedule::factory()->for($barber)->create([
            'weekday' => $date->dayOfWeek,
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
        ]);
        Reservation::factory()->for($barber)->create([
            'starts_at' => $date->setTime(9, 0),
            'ends_at' => $date->setTime(9, 30),
            'status' => ReservationStatus::PendingPayment,
            'expires_at' => now()->subMinute(),
        ]);

        $slots = app(AvailabilityService::class)->forDate($barber, $date, 30);

        $this->assertTrue($slots->contains(fn (CarbonImmutable $slot): bool => $slot->format('H:i') === '09:00'));
    }

    public function test_barber_cadence_is_used_and_break_window_is_never_offered(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 08:00:00', 'Asia/Tehran'));
        $date = CarbonImmutable::now('Asia/Tehran')->addDay()->startOfDay();
        $barber = Barber::factory()->create(['slot_duration_minutes' => 60]);

        BarberSchedule::factory()->for($barber)->create([
            'weekday' => $date->dayOfWeek,
            'starts_at' => '10:00:00',
            'ends_at' => '16:00:00',
        ]);
        BarberSchedule::factory()->for($barber)->create([
            'weekday' => $date->dayOfWeek,
            'starts_at' => '17:00:00',
            'ends_at' => '18:00:00',
        ]);

        $slots = app(AvailabilityService::class)
            ->forDate($barber, $date, 60)
            ->map(fn (CarbonImmutable $slot): string => $slot->format('H:i'))
            ->all();

        $this->assertSame(['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '17:00'], $slots);
        $this->assertNotContains('16:00', $slots);
    }
}
