<?php

namespace App\Services\Booking;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SlotClaim;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReservationCreator
{
    public function __construct(
        private AvailabilityService $availability,
        private BookingQuoteCalculator $quotes,
        private BusinessSettings $settings,
    ) {}

    /**
     * @param  array{barber_id: int, starts_at: string, service_ids?: list<int>, full_name: string, mobile: string, notes?: ?string}  $data
     */
    public function create(array $data): Reservation
    {
        $timezone = (string) config('app.timezone');
        $startsAt = CarbonImmutable::parse($data['starts_at'], $timezone)->setSecond(0);

        return DB::transaction(function () use ($data, $startsAt): Reservation {
            $barber = Barber::query()
                ->whereKey($data['barber_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $quote = $this->quotes->calculate($barber, $data['service_ids'] ?? []);
            $endsAt = $startsAt->addMinutes($quote['duration_minutes']);

            SlotClaim::query()
                ->where('barber_id', $barber->getKey())
                ->where('expires_at', '<=', now())
                ->delete();

            if (! $this->availability->isAvailable($barber, $startsAt, $quote['duration_minutes'])) {
                throw new SlotUnavailableException;
            }

            $customer = Customer::query()->updateOrCreate(
                ['mobile' => $data['mobile']],
                ['full_name' => $data['full_name']],
            );
            $usesOnlineDeposit = $this->settings->usesOnlineDeposit();
            $expiresAt = $usesOnlineDeposit
                ? CarbonImmutable::now(config('app.timezone'))->addMinutes($this->settings->holdMinutes())
                : null;
            $reservation = Reservation::query()->create([
                'barber_id' => $barber->getKey(),
                'customer_id' => $customer->getKey(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $usesOnlineDeposit ? ReservationStatus::PendingPayment : ReservationStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'base_price_amount' => $quote['base_price_amount'],
                'services_amount' => $quote['services_amount'],
                'total_amount' => $quote['total_amount'],
                'deposit_percentage' => $quote['deposit_percentage'],
                'deposit_amount' => $quote['deposit_amount'],
                'currency' => $quote['currency'],
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($quote['services'] as $service) {
                $reservation->services()->attach($service->getKey(), [
                    'name_snapshot' => $service->name,
                    'price_amount' => $service->price_amount,
                    'duration_minutes' => $service->duration_minutes,
                ]);
            }

            $claimStart = $startsAt;

            while ($claimStart->lessThan($endsAt)) {
                try {
                    SlotClaim::query()->create([
                        'barber_id' => $barber->getKey(),
                        'reservation_id' => $reservation->getKey(),
                        'slot_start' => $claimStart,
                        'expires_at' => $expiresAt,
                    ]);
                } catch (QueryException $exception) {
                    throw new SlotUnavailableException($exception);
                }

                $claimStart = $claimStart->addMinutes(max(1, (int) ($barber->slot_duration_minutes ?: $this->settings->slotDurationMinutes())));
            }

            return $reservation->load(['barber', 'customer', 'services', 'slotClaims']);
        }, 3);
    }
}
