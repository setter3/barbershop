<x-layouts.admin title="رزرو {{ $reservation->reference }}" heading="جزئیات رزرو" eyebrow="{{ $reservation->reference }}">
    <div class="admin-form-grid reservation-detail-grid">
        <section class="admin-form-section panel">
            <div class="admin-section-head"><div><p class="eyebrow">مراجعه</p><h2>{{ $reservation->customer->full_name }}</h2></div><span class="admin-status is-{{ $reservation->status->value }}">{{ $reservation->status->label() }}</span></div>
            <dl class="detail-list">
                <div><dt>موبایل</dt><dd dir="ltr">{{ $reservation->customer->mobile }}</dd></div>
                <div><dt>آرایشگر</dt><dd>{{ $reservation->barber->name }}</dd></div>
                <div><dt>شروع</dt><dd>{{ \App\Support\JalaliDate::format($reservation->starts_at) }}</dd></div>
                <div><dt>پایان</dt><dd>{{ \App\Support\JalaliDate::format($reservation->ends_at) }}</dd></div>
                <div><dt>تاریخ و ساعت پرداخت</dt><dd>
                    @if($reservation->firstPaidPayment?->paid_at)
                        {{ \App\Support\JalaliDate::format($reservation->firstPaidPayment->paid_at) }}
                    @elseif($reservation->payment_status === \App\Enums\PaymentStatus::Paid)
                        ثبت نشده
                    @else
                        —
                    @endif
                </dd></div>
                <div><dt>خدمات</dt><dd>{{ $reservation->services->pluck('pivot.name_snapshot')->join('، ') ?: 'فقط خدمت پایه' }}</dd></div>
                <div><dt>مبلغ کل</dt><dd>{{ number_format($reservation->total_amount) }} ریال</dd></div>
                <div><dt>مبلغ پرداخت</dt><dd>{{ number_format($reservation->total_amount) }} ریال</dd></div>
            </dl>
            @if($reservation->notes)<div class="admin-note"><strong>یادداشت مشتری</strong><p>{{ $reservation->notes }}</p></div>@endif
        </section>

        <form class="admin-form-section panel" method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
            @csrf @method('PUT')
            <div class="admin-section-head"><div><p class="eyebrow">عملیات مدیر</p><h2>وضعیت و پرداخت</h2></div></div>
            <label class="field"><span>وضعیت رزرو</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $reservation->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            <label class="field"><span>وضعیت پرداخت</span><select name="payment_status">@foreach($paymentStatuses as $status)<option value="{{ $status->value }}" @selected(old('payment_status', $reservation->payment_status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            <label class="field"><span>شماره پیگیری پرداخت <small>اختیاری</small></span><input name="payment_reference" dir="ltr" value="{{ old('payment_reference', $reservation->payments->last()?->transaction_id) }}"></label>
            <label class="field"><span>یادداشت داخلی / رزرو</span><textarea name="notes" rows="5">{{ old('notes', $reservation->notes) }}</textarea></label>
            <button class="button" type="submit">ثبت تغییرات</button>
            <p class="form-hint">لغو یا تکمیل رزرو، زمان آن را برای رزرو جدید آزاد می‌کند.</p>
        </form>
    </div>
    <a class="quiet-link back-link" href="{{ route('admin.reservations.index') }}">بازگشت به دفتر رزروها</a>
</x-layouts.admin>
