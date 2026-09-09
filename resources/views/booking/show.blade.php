@php
    $isExpired = $reservation->status === \App\Enums\ReservationStatus::Expired
        || ($reservation->status === \App\Enums\ReservationStatus::PendingPayment && $reservation->expires_at?->isPast());
    $statusLabel = match (true) {
        $isExpired => 'منقضی‌شده',
        $reservation->status === \App\Enums\ReservationStatus::Confirmed => 'تأییدشده',
        $reservation->status === \App\Enums\ReservationStatus::Completed => 'انجام‌شده',
        $reservation->status === \App\Enums\ReservationStatus::Cancelled => 'لغوشده',
        default => 'منتظر پرداخت',
    };
@endphp

<x-layouts.app title="پیگیری رزرو | آرشام باربرشاپ" body-class="booking-page">
    <div class="booking-shell confirmation-shell">
        <header class="booking-header">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">A</span>
                <span>ARSHAM <small>BOOKING</small></span>
            </a>
            <a class="quiet-link" href="{{ route('booking.create') }}">رزرو جدید</a>
        </header>

        <main class="confirmation-card panel">
            <div class="confirmation-icon {{ $isExpired ? 'is-expired' : '' }}">{{ $isExpired ? '×' : '✓' }}</div>
            <p class="eyebrow">کد پیگیری</p>
            <h1 dir="ltr">{{ $reservation->reference }}</h1>
            <span class="status-pill {{ $isExpired ? 'is-expired' : '' }}">{{ $statusLabel }}</span>

            @if (session('payment_error'))
                <p class="form-error" role="alert">{{ session('payment_error') }}</p>
            @elseif (request('payment') === 'success')
                <p class="confirmation-message">پرداخت بیعانه با موفقیت تأیید شد و نوبت شما قطعی است.</p>
            @elseif (request('payment') === 'failed')
                <p class="form-error" role="alert">پرداخت انجام نشد یا توسط درگاه تأیید نشد. اگر مبلغی از حساب شما کسر شده است با پشتیبانی تماس بگیرید.</p>
            @elseif (request('payment') === 'verification-error')
                <p class="form-error" role="alert">ارتباط با زیبال برای تأیید نهایی برقرار نشد. وضعیت پرداخت شما محفوظ است؛ چند دقیقه دیگر دوباره این صفحه را بررسی کنید.</p>
            @elseif (request('payment') === 'review')
                <p class="form-error" role="alert">پرداخت ثبت شده اما زمان رزرو دیگر قابل تثبیت نبود. برای تعیین زمان جایگزین یا بازگشت وجه با مجموعه تماس بگیرید.</p>
            @endif

            @if ($isExpired)
                <p class="confirmation-message">مهلت این رزرو موقت تمام شده و زمان انتخابی آزاد شده است. لطفاً رزرو تازه‌ای ثبت کنید.</p>
            @elseif ($reservation->status === \App\Enums\ReservationStatus::PendingPayment)
                <p class="confirmation-message">زمان انتخابی تا ساعت {{ $reservation->expires_at?->format('H:i') }} برای شما نگه داشته شده است. با پرداخت ۳۰٪ بیعانه، نوبت قطعی می‌شود.</p>
            @else
                <p class="confirmation-message">رزرو شما ثبت شده است. کد پیگیری را تا زمان مراجعه نگه دارید.</p>
            @endif

            <dl class="confirmation-details">
                <div><dt>آرایشگر</dt><dd>{{ $reservation->barber->name }}</dd></div>
                <div><dt>زمان مراجعه</dt><dd>{{ $reservation->starts_at->format('Y/m/d - H:i') }}</dd></div>
                <div><dt>موبایل</dt><dd dir="ltr">{{ $maskedMobile }}</dd></div>
                <div><dt>مبلغ کل</dt><dd>{{ number_format($reservation->total_amount) }} ریال</dd></div>
                <div><dt>بیعانه</dt><dd>{{ number_format($reservation->deposit_amount) }} ریال</dd></div>
            </dl>

            @if ($reservation->services->isNotEmpty())
                <div class="confirmation-services">
                    <span>خدمات انتخاب‌شده</span>
                    <p>{{ $reservation->services->pluck('pivot.name_snapshot')->join('، ') }}</p>
                </div>
            @endif

            @if ($isExpired)
                <a class="button" href="{{ route('booking.create') }}">انتخاب زمان جدید</a>
            @elseif ($reservation->status === \App\Enums\ReservationStatus::PendingPayment)
                <form method="POST" action="{{ route('booking.payments.zibal.start', $reservation) }}">
                    @csrf
                    <button class="button" type="submit">پرداخت {{ number_format($reservation->deposit_amount) }} ریال با زیبال</button>
                </form>
            @endif
        </main>
    </div>
</x-layouts.app>
