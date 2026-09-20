<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JalaliDateTest extends TestCase
{
    #[Test]
    public function it_formats_and_parses_jalali_dates_with_persian_digits(): void
    {
        $date = CarbonImmutable::parse('2025-03-21 14:30:00', 'Asia/Tehran');

        $this->assertSame('1404/01/01 — 14:30', JalaliDate::format($date));
        $this->assertSame(
            '2025-03-21 14:30:00',
            JalaliDate::parse('۱۴۰۴/۰۱/۰۱', '۱۴:۳۰')->format('Y-m-d H:i:s'),
        );
    }
}
