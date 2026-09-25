<x-layouts.admin title="رزروها" heading="دفتر رزروها" eyebrow="جست‌وجو و پیگیری">
    <form class="filter-bar panel" method="GET" action="{{ route('admin.reservations.index') }}">
        <label class="field"><span>جست‌وجوی مشتری یا کد</span><input name="search" value="{{ request('search') }}" placeholder="نام، موبایل یا کد پیگیری"></label>
        <label class="field"><span>تاریخ شمسی</span><input name="date" inputmode="numeric" value="{{ request('date') }}" placeholder="۱۴۰۵/۰۱/۱۵"></label>
        <label class="field"><span>آرایشگر</span><select name="barber_id"><option value="">همه</option>@foreach($barbers as $barber)<option value="{{ $barber->id }}" @selected((string) request('barber_id') === (string) $barber->id)>{{ $barber->name }}</option>@endforeach</select></label>
        <label class="field"><span>وضعیت</span><select name="status"><option value="">همه</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
        <button class="button" type="submit">اعمال فیلتر</button>
        @if(request()->hasAny(['search','date','barber_id','status']))<a class="quiet-link" href="{{ route('admin.reservations.index') }}">پاک‌کردن</a>@endif
    </form>

    <section class="admin-section panel">
        @if ($reservations->isEmpty())
            <div class="admin-empty"><strong>رزروی پیدا نشد</strong><span>فیلترها را تغییر دهید یا منتظر رزرو جدید بمانید.</span></div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>مشتری</th><th>آرایشگر</th><th>تاریخ و ساعت نوبت</th><th>تاریخ و ساعت پرداخت</th><th>مبلغ</th><th>رزرو</th><th>پرداخت</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td><strong>{{ $reservation->customer->full_name }}</strong><small dir="ltr">{{ $reservation->customer->mobile }}</small></td>
                            <td>{{ $reservation->barber->name }}</td>
                            <td>{{ \App\Support\JalaliDate::format($reservation->starts_at) }}</td>
                            <td>
                                @if($reservation->firstPaidPayment?->paid_at)
                                    {{ \App\Support\JalaliDate::format($reservation->firstPaidPayment->paid_at) }}
                                @elseif($reservation->payment_status === \App\Enums\PaymentStatus::Paid)
                                    <small>ثبت نشده</small>
                                @else
                                    <small>—</small>
                                @endif
                            </td>
                            <td>{{ number_format($reservation->total_amount) }} <small>ریال</small></td>
                            <td><span class="admin-status is-{{ $reservation->status->value }}">{{ $reservation->status->label() }}</span></td>
                            <td><span class="admin-status is-{{ $reservation->payment_status->value }}">{{ $reservation->payment_status->label() }}</span></td>
                            <td><a class="row-link" href="{{ route('admin.reservations.show', $reservation) }}">مدیریت</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="simple-pagination">
                @if ($reservations->previousPageUrl())<a href="{{ $reservations->previousPageUrl() }}">صفحه قبل</a>@else<span></span>@endif
                <span>صفحه {{ $reservations->currentPage() }}</span>
                @if ($reservations->nextPageUrl())<a href="{{ $reservations->nextPageUrl() }}">صفحه بعد</a>@endif
            </div>
        @endif
    </section>
</x-layouts.admin>
