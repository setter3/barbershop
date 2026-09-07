<?php

namespace App\Services\Booking;

use App\Models\Barber;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BookingQuoteCalculator
{
    public function __construct(private BusinessSettings $settings) {}

    /**
     * @param  list<int>  $serviceIds
     * @return array{services: Collection<int, Service>, duration_minutes: int, base_price_amount: int, services_amount: int, total_amount: int, deposit_percentage: int, deposit_amount: int, currency: string}
     */
    public function calculate(Barber $barber, array $serviceIds): array
    {
        $serviceIds = array_values(array_unique($serviceIds));
        $services = $serviceIds === []
            ? new Collection
            : $barber->services()->where('services.is_active', true)->whereKey($serviceIds)->get();

        if ($services->count() !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'یک یا چند خدمت برای آرایشگر انتخاب‌شده قابل ارائه نیست.',
            ]);
        }

        $basePrice = $this->settings->basePriceAmount();
        $servicesAmount = (int) $services->sum('price_amount');
        $totalAmount = $basePrice + $servicesAmount;
        $depositPercentage = $this->settings->depositPercentage();

        return [
            'services' => $services,
            'duration_minutes' => max(1, $barber->slot_duration_minutes + (int) $services->sum('duration_minutes')),
            'base_price_amount' => $basePrice,
            'services_amount' => $servicesAmount,
            'total_amount' => $totalAmount,
            'deposit_percentage' => $depositPercentage,
            'deposit_amount' => (int) ceil($totalAmount * $depositPercentage / 100),
            'currency' => $this->settings->currency(),
        ];
    }
}
