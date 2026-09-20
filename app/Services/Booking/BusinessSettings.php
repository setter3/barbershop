<?php

namespace App\Services\Booking;

use App\Models\Setting;

class BusinessSettings
{
    public function basePriceAmount(): int
    {
        return $this->integer('base_price', (int) config('barbershop.base_price'));
    }

    public function depositPercentage(): int
    {
        return 100;
    }

    public function slotDurationMinutes(): int
    {
        return max(1, $this->integer('slot_duration_minutes', (int) config('barbershop.slot_duration_minutes')));
    }

    public function holdMinutes(): int
    {
        return max(1, $this->integer('hold_minutes', (int) config('barbershop.hold_minutes')));
    }

    public function currency(): string
    {
        return (string) $this->value('currency', (string) config('barbershop.currency'));
    }

    public function bookingMode(): string
    {
        $mode = (string) $this->value('booking_mode', (string) config('barbershop.booking_mode'));

        return in_array($mode, ['manual_confirmation', 'online_deposit'], true)
            ? $mode
            : 'manual_confirmation';
    }

    public function usesOnlineDeposit(): bool
    {
        return $this->bookingMode() === 'online_deposit';
    }

    private function integer(string $key, int $fallback): int
    {
        return (int) $this->value($key, $fallback);
    }

    private function value(string $key, mixed $fallback): mixed
    {
        return Setting::query()->where('key', $key)->first()?->typedValue() ?? $fallback;
    }
}
