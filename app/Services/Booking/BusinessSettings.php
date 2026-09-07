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
        return min(100, max(0, $this->integer('deposit_percentage', (int) config('barbershop.deposit_percentage'))));
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

    private function integer(string $key, int $fallback): int
    {
        return (int) $this->value($key, $fallback);
    }

    private function value(string $key, mixed $fallback): mixed
    {
        return Setting::query()->where('key', $key)->first()?->typedValue() ?? $fallback;
    }
}
