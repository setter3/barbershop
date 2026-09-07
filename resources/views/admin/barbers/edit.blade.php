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
            <label class="field"><span>شروع</span><input name="starts_at" type="datetime-local" required></label>
            <label class="field"><span>پایان</span><input name="ends_at" type="datetime-local" required></label>
            <label class="field"><span>دلیل</span><input name="reason" placeholder="مثلاً مرخصی"></label>
            <button class="button" type="submit">ثبت بازه</button>
        </form>
        <div class="compact-list">
            @forelse ($barber->timeOffs as $timeOff)
                <div><span><strong>{{ $timeOff->reason ?: 'بدون عنوان' }}</strong><small dir="ltr">{{ $timeOff->starts_at->format('Y/m/d H:i') }} → {{ $timeOff->ends_at->format('Y/m/d H:i') }}</small></span><form method="POST" action="{{ route('admin.barbers.time-offs.destroy', [$barber, $timeOff]) }}">@csrf @method('DELETE')<button class="danger-link" type="submit">حذف</button></form></div>
            @empty
                <p class="muted-copy">هیچ بازه‌ای ثبت نشده است.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
