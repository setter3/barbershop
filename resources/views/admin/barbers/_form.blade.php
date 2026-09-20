@php
    $weekdays = [6 => 'شنبه', 0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنج‌شنبه', 5 => 'جمعه'];
    $selectedServices = old('service_ids', $barber->exists ? $barber->services->pluck('id')->all() : []);
@endphp

<div class="admin-form-grid">
    <section class="admin-form-section panel">
        <div class="admin-section-head"><div><p class="eyebrow">پروفایل عمومی</p><h2>اطلاعات آرایشگر</h2></div></div>
        <div class="field-grid two-columns">
            <label class="field"><span>نام نمایشی</span><input name="name" value="{{ old('name', $barber->name) }}" required></label>
            <label class="field"><span>شناسه لاتین <small>اختیاری</small></span><input name="slug" dir="ltr" value="{{ old('slug', $barber->slug) }}" placeholder="arsham"></label>
            <label class="field"><span>مدت پایه هر نوبت</span><input name="slot_duration_minutes" type="number" min="10" max="240" value="{{ old('slot_duration_minutes', $barber->slot_duration_minutes ?: 30) }}" required></label>
            <label class="field"><span>ترتیب نمایش</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $barber->sort_order ?: 0) }}"></label>
        </div>
        <label class="field"><span>معرفی کوتاه</span><textarea name="bio" rows="4">{{ old('bio', $barber->bio) }}</textarea></label>
        <label class="field"><span>تصویر پرتره <small>JPG/PNG/WebP تا ۴ مگابایت</small></span><input name="avatar" type="file" accept="image/png,image/jpeg,image/webp"></label>
        <input name="is_active" type="hidden" value="0">
        <label class="switch-row"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $barber->exists ? $barber->is_active : true))><span><strong>نمایش در رزرو آنلاین</strong><small>با غیرفعال‌کردن، رزروهای قبلی حفظ می‌شوند.</small></span></label>
    </section>

    <section class="admin-form-section panel">
        <div class="admin-section-head"><div><p class="eyebrow">منوی خدمات</p><h2>خدمات قابل ارائه</h2></div><a class="quiet-link" href="{{ route('admin.services.index') }}">مدیریت خدمات</a></div>
        <div class="check-list">
            @forelse ($services as $service)
                <label><input name="service_ids[]" type="checkbox" value="{{ $service->id }}" @checked(in_array($service->id, $selectedServices))><span><strong>{{ $service->name }}</strong><small>{{ number_format($service->price_amount) }} ریال · {{ $service->duration_minutes }} دقیقه</small></span></label>
            @empty
                <p class="muted-copy">هنوز خدمتی تعریف نشده است.</p>
            @endforelse
        </div>
    </section>
    <section class="admin-form-section panel admin-form-wide">
        <div class="admin-section-head"><div><p class="eyebrow">تقویم هفتگی</p><h2>ساعت کاری ثابت</h2></div><p>برای هر روز می‌توانید یک بازه استراحت نیز تعیین کنید.</p></div>
        <div class="schedule-grid">
            @foreach ($weekdays as $weekday => $label)
                <div class="schedule-row">
                    <label class="schedule-toggle"><input name="schedules[{{ $weekday }}][is_active]" type="hidden" value="0"><input name="schedules[{{ $weekday }}][is_active]" type="checkbox" value="1" @checked(old("schedules.$weekday.is_active", $schedules[$weekday]['is_active']))><strong>{{ $label }}</strong></label>
                    <label><span>از</span><input name="schedules[{{ $weekday }}][starts_at]" type="time" value="{{ old("schedules.$weekday.starts_at", $schedules[$weekday]['starts_at']) }}"></label>
                    <label><span>تا</span><input name="schedules[{{ $weekday }}][ends_at]" type="time" value="{{ old("schedules.$weekday.ends_at", $schedules[$weekday]['ends_at']) }}"></label>
                    <label><span>شروع استراحت</span><input name="schedules[{{ $weekday }}][break_starts_at]" type="time" value="{{ old("schedules.$weekday.break_starts_at", $schedules[$weekday]['break_starts_at']) }}"></label>
                    <label><span>پایان استراحت</span><input name="schedules[{{ $weekday }}][break_ends_at]" type="time" value="{{ old("schedules.$weekday.break_ends_at", $schedules[$weekday]['break_ends_at']) }}"></label>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="sticky-save"><a class="quiet-link" href="{{ route('admin.barbers.index') }}">انصراف</a><button class="button" type="submit">ذخیره اطلاعات</button></div>
