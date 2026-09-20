<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReservationUpdateRequest;
use App\Models\Barber;
use App\Models\Reservation;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
            'barber_id' => ['nullable', 'integer', 'exists:barbers,id'],
            'date' => ['nullable', 'string', 'max:10'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $filterDate = $request->filled('date')
                ? JalaliDate::parse((string) $request->input('date'))->toDateString()
                : null;
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['date' => $exception->getMessage()]);
        }

        $reservations = Reservation::query()
            ->with(['barber', 'customer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('barber_id'), fn ($query) => $query->where('barber_id', $request->integer('barber_id')))
            ->when($filterDate, fn ($query) => $query->whereDate('starts_at', $filterDate))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('reference', 'like', $term)
                        ->orWhereHas('customer', fn ($query) => $query->where('full_name', 'like', $term)->orWhere('mobile', 'like', $term));
                });
            })
            ->orderByDesc('starts_at')
            ->simplePaginate(20)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'barbers' => Barber::query()->orderBy('sort_order')->get(),
            'statuses' => ReservationStatus::cases(),
        ]);
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['barber', 'customer', 'services', 'payments']);

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    public function update(ReservationUpdateRequest $request, Reservation $reservation): RedirectResponse
    {
        $status = ReservationStatus::from($request->validated('status'));
        $paymentStatus = PaymentStatus::from($request->validated('payment_status'));
        $this->ensureTransitionIsAllowed($reservation->status, $status);

        DB::transaction(function () use ($request, $reservation, $status, $paymentStatus): void {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            $reservation->update([
                'status' => $status,
                'payment_status' => $paymentStatus,
                'notes' => $request->validated('notes'),
                'expires_at' => $status === ReservationStatus::PendingPayment ? $reservation->expires_at : null,
            ]);

            if ($status->blocksAvailability()) {
                $reservation->slotClaims()->update(['expires_at' => $status === ReservationStatus::PendingPayment ? $reservation->expires_at : null]);
            } else {
                $reservation->slotClaims()->delete();
            }

            if ($paymentStatus === PaymentStatus::Paid) {
                $reservation->payments()->updateOrCreate(['provider' => 'manual'], [
                    'amount' => $reservation->total_amount,
                    'currency' => $reservation->currency,
                    'status' => PaymentStatus::Paid,
                    'transaction_id' => $request->validated('payment_reference'),
                    'paid_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'وضعیت رزرو به‌روزرسانی شد.');
    }

    private function ensureTransitionIsAllowed(ReservationStatus $from, ReservationStatus $to): void
    {
        $allowed = match ($from) {
            ReservationStatus::PendingPayment => [ReservationStatus::PendingPayment, ReservationStatus::Confirmed, ReservationStatus::Cancelled, ReservationStatus::Expired],
            ReservationStatus::Confirmed => [ReservationStatus::Confirmed, ReservationStatus::InProgress, ReservationStatus::Completed, ReservationStatus::Cancelled, ReservationStatus::NoShow],
            ReservationStatus::InProgress => [ReservationStatus::InProgress, ReservationStatus::Completed, ReservationStatus::Cancelled],
            default => [$from],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'این تغییر وضعیت برای رزرو فعلی مجاز نیست.']);
        }
    }
}
