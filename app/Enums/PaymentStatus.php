<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'پرداخت‌نشده',
            self::Pending => 'در انتظار',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
            self::Refunded => 'بازگشت کامل',
            self::PartiallyRefunded => 'بازگشت بخشی',
        };
    }
}
