<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case NoShow = 'no_show';

    public function blocksAvailability(): bool
    {
        return in_array($this, [self::PendingPayment, self::Confirmed, self::InProgress], true);
    }

    /**
     * @return list<string>
     */
    public static function blockingValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->blocksAvailability()),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'منتظر پرداخت',
            self::Confirmed => 'تأییدشده',
            self::InProgress => 'در حال انجام',
            self::Completed => 'انجام‌شده',
            self::Cancelled => 'لغوشده',
            self::Expired => 'منقضی‌شده',
            self::NoShow => 'عدم مراجعه',
        };
    }
}
