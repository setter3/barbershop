<?php

namespace App\Services\Sms;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Payment;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReservationConfirmationSms
{
    public function __construct(private readonly IpPanelGateway $gateway) {}

    public function sendFor(Payment $payment): void
    {
        try {
            $this->dispatch($payment);
        } catch (Throwable $exception) {
            Log::error('Reservation SMS dispatch stopped unexpectedly.', [
                'payment_id' => $payment->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatch(Payment $payment): void
    {
        if (! $this->gateway->isConfigured()) {
            Log::warning('Reservation SMS was skipped because the service is not fully configured.', [
                'payment_id' => $payment->getKey(),
            ]);

            return;
        }

        $payment = Payment::query()
            ->with(['reservation.customer', 'reservation.barber'])
            ->findOrFail($payment->getKey());
        $reservation = $payment->reservation;

        if ($payment->status !== PaymentStatus::Paid
            || $reservation->status !== ReservationStatus::Confirmed
            || $reservation->payment_status !== PaymentStatus::Paid) {
            return;
        }

        $common = [
            'customer' => $reservation->customer->full_name,
            'date' => JalaliDate::format($reservation->starts_at, false),
            'time' => $reservation->starts_at->format('H:i'),
            'barber' => $reservation->barber->name,
            'reference' => $reservation->reference,
        ];

        $this->sendTo(
            $payment,
            'customer',
            $reservation->customer->mobile,
            (string) config('services.sms.customer_pattern'),
            $common,
        );
        $this->sendTo(
            $payment,
            'owner',
            (string) config('services.sms.owner_mobile'),
            (string) config('services.sms.owner_pattern'),
            [...$common, 'mobile' => $reservation->customer->mobile, 'amount' => (string) $payment->amount],
        );
    }

    /** @param array<string, string> $params */
    private function sendTo(Payment $payment, string $target, string $recipient, string $pattern, array $params): void
    {
        if (! $this->claim($payment->getKey(), $target)) {
            return;
        }

        try {
            $response = $this->gateway->sendPattern($recipient, $pattern, $params);
            $outboxIds = data_get($response, 'data.message_outbox_ids', []);

            $this->record($payment->getKey(), $target, [
                'status' => 'sent',
                'sent_at' => now()->toIso8601String(),
                'message_outbox_ids' => is_array($outboxIds) ? array_values($outboxIds) : [],
            ]);
        } catch (Throwable $exception) {
            $this->record($payment->getKey(), $target, [
                'status' => 'failed',
                'failed_at' => now()->toIso8601String(),
                'error' => $exception->getMessage(),
            ]);
            Log::warning('Reservation confirmation SMS failed.', [
                'payment_id' => $payment->getKey(),
                'target' => $target,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function claim(int $paymentId, string $target): bool
    {
        return DB::transaction(function () use ($paymentId, $target): bool {
            $payment = Payment::query()->lockForUpdate()->findOrFail($paymentId);
            $state = data_get($payment->payload ?? [], "sms.targets.{$target}", []);

            if (($state['status'] ?? null) === 'sent') {
                return false;
            }

            if (($state['status'] ?? null) === 'sending'
                && $this->wasRecentlyClaimed($state['attempted_at'] ?? null)) {
                return false;
            }

            $this->writeState($payment, $target, [
                'status' => 'sending',
                'attempted_at' => now()->toIso8601String(),
                'attempts' => ((int) ($state['attempts'] ?? 0)) + 1,
            ]);

            return true;
        }, 3);
    }

    /** @param array<string, mixed> $state */
    private function record(int $paymentId, string $target, array $state): void
    {
        DB::transaction(function () use ($paymentId, $target, $state): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($paymentId);
            $this->writeState($payment, $target, $state);
        }, 3);
    }

    /** @param array<string, mixed> $state */
    private function writeState(Payment $payment, string $target, array $state): void
    {
        $payload = $payment->payload ?? [];
        $current = data_get($payload, "sms.targets.{$target}", []);
        Arr::set($payload, "sms.targets.{$target}", [...$current, ...$state]);
        $payment->update(['payload' => $payload]);
    }

    private function wasRecentlyClaimed(mixed $attemptedAt): bool
    {
        if (! is_string($attemptedAt) || $attemptedAt === '') {
            return false;
        }

        try {
            return CarbonImmutable::parse($attemptedAt)->isAfter(now()->subMinutes(5));
        } catch (Throwable) {
            return false;
        }
    }
}
