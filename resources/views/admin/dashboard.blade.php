<x-layouts.admin title="داشبورد" heading="نبض امروز آرشام">
    <section class="metric-grid">
        <article class="metric-card"><span>رزرو امروز</span><strong>{{ number_format($metrics['today']) }}</strong><small>قرار ثبت‌شده برای امروز</small></article>
        <article class="metric-card"><span>رزروهای پیش رو</span><strong>{{ number_format($metrics['upcoming']) }}</strong><small>رزرو فعال از اکنون به بعد</small></article>
        <article class="metric-card"><span>پرداخت‌نشده</span><strong>{{ number_format($metrics['unpaid']) }}</strong><small>نیازمند پیگیری مدیر</small></article>
        <article class="metric-card"><span>تیم فعال</span><strong>{{ number_format($metrics['barbers']) }}</strong><small>{{ number_format($metrics['services']) }} خدمت فعال</small></article>
    </section>

    <section class="admin-section panel">
        <div class="admin-section-head">
            <div><p class="eyebrow">برنامه نزدیک</p><h2>رزروهای پیش رو</h2></div>
            <a class="quiet-link" href="{{ route('admin.reservations.index') }}">مشاهده همه</a>
        </div>

        @if ($upcomingReservations->isEmpty())
            <div class="admin-empty"><strong>رزروی در صف نیست</strong><span>رزروهای جدید مشتریان اینجا ظاهر می‌شوند.</span></div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>مشتری</th><th>آرایشگر</th><th>زمان</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($upcomingReservations as $reservation)
                            <tr>
                                <td><strong>{{ $reservation->customer->full_name }}</strong><small dir="ltr">{{ $reservation->customer->mobile }}</small></td>
                                <td>{{ $reservation->barber->name }}</td>
                                <td>{{ \App\Support\JalaliDate::format($reservation->starts_at) }}</td>
                                <td><span class="admin-status is-{{ $reservation->status->value }}">{{ $reservation->status->label() }}</span></td>
                                <td><a class="row-link" href="{{ route('admin.reservations.show', $reservation) }}">جزئیات</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
