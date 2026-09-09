<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateReservationRequest;
use App\Services\Booking\ReservationCreator;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateReservationRequest $request, ReservationCreator $creator): JsonResponse
    {
        $reservation = $creator->create($request->validated());

        return response()->json([
            'data' => [
                'reference' => $reservation->reference,
                'status' => $reservation->status->value,
                'payment_status' => $reservation->payment_status->value,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
                'expires_at' => $reservation->expires_at?->toIso8601String(),
                'total_amount' => $reservation->total_amount,
                'deposit_percentage' => $reservation->deposit_percentage,
                'deposit_amount' => $reservation->deposit_amount,
                'currency' => $reservation->currency,
                'redirect_url' => route('booking.show', $reservation),
                'payment_url' => $reservation->status === \App\Enums\ReservationStatus::PendingPayment
                    ? route('booking.payments.zibal.start', $reservation)
                    : null,
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}
