<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Services\Booking\BusinessSettings;
use Illuminate\View\View;

class BookingPageController extends Controller
{
    public function __invoke(BusinessSettings $settings): View
    {
        $barbers = Barber::query()
            ->where('is_active', true)
            ->with(['services' => fn ($query) => $query->where('services.is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Barber $barber): array => [
                'id' => $barber->getKey(),
                'name' => $barber->name,
                'slug' => $barber->slug,
                'bio' => $barber->bio,
                'avatar_url' => $barber->avatar_path ? asset('storage/'.$barber->avatar_path) : null,
                'initial' => mb_substr($barber->name, 0, 1),
                'services' => $barber->services->map(fn ($service): array => [
                    'id' => $service->getKey(),
                    'name' => $service->name,
                    'description' => $service->description,
                    'price_amount' => $service->price_amount,
                    'duration_minutes' => $service->duration_minutes,
                ])->values()->all(),
            ])
            ->values();

        return view('booking.create', [
            'barbers' => $barbers,
            'usesOnlineDeposit' => $settings->usesOnlineDeposit(),
        ]);
    }
}
