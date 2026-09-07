<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $todayStart = now(config('app.timezone'))->startOfDay();
        $todayEnd = $todayStart->copy()->endOfDay();

        return view('admin.dashboard', [
            'metrics' => [
                'today' => Reservation::query()->whereBetween('starts_at', [$todayStart, $todayEnd])->count(),
                'upcoming' => Reservation::query()->where('starts_at', '>=', now())->whereIn('status', ReservationStatus::blockingValues())->count(),
                'unpaid' => Reservation::query()->whereIn('status', ReservationStatus::blockingValues())->where('payment_status', PaymentStatus::Unpaid)->count(),
                'barbers' => Barber::query()->where('is_active', true)->count(),
                'services' => Service::query()->where('is_active', true)->count(),
            ],
            'upcomingReservations' => Reservation::query()
                ->with(['barber', 'customer'])
                ->where('starts_at', '>=', now())
                ->whereIn('status', ReservationStatus::blockingValues())
                ->orderBy('starts_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
