<x-layouts.admin title="ویرایش {{ $barber->name }}" heading="{{ $barber->name }}" eyebrow="پروفایل و دسترس‌پذیری">
    <form method="POST" action="{{ route('admin.barbers.update', $barber) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.barbers._form')
    </form>

    <section class="admin-section panel time-off-section">
        <div class="admin-section-head"><div><p class="eyebrow">مرخصی و مسدودی</p><h2>بازه‌های عدم حضور</h2></div><p>این زمان‌ها بلافاصله از تقویم مشتری حذف می‌شوند.</p></div>
        <form class="inline-admin-form" method="POST" action="{{ route('admin.barbers.time-offs.store', $barber) }}">
            @csrf
            <label class="field"><span>تاریخ شروع (شمسی)</span><input name="starts_on" inputmode="numeric" placeholder="۱۴۰۵/۰۱/۱۵" required></label>
            <label class="field"><span>ساعت شروع</span><input name="starts_time" type="time" required></label>
            <label class="field"><span>تاریخ پایان (شمسی)</span><input name="ends_on" inputmode="numeric" placeholder="۱۴۰۵/۰۱/۱۵" required></label>
            <label class="field"><span>ساعت پایان</span><input name="ends_time" type="time" required></label>
            <label class="field"><span>دلیل</span><input name="reason" placeholder="مثلاً مرخصی"></label>
            <button class="button" type="submit">ثبت بازه</button>
        </form>
        <div class="compact-list">
            @forelse ($barber->timeOffs as $timeOff)
                <div><span><strong>{{ $timeOff->reason ?: 'بدون عنوان' }}</strong><small>{{ \App\Support\JalaliDate::format($timeOff->starts_at) }} ← {{ \App\Support\JalaliDate::format($timeOff->ends_at) }}</small></span><form method="POST" action="{{ route('admin.barbers.time-offs.destroy', [$barber, $timeOff]) }}">@csrf @method('DELETE')<button class="danger-link" type="submit">حذف</button></form></div>
            @empty
                <p class="muted-copy">هیچ بازه‌ای ثبت نشده است.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-section panel danger-zone">
        <div class="admin-section-head"><div><p class="eyebrow">حذف آرایشگر</p><h2>حذف از فهرست تیم</h2></div><p>اگر آرایشگر سابقه رزرو داشته باشد، برای حفظ سوابق حذف امکان‌پذیر نیست.</p></div>
        <form method="POST" action="{{ route('admin.barbers.destroy', $barber) }}" onsubmit="return confirm('این آرایشگر حذف شود؟ این عملیات قابل بازگشت نیست.');">
            @csrf @method('DELETE')
            <button class="button button-danger" type="submit">حذف آرایشگر</button>
        </form>
    </section>
</x-layouts.admin>
