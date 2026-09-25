<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SlotClaim;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_a_barber_with_services_and_weekly_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.barbers.store'), [
            'name' => 'آرشام',
            'slug' => 'arsham-admin',
            'bio' => 'متخصص استایل مردانه',
            'slot_duration_minutes' => 30,
            'is_active' => 1,
            'sort_order' => 1,
            'service_ids' => [$service->getKey()],
            'schedules' => $this->schedules([6, 0, 1, 2, 3, 4]),
        ]);

        $barber = Barber::query()->where('slug', 'arsham-admin')->firstOrFail();
        $response->assertRedirect(route('admin.barbers.edit', $barber));
        $this->assertTrue($barber->services()->whereKey($service->getKey())->exists());
        $this->assertSame(6, $barber->schedules()->count());
    }

    public function test_main_admin_pages_render_for_an_administrator(): void
    {
        $admin = User::factory()->admin()->create();
        $barber = Barber::factory()->create();
        $service = Service::factory()->create();
        $reservation = Reservation::factory()->for($barber)->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.barbers.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.barbers.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.barbers.edit', $barber))->assertOk();
        $this->actingAs($admin)->get(route('admin.services.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.services.edit', $service))->assertOk();
        $this->actingAs($admin)->get(route('admin.reservations.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reservations.show', $reservation))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_reservation_reports_show_both_appointment_and_payment_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $appointmentAt = CarbonImmutable::parse('2030-03-22 14:00:00', 'Asia/Tehran');
        $paidAt = CarbonImmutable::parse('2030-03-20 09:30:00', 'Asia/Tehran');
        $reservation = Reservation::factory()->create([
            'starts_at' => $appointmentAt,
            'ends_at' => $appointmentAt->addHour(),
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
        ]);
        Payment::factory()->for($reservation)->create([
            'status' => PaymentStatus::Paid,
            'paid_at' => $paidAt,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee(JalaliDate::format($appointmentAt))
            ->assertSee(JalaliDate::format($paidAt));

        $this->actingAs($admin)
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee(JalaliDate::format($appointmentAt))
            ->assertSee(JalaliDate::format($paidAt));
    }

    public function test_editing_a_paid_reservation_preserves_its_original_payment_date(): void
    {
        $admin = User::factory()->admin()->create();
        $paidAt = CarbonImmutable::parse('2030-03-20 09:30:00', 'Asia/Tehran');
        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $payment = Payment::factory()->for($reservation)->create([
            'provider' => 'manual',
            'status' => PaymentStatus::Paid,
            'paid_at' => $paidAt,
        ]);

        CarbonImmutable::setTestNow($paidAt->addDays(3));

        $this->actingAs($admin)->put(route('admin.reservations.update', $reservation), [
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'payment_reference' => 'MANUAL-UPDATED',
            'notes' => null,
        ])->assertRedirect();

        $this->assertTrue($payment->fresh()->paid_at->equalTo($paidAt));
        $this->assertSame('MANUAL-UPDATED', $payment->fresh()->transaction_id);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_manual_payment_confirmation_sends_both_approved_sms_patterns(): void
    {
        config()->set('services.sms', [
            'base_url' => 'https://edge.ippanel.test/v1',
            'token' => 'test-api-token',
            'sender_number' => '3000505',
            'customer_pattern' => 'bookingcustomer',
            'owner_pattern' => 'bookingowner',
            'owner_mobile' => '09351234567',
        ]);
        Http::fake([
            'https://edge.ippanel.test/v1/api/send' => Http::response([
                'data' => ['message_outbox_ids' => [1123544244]],
                'meta' => ['status' => true, 'message_code' => '200-1'],
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PendingPayment,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $this->actingAs($admin)->put(route('admin.reservations.update', $reservation), [
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'payment_reference' => 'MANUAL-1001',
            'notes' => null,
        ])->assertRedirect();

        Http::assertSentCount(2);
        Http::assertSent(fn (HttpRequest $request): bool => $request['code'] === 'bookingcustomer'
            && $request['from_number'] === '+983000505');
        Http::assertSent(fn (HttpRequest $request): bool => $request['code'] === 'bookingowner'
            && $request['recipients'] === ['+989351234567']);
    }

    public function test_admin_can_manage_services_and_business_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'پاکسازی صورت',
            'slug' => 'face-cleanup-admin',
            'description' => 'پاکسازی کامل',
            'price_amount' => 1_500_000,
            'duration_minutes' => 30,
            'is_active' => 1,
            'sort_order' => 2,
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'آرشام باربرشاپ',
            'base_price' => 2_500_000,
            'deposit_percentage' => 30,
            'slot_duration_minutes' => 30,
            'hold_minutes' => 10,
            'booking_mode' => 'manual_confirmation',
            'contact_phone' => '02100000000',
            'address' => 'تهران',
        ])->assertRedirect();

        $this->assertDatabaseHas('services', ['slug' => 'face-cleanup-admin', 'price_amount' => 1_500_000]);
        $this->assertDatabaseHas('settings', ['key' => 'booking_mode', 'value' => 'manual_confirmation']);
        $this->assertDatabaseHas('settings', ['key' => 'deposit_percentage', 'value' => '100']);
    }

    public function test_manual_booking_mode_creates_a_confirmed_non_expiring_reservation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 08:00:00', 'Asia/Tehran'));
        Setting::factory()->create(['key' => 'base_price', 'value' => '100000', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'deposit_percentage', 'value' => '30', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'slot_duration_minutes', 'value' => '30', 'type' => 'integer']);
        Setting::factory()->create(['key' => 'booking_mode', 'value' => 'manual_confirmation', 'type' => 'string']);
        $startsAt = CarbonImmutable::now('Asia/Tehran')->addDay()->setTime(10, 0);
        $barber = Barber::factory()->create(['slot_duration_minutes' => 30]);
        $service = Service::factory()->create(['price_amount' => 100000, 'duration_minutes' => 0]);
        $barber->services()->attach($service);
        BarberSchedule::factory()->for($barber)->create(['weekday' => $startsAt->dayOfWeek, 'starts_at' => '10:00:00', 'ends_at' => '13:00:00']);

        $this->postJson(route('booking.holds.store'), [
            'barber_id' => $barber->getKey(),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'service_ids' => [$service->getKey()],
            'full_name' => 'مشتری نسخه اولیه',
            'mobile' => '09121234567',
        ])->assertCreated()->assertJsonPath('data.status', ReservationStatus::Confirmed->value)->assertJsonPath('data.expires_at', null);

        $this->assertDatabaseHas('slot_claims', ['barber_id' => $barber->getKey(), 'expires_at' => null]);
    }

    public function test_admin_can_cancel_reservation_and_release_its_slot(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
        ]);
        SlotClaim::factory()->for($reservation)->for($reservation->barber)->create(['expires_at' => null]);

        $this->actingAs($admin)->put(route('admin.reservations.update', $reservation), [
            'status' => ReservationStatus::Cancelled->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'notes' => 'لغو با درخواست مشتری',
        ])->assertRedirect();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertDatabaseMissing('slot_claims', ['reservation_id' => $reservation->getKey()]);
    }

    public function test_time_off_can_be_added_and_removed_for_a_barber(): void
    {
        $admin = User::factory()->admin()->create();
        $barber = Barber::factory()->create();

        $this->actingAs($admin)->post(route('admin.barbers.time-offs.store', $barber), [
            'starts_on' => '۱۴۰۸/۱۰/۱۲',
            'starts_time' => '10:00',
            'ends_on' => '۱۴۰۸/۱۰/۱۲',
            'ends_time' => '18:00',
            'reason' => 'مرخصی',
        ])->assertRedirect();

        $timeOff = $barber->timeOffs()->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.barbers.time-offs.destroy', [$barber, $timeOff]))->assertRedirect();
        $this->assertDatabaseMissing('barber_time_offs', ['id' => $timeOff->getKey()]);
    }

    public function test_admin_can_delete_unused_barbers_and_services_but_not_historical_records(): void
    {
        $admin = User::factory()->admin()->create();
        $unusedBarber = Barber::factory()->create();
        $unusedService = Service::factory()->create();

        $this->actingAs($admin)->delete(route('admin.barbers.destroy', $unusedBarber))->assertRedirect(route('admin.barbers.index'));
        $this->actingAs($admin)->delete(route('admin.services.destroy', $unusedService))->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseMissing('barbers', ['id' => $unusedBarber->getKey()]);
        $this->assertDatabaseMissing('services', ['id' => $unusedService->getKey()]);

        $reservation = Reservation::factory()->create();
        $historicalService = Service::factory()->create();
        $reservation->services()->attach($historicalService, [
            'name_snapshot' => $historicalService->name,
            'price_amount' => $historicalService->price_amount,
            'duration_minutes' => $historicalService->duration_minutes,
        ]);

        $this->actingAs($admin)->delete(route('admin.barbers.destroy', $reservation->barber))->assertSessionHasErrors('delete');
        $this->actingAs($admin)->delete(route('admin.services.destroy', $historicalService))->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('barbers', ['id' => $reservation->barber_id]);
        $this->assertDatabaseHas('services', ['id' => $historicalService->getKey()]);
    }

    public function test_admin_can_define_a_daily_break_inside_working_hours(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->schedules([6]);
        $payload[6] = [
            'is_active' => 1,
            'starts_at' => '10:00',
            'ends_at' => '18:00',
            'break_starts_at' => '16:00',
            'break_ends_at' => '17:00',
        ];

        $this->actingAs($admin)->post(route('admin.barbers.store'), [
            'name' => 'امین هیرتال',
            'slug' => 'amin-hairtal',
            'slot_duration_minutes' => 60,
            'is_active' => 1,
            'sort_order' => 1,
            'schedules' => $payload,
        ])->assertRedirect();

        $barber = Barber::query()->where('slug', 'amin-hairtal')->firstOrFail();
        $this->assertDatabaseHas('barber_schedules', ['barber_id' => $barber->getKey(), 'starts_at' => '10:00', 'ends_at' => '16:00']);
        $this->assertDatabaseHas('barber_schedules', ['barber_id' => $barber->getKey(), 'starts_at' => '17:00', 'ends_at' => '18:00']);
    }

    private function schedules(array $activeDays): array
    {
        return collect(range(0, 6))->mapWithKeys(fn (int $day): array => [$day => [
            'is_active' => in_array($day, $activeDays, true) ? 1 : 0,
            'starts_at' => '10:00',
            'ends_at' => '21:00',
        ]])->all();
    }
}
