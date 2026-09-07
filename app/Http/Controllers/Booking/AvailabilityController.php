<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AvailabilityRequest;
use App\Models\Barber;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingQuoteCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        AvailabilityRequest $request,
        AvailabilityService $availability,
        BookingQuoteCalculator $quotes,
    ): JsonResponse {
        $data = $request->validated();
        $barber = Barber::query()->where('is_active', true)->findOrFail($data['barber_id']);
        $quote = $quotes->calculate($barber, $data['service_ids'] ?? []);
        $date = CarbonImmutable::parse($data['date'], config('app.timezone'))->startOfDay();
        $slots = $availability->forDate($barber, $date, $quote['duration_minutes']);

        return response()->json([
            'data' => [
                'barber_id' => $barber->getKey(),
                'date' => $date->toDateString(),
                'duration_minutes' => $quote['duration_minutes'],
                'slots' => $slots->map(fn (CarbonImmutable $slot): string => $slot->toIso8601String())->all(),
                'quote' => [
                    'total_amount' => $quote['total_amount'],
                    'deposit_percentage' => $quote['deposit_percentage'],
                    'deposit_amount' => $quote['deposit_amount'],
                    'currency' => $quote['currency'],
                ],
            ],
        ]);
    }
}
