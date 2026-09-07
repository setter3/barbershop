<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('bookings:expire-holds')]
#[Description('Expire unpaid reservation holds and release their slot claims.')]
class ExpireBookingHolds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reservationIds = Reservation::query()
            ->where('status', ReservationStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id');
        $expiredCount = 0;

        foreach ($reservationIds as $reservationId) {
            $wasExpired = DB::transaction(function () use ($reservationId): bool {
                $reservation = Reservation::query()->lockForUpdate()->find($reservationId);

                if (! $reservation
                    || $reservation->status !== ReservationStatus::PendingPayment
                    || ! $reservation->expires_at?->isPast()) {
                    return false;
                }

                $reservation->slotClaims()->delete();
                $reservation->update(['status' => ReservationStatus::Expired]);

                return true;
            }, 3);

            $expiredCount += (int) $wasExpired;
        }

        $this->info("Expired {$expiredCount} booking hold(s).");

        return self::SUCCESS;
    }
}
