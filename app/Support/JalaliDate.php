<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class JalaliDate
{
    public static function format(DateTimeInterface $date, bool $withTime = true): string
    {
        [$year, $month, $day] = self::fromGregorian(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        );

        $formatted = sprintf('%04d/%02d/%02d', $year, $month, $day);

        return $withTime ? $formatted.' — '.$date->format('H:i') : $formatted;
    }

    public static function parse(string $date, ?string $time = null): CarbonImmutable
    {
        $normalized = str_replace(['-', '.'], '/', self::latinDigits(trim($date)));

        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $normalized, $matches) !== 1) {
            throw new InvalidArgumentException('تاریخ شمسی باید مانند ۱۴۰۵/۰۱/۱۵ وارد شود.');
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year < 1200 || $year > 1600 || $month < 1 || $month > 12 || $day < 1 || $day > ($month <= 6 ? 31 : 30)) {
            throw new InvalidArgumentException('تاریخ شمسی واردشده معتبر نیست.');
        }

        [$gregorianYear, $gregorianMonth, $gregorianDay] = self::toGregorian($year, $month, $day);
        if (self::fromGregorian($gregorianYear, $gregorianMonth, $gregorianDay) !== [$year, $month, $day]) {
            throw new InvalidArgumentException('تاریخ شمسی واردشده معتبر نیست.');
        }

        $time = self::latinDigits($time ?: '00:00');

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $timeMatches) !== 1) {
            throw new InvalidArgumentException('ساعت واردشده معتبر نیست.');
        }

        return CarbonImmutable::create(
            $gregorianYear,
            $gregorianMonth,
            $gregorianDay,
            (int) $timeMatches[1],
            (int) $timeMatches[2],
            0,
            config('app.timezone'),
        );
    }

    /** @return array{int, int, int} */
    private static function fromGregorian(int $year, int $month, int $day): array
    {
        $monthDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $adjustedYear = $month > 2 ? $year + 1 : $year;
        $days = 355666 + (365 * $year) + intdiv($adjustedYear + 3, 4)
            - intdiv($adjustedYear + 99, 100) + intdiv($adjustedYear + 399, 400)
            + $day + $monthDays[$month - 1];
        $jalaliYear = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jalaliYear += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jalaliYear += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jalaliMonth = 1 + intdiv($days, 31);
            $jalaliDay = 1 + ($days % 31);
        } else {
            $jalaliMonth = 7 + intdiv($days - 186, 30);
            $jalaliDay = 1 + (($days - 186) % 30);
        }

        return [$jalaliYear, $jalaliMonth, $jalaliDay];
    }

    /** @return array{int, int, int} */
    private static function toGregorian(int $year, int $month, int $day): array
    {
        $year += 1595;
        $days = -355668 + (365 * $year) + (intdiv($year, 33) * 8)
            + intdiv(($year % 33) + 3, 4) + $day
            + ($month < 7 ? ($month - 1) * 31 : (($month - 7) * 30) + 186);
        $gregorianYear = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gregorianYear += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gregorianYear += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gregorianYear += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gregorianDay = $days + 1;
        $leap = ($gregorianYear % 4 === 0 && $gregorianYear % 100 !== 0) || $gregorianYear % 400 === 0;
        $monthDays = [0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gregorianMonth = 1;

        while ($gregorianMonth <= 12 && $gregorianDay > $monthDays[$gregorianMonth]) {
            $gregorianDay -= $monthDays[$gregorianMonth++];
        }

        return [$gregorianYear, $gregorianMonth, $gregorianDay];
    }

    private static function latinDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
