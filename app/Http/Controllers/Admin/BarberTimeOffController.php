<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TimeOffRequest;
use App\Models\Barber;
use App\Models\BarberTimeOff;
use Illuminate\Http\RedirectResponse;

class BarberTimeOffController extends Controller
{
    public function store(TimeOffRequest $request, Barber $barber): RedirectResponse
    {
        $barber->timeOffs()->create($request->safe()->only(['starts_at', 'ends_at', 'reason']));

        return back()->with('success', 'بازه عدم حضور ثبت شد.');
    }

    public function destroy(Barber $barber, BarberTimeOff $timeOff): RedirectResponse
    {
        abort_unless($timeOff->barber_id === $barber->getKey(), 404);
        $timeOff->delete();

        return back()->with('success', 'بازه عدم حضور حذف شد.');
    }
}
