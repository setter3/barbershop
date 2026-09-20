<?php

namespace App\Services\Booking;

use App\Enums\ReservationStatus;
use App\Models\Barber;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(private BusinessSettings $settings) {}

    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function forDate(Barber $barber, CarbonImmutable $date, int $durationMinutes): Collection
    {
        $timezone = (string) config('app.timezone');
        $day = $date->setTimezone($timezone)->startOfDay();
        $dayEnd = $day->endOfDay();
        $now = CarbonImmutable::now($timezone);

        $schedules = $barber->schedules()
            ->where('weekday', $day->dayOfWeek)
            ->where('is_active', true)
            ->orderBy('starts_at')
            ->get();

        $timeOffs = $barber->timeOffs()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $day)
            ->get();

        $reservations = $barber->reservations()
            ->whereIn('status', ReservationStatus::blockingValues())
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $day)
            ->where(function ($query) use ($now): void {
                $query->where('status', '!=', ReservationStatus::PendingPayment->value)
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->get();

        $claims = $barber->slotClaims()
            ->where('slot_start', '>=', $day)
            ->where('slot_start', '<=', $dayEnd)
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->get();

        // Each barber owns their booking cadence. The global setting is only a
        // fallback for legacy records that do not have a valid duration.
        $stepMinutes = max(1, (int) ($barber->slot_duration_minutes ?: $this->settings->slotDurationMinutes()));
        $slots = collect();

        foreach ($schedules as $schedule) {
            $cursor = CarbonImmutable::parse($day->toDateString().' '.$schedule->starts_at, $timezone);
            $windowEnd = CarbonImmutable::parse($day->toDateString().' '.$schedule->ends_at, $timezone);

            while ($cursor->addMinutes($durationMinutes)->lessThanOrEqualTo($windowEnd)) {
                $endsAt = $cursor->addMinutes($durationMinutes);

                if ($cursor->greaterThan($now)
                    && ! $timeOffs->contains(fn ($timeOff): bool => $this->overlaps($cursor, $endsAt, $timeOff->starts_at, $timeOff->ends_at))
                    && ! $reservations->contains(fn ($reservation): bool => $this->overlaps($cursor, $endsAt, $reservation->starts_at, $reservation->ends_at))
                    && ! $claims->contains(fn ($claim): bool => $claim->slot_start->greaterThanOrEqualTo($cursor) && $claim->slot_start->lessThan($endsAt))) {
                    $slots->push($cursor);
                }

                $cursor = $cursor->addMinutes($stepMinutes);
            }
        }

        return $slots->unique(fn (CarbonImmutable $slot): string => $slot->toIso8601String())->sort()->values();
    }

    public function isAvailable(Barber $barber, CarbonImmutable $startsAt, int $durationMinutes): bool
    {
        return $this->forDate($barber, $startsAt, $durationMinutes)
            ->contains(fn (CarbonImmutable $slot): bool => $slot->equalTo($startsAt));
    }

    private function overlaps(CarbonInterface $startsAt, CarbonInterface $endsAt, CarbonInterface $otherStartsAt, CarbonInterface $otherEndsAt): bool
    {
        return $startsAt->lessThan($otherEndsAt) && $endsAt->greaterThan($otherStartsAt);
    }
}
