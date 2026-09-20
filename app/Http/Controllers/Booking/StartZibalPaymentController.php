<?php

namespace App\Http\Controllers\Booking;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\Payments\ZibalGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class StartZibalPaymentController extends Controller
{
    public function __invoke(Reservation $reservation, ZibalGateway $gateway): RedirectResponse
    {
        try {
            $paymentUrl = DB::transaction(function () use ($reservation, $gateway): string {
                $reservation = Reservation::query()
                    ->with('customer')
                    ->lockForUpdate()
                    ->findOrFail($reservation->getKey());

                if ($reservation->status === ReservationStatus::Confirmed
                    && $reservation->payment_status === PaymentStatus::Paid) {
                    return route('booking.show', ['reservation' => $reservation, 'payment' => 'success']);
                }

                if ($reservation->status !== ReservationStatus::PendingPayment
                    || ! $reservation->expires_at
                    || $reservation->expires_at->isPast()) {
                    throw new RuntimeException('This reservation is no longer payable.');
                }

                if ($reservation->total_amount <= 1000) {
                    throw new RuntimeException('The payment is below the gateway minimum.');
                }

                if ($reservation->currency !== 'IRR') {
                    throw new RuntimeException('Zibal payments must use IRR.');
                }

                $reservation->payments()
                    ->where('provider', 'zibal')
                    ->where('status', PaymentStatus::Pending)
                    ->where('amount', '!=', $reservation->total_amount)
                    ->update(['status' => PaymentStatus::Failed]);

                $payment = $reservation->payments()
                    ->where('provider', 'zibal')
                    ->where('status', PaymentStatus::Pending)
                    ->where('amount', $reservation->total_amount)
                    ->whereNotNull('authority')
                    ->latest('id')
                    ->first();

                if (! $payment) {
                    $response = $gateway->request(
                        amount: $reservation->total_amount,
                        callbackUrl: route('booking.payments.zibal.callback'),
                        orderId: $reservation->reference,
                        mobile: $reservation->customer->mobile,
                        description: 'پرداخت کامل رزرو آرشام - '.$reservation->reference,
                    );

                    $payment = $reservation->payments()->create([
                        'provider' => 'zibal',
                        'authority' => (string) $response['trackId'],
                        'amount' => $reservation->total_amount,
                        'currency' => $reservation->currency,
                        'status' => PaymentStatus::Pending,
                        'payload' => ['request' => $response],
                    ]);

                    $paymentDeadline = CarbonImmutable::now(config('app.timezone'))
                        ->addMinutes(max(10, (int) config('services.zibal.payment_hold_minutes', 20)));

                    if ($paymentDeadline->isAfter($reservation->expires_at)) {
                        $reservation->update(['expires_at' => $paymentDeadline]);
                        $reservation->slotClaims()->update(['expires_at' => $paymentDeadline]);
                    }

                    $reservation->update(['payment_status' => PaymentStatus::Pending]);
                }

                return $gateway->startUrl((string) $payment->authority);
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'payment_error',
                'اتصال به درگاه پرداخت ممکن نشد. لطفاً دوباره تلاش کنید. کد پیگیری: '
                    .$this->errorCode($exception),
            );
        }

        return redirect()->away($paymentUrl);
    }

    private function errorCode(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return 'ZP-CONNECTION';
        }

        $message = $exception->getMessage();

        if (str_contains($message, 'merchant is not configured')) {
            return 'ZP-CONFIG';
        }

        if (preg_match('/Result: ([\-\d]+)/', $message, $matches) === 1) {
            return 'ZP-RESULT-'.$matches[1];
        }

        if (preg_match('/HTTP (\d+)/', $message, $matches) === 1) {
            return 'ZP-HTTP-'.$matches[1];
        }

        if (str_contains($message, 'no longer payable')) {
            return 'ZP-EXPIRED';
        }

        if (str_contains($message, 'below the gateway minimum')) {
            return 'ZP-AMOUNT';
        }

        if (str_contains($message, 'must use IRR')) {
            return 'ZP-CURRENCY';
        }

        return 'ZP-UNEXPECTED';
    }
}
