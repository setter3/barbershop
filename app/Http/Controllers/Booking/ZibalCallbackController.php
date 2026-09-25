<?php

namespace App\Http\Controllers\Booking;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\Payments\ZibalGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZibalCallbackController extends Controller
{
    public function __invoke(Request $request, ZibalGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'trackId' => ['required', 'integer'],
            'success' => ['required', 'in:0,1'],
            'status' => ['nullable', 'integer'],
            'orderId' => ['nullable', 'string', 'max:64'],
        ]);

        $payment = Payment::query()
            ->where('provider', 'zibal')
            ->where('authority', (string) $validated['trackId'])
            ->with('reservation')
            ->firstOrFail();
        $reservation = $payment->reservation;

        if (isset($validated['orderId']) && $validated['orderId'] !== $reservation->reference) {
            abort(404);
        }

        if ($payment->status === PaymentStatus::Paid) {
            return $this->toReservation(
                $reservation,
                $reservation->status === ReservationStatus::Confirmed ? 'success' : 'review',
            );
        }

        if ((string) $validated['success'] !== '1') {
            $this->markFailed($payment, $validated);

            return $this->toReservation($reservation, 'failed');
        }

        try {
            $verification = $gateway->verify((string) $payment->authority);

            if ((int) ($verification['result'] ?? 0) === 201
                || ! array_key_exists('amount', $verification)) {
                $verification = $gateway->inquiry((string) $payment->authority);
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->toReservation($reservation, 'verification-error');
        }

        $isPaid = (int) ($verification['result'] ?? 0) === 100
            && (int) ($verification['status'] ?? 0) === 1;
        $amountMatches = (int) ($verification['amount'] ?? 0) === $payment->amount;
        $orderMatches = ! isset($verification['orderId'])
            || (string) $verification['orderId'] === $reservation->reference;

        if (! $isPaid) {
            Log::warning('Zibal verification did not match the local payment.', [
                'payment_id' => $payment->getKey(),
                'result' => $verification['result'] ?? null,
                'status' => $verification['status'] ?? null,
                'amount_matches' => $amountMatches,
                'order_matches' => $orderMatches,
            ]);
            $this->markFailed($payment, $validated, $verification);

            return $this->toReservation($reservation, 'failed');
        }

        if (! $amountMatches || ! $orderMatches) {
            Log::critical('Zibal reported a paid transaction that requires manual review.', [
                'payment_id' => $payment->getKey(),
                'amount_matches' => $amountMatches,
                'order_matches' => $orderMatches,
            ]);
            $this->markPaidForReview($payment, $validated, $verification);

            return $this->toReservation($reservation, 'review');
        }

        $needsReview = DB::transaction(function () use ($payment, $validated, $verification): bool {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status === PaymentStatus::Paid) {
                return false;
            }

            $reservation = Reservation::query()->lockForUpdate()->findOrFail($payment->reservation_id);
            $hasProtectedSlot = $reservation->slotClaims()->exists();
            $canConfirm = $reservation->status === ReservationStatus::Confirmed
                || ($reservation->status === ReservationStatus::PendingPayment && $hasProtectedSlot);

            $payment->update([
                'status' => PaymentStatus::Paid,
                'transaction_id' => isset($verification['refNumber']) ? (string) $verification['refNumber'] : null,
                'paid_at' => $this->paymentTime($verification),
                'payload' => [
                    ...($payment->payload ?? []),
                    'callback' => $validated,
                    'verification' => $verification,
                ],
            ]);

            if ($canConfirm) {
                $reservation->update([
                    'status' => ReservationStatus::Confirmed,
                    'payment_status' => PaymentStatus::Paid,
                    'expires_at' => null,
                ]);
                $reservation->slotClaims()->update(['expires_at' => null]);

                return false;
            }

            $reservation->update([
                'status' => ReservationStatus::Cancelled,
                'payment_status' => PaymentStatus::Paid,
                'expires_at' => null,
            ]);

            return true;
        }, 3);

        return $this->toReservation($reservation, $needsReview ? 'review' : 'success');
    }

    /**
     * @param  array<string, mixed>  $callback
     * @param  array<string, mixed>  $verification
     */
    private function markPaidForReview(Payment $payment, array $callback, array $verification): void
    {
        DB::transaction(function () use ($payment, $callback, $verification): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($payment->reservation_id);

            $payment->update([
                'status' => PaymentStatus::Paid,
                'transaction_id' => isset($verification['refNumber']) ? (string) $verification['refNumber'] : null,
                'paid_at' => $this->paymentTime($verification),
                'payload' => [
                    ...($payment->payload ?? []),
                    'callback' => $callback,
                    'verification' => $verification,
                    'requires_review' => true,
                ],
            ]);
            $reservation->update([
                'status' => ReservationStatus::Cancelled,
                'payment_status' => PaymentStatus::Paid,
                'expires_at' => null,
            ]);
            $reservation->slotClaims()->delete();
        }, 3);
    }

    /** @param array<string, mixed> $callback */
    private function markFailed(Payment $payment, array $callback, ?array $verification = null): void
    {
        DB::transaction(function () use ($payment, $callback, $verification): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'payload' => [
                    ...($payment->payload ?? []),
                    'callback' => $callback,
                    ...($verification ? ['verification' => $verification] : []),
                ],
            ]);

            $payment->reservation()->where('status', ReservationStatus::PendingPayment->value)
                ->update(['payment_status' => PaymentStatus::Failed]);
        }, 3);
    }

    private function toReservation(Reservation $reservation, string $result): RedirectResponse
    {
        return redirect()->route('booking.show', [
            'reservation' => $reservation,
            'payment' => $result,
        ]);
    }

    /** @param array<string, mixed> $verification */
    private function paymentTime(array $verification): CarbonImmutable
    {
        $timezone = (string) config('app.timezone');
        $paidAt = $verification['paidAt'] ?? null;

        if (is_string($paidAt) && trim($paidAt) !== '') {
            try {
                return CarbonImmutable::parse($paidAt, $timezone)->setTimezone($timezone);
            } catch (Throwable $exception) {
                Log::warning('Zibal returned an invalid paidAt value.', [
                    'paid_at' => $paidAt,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return CarbonImmutable::now($timezone);
    }
}
