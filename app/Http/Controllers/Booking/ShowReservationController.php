<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\View\View;

class ShowReservationController extends Controller
{
    public function __invoke(Reservation $reservation): View
    {
        $reservation->load(['barber', 'customer', 'services']);
        $mobile = $reservation->customer->mobile;
        $maskedMobile = mb_substr($mobile, 0, 6).'***'.mb_substr($mobile, -2);

        return view('booking.show', compact('reservation', 'maskedMobile'));
    }
}
