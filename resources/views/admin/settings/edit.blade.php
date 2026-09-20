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
                <div class="admin-section-head"><div><p class="eyebrow">قیمت‌گذاری</p><h2>رزرو و پرداخت کامل</h2></div></div>
                <input name="base_price" type="hidden" value="0">
                <p class="form-hint">مبلغ نهایی از جمع قیمت خدمات انتخاب‌شده محاسبه می‌شود و به‌صورت کامل در درگاه پرداخت خواهد شد.</p>
                <label class="field"><span>فاصله شبکه زمانی به دقیقه</span><input name="slot_duration_minutes" type="number" min="5" value="{{ old('slot_duration_minutes', $settings['slot_duration_minutes'] ?? 30) }}" required></label>
                <label class="field"><span>مهلت پرداخت آنلاین به دقیقه</span><input name="hold_minutes" type="number" min="3" value="{{ old('hold_minutes', $settings['hold_minutes'] ?? 10) }}" required></label>
            </section>
            <section class="admin-form-section panel admin-form-wide">
                <div class="admin-section-head"><div><p class="eyebrow">نحوه تأیید</p><h2>حالت ثبت رزرو</h2></div></div>
                <div class="choice-cards">
                    <label><input name="booking_mode" type="radio" value="manual_confirmation" @checked(old('booking_mode', $settings['booking_mode'] ?? 'manual_confirmation') === 'manual_confirmation')><span><strong>ثبت قطعی بدون درگاه</strong><small>پیشنهاد نسخه اولیه؛ رزرو بلافاصله قطعی می‌شود و مدیر پرداخت را پیگیری می‌کند.</small></span></label>
                    <label><input name="booking_mode" type="radio" value="online_deposit" @checked(old('booking_mode', $settings['booking_mode'] ?? '') === 'online_deposit')><span><strong>پرداخت کامل آنلاین</strong><small>تا تکمیل پرداخت، زمان انتخاب‌شده به‌صورت موقت نگه داشته می‌شود.</small></span></label>
                </div>
            </section>
        </div>
        <div class="sticky-save"><span class="form-hint">تغییرات روی رزروهای جدید اعمال می‌شود.</span><button class="button" type="submit">ذخیره تنظیمات</button></div>
    </form>
</x-layouts.admin>
