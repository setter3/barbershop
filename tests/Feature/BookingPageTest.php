<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Service;
use Database\Seeders\DemoBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BookingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_page_only_exposes_active_barbers_and_services(): void
    {
        $activeBarber = Barber::factory()->create(['name' => 'آرایشگر فعال', 'is_active' => true]);
        $inactiveBarber = Barber::factory()->create(['name' => 'آرایشگر غیرفعال', 'is_active' => false]);
        $activeService = Service::factory()->create(['name' => 'پاکسازی فعال', 'is_active' => true]);
        $inactiveService = Service::factory()->create(['name' => 'خدمت غیرفعال', 'is_active' => false]);
        $activeBarber->services()->attach([$activeService->getKey(), $inactiveService->getKey()]);
        $inactiveBarber->services()->attach($activeService);

        $this->get(route('booking.create'))
            ->assertOk()
            ->assertSee('نوبت شما، دقیق و بدون انتظار')
            ->assertViewHas('barbers', function (Collection $barbers) use ($activeBarber, $activeService): bool {
                return $barbers->count() === 1
                    && $barbers->first()['id'] === $activeBarber->getKey()
                    && $barbers->first()['services'][0]['id'] === $activeService->getKey();
            });
    }

    public function test_confirmation_page_uses_public_reference_and_masks_mobile(): void
    {
        $barber = Barber::factory()->create(['name' => 'آرشام']);
        $customer = Customer::factory()->create([
            'full_name' => 'مشتری آزمایشی',
            'mobile' => '+989121234567',
        ]);
        $reservation = Reservation::factory()->for($barber)->for($customer)->create([
            'status' => ReservationStatus::PendingPayment,
            'payment_status' => PaymentStatus::Unpaid,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->get(route('booking.show', $reservation))
            ->assertOk()
            ->assertSee($reservation->reference)
            ->assertSee('+98912***67')
            ->assertDontSee($customer->mobile)
            ->assertSee('منتظر پرداخت');

        $this->get('/booking/'.$reservation->getKey())->assertNotFound();
    }

    public function test_homepage_links_to_live_booking_flow(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('booking.create'), false)
            ->assertSee('رزرو آنلاین');
    }

    public function test_demo_booking_seeder_is_repeatable(): void
    {
        $this->seed(DemoBookingSeeder::class);
        $this->seed(DemoBookingSeeder::class);

        $this->assertDatabaseCount('barbers', 2);
        $this->assertDatabaseCount('services', 3);
        $this->assertDatabaseCount('barber_schedules', 12);
        $this->assertDatabaseCount('barber_service', 6);
    }
}
