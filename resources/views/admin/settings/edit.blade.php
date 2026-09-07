<x-layouts.admin title="تنظیمات" heading="تنظیمات کسب‌وکار" eyebrow="نسخه قابل انتشار">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <div class="admin-form-grid">
            <section class="admin-form-section panel">
                <div class="admin-section-head"><div><p class="eyebrow">هویت و تماس</p><h2>اطلاعات عمومی</h2></div></div>
                <label class="field"><span>نام سایت</span><input name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'آرشام باربرشاپ') }}" required></label>
                <label class="field"><span>شماره تماس</span><input name="contact_phone" dir="ltr" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"></label>
                <label class="field"><span>آدرس</span><textarea name="address" rows="4">{{ old('address', $settings['address'] ?? '') }}</textarea></label>
            </section>
            <section class="admin-form-section panel">
                <div class="admin-section-head"><div><p class="eyebrow">قیمت‌گذاری</p><h2>رزرو و بیعانه</h2></div></div>
                <label class="field"><span>قیمت پایه به ریال</span><input name="base_price" type="number" min="0" value="{{ old('base_price', $settings['base_price'] ?? 0) }}" required></label>
                <label class="field"><span>درصد بیعانه</span><input name="deposit_percentage" type="number" min="0" max="100" value="{{ old('deposit_percentage', $settings['deposit_percentage'] ?? 30) }}" required></label>
                <label class="field"><span>فاصله شبکه زمانی به دقیقه</span><input name="slot_duration_minutes" type="number" min="5" value="{{ old('slot_duration_minutes', $settings['slot_duration_minutes'] ?? 30) }}" required></label>
                <label class="field"><span>مهلت پرداخت آنلاین به دقیقه</span><input name="hold_minutes" type="number" min="3" value="{{ old('hold_minutes', $settings['hold_minutes'] ?? 10) }}" required></label>
            </section>
            <section class="admin-form-section panel admin-form-wide">
                <div class="admin-section-head"><div><p class="eyebrow">نحوه تأیید</p><h2>حالت ثبت رزرو</h2></div></div>
                <div class="choice-cards">
                    <label><input name="booking_mode" type="radio" value="manual_confirmation" @checked(old('booking_mode', $settings['booking_mode'] ?? 'manual_confirmation') === 'manual_confirmation')><span><strong>ثبت قطعی بدون درگاه</strong><small>پیشنهاد نسخه اولیه؛ رزرو بلافاصله قطعی می‌شود و مدیر پرداخت را پیگیری می‌کند.</small></span></label>
                    <label><input name="booking_mode" type="radio" value="online_deposit" @checked(old('booking_mode', $settings['booking_mode'] ?? '') === 'online_deposit')><span><strong>بیعانه آنلاین</strong><small>پس از اتصال درگاه فعال کنید؛ تا پرداخت، زمان فقط موقت نگه داشته می‌شود.</small></span></label>
                </div>
            </section>
        </div>
        <div class="sticky-save"><span class="form-hint">تغییرات روی رزروهای جدید اعمال می‌شود.</span><button class="button" type="submit">ذخیره تنظیمات</button></div>
    </form>
</x-layouts.admin>
