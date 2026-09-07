<x-layouts.app title="رزرو آنلاین | آرشام باربرشاپ" body-class="booking-page">
    <script id="booking-config" type="application/json">
        {!! json_encode([
            'barbers' => $barbers,
            'availabilityUrl' => route('booking.availability'),
            'holdUrl' => route('booking.holds.store'),
            'csrf' => csrf_token(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>

    <div
        class="booking-shell"
        x-data="bookingWizard(JSON.parse(document.getElementById('booking-config').textContent))"
    >
        <header class="booking-header">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">A</span>
                <span>ARSHAM <small>BOOKING</small></span>
            </a>
            <a class="quiet-link" href="{{ route('home') }}">بازگشت به خانه</a>
        </header>

        <div class="booking-intro">
            <div>
                <p class="eyebrow">رزرو آنلاین</p>
                <h1>نوبت شما، دقیق و بدون انتظار</h1>
            </div>
            <p>در چهار قدم کوتاه زمان مناسب خودتان را انتخاب کنید.</p>
        </div>

        <ol class="booking-progress" aria-label="مراحل رزرو">
            <template x-for="item in progress" :key="item.step">
                <li :class="{ 'is-active': step === item.step, 'is-complete': step > item.step }">
                    <span x-text="item.step"></span>
                    <small x-text="item.label"></small>
                </li>
            </template>
        </ol>

        <main class="booking-panel panel">
            <section x-show="step === 1" x-cloak>
                <div class="section-heading">
                    <div><span>۰۱</span><h2>آرایشگر خود را انتخاب کنید</h2></div>
                    <p>هر آرایشگر برنامه و خدمات اختصاصی خود را دارد.</p>
                </div>

                <div class="barber-grid" x-show="barbers.length > 0">
                    <template x-for="barber in barbers" :key="barber.id">
                        <button
                            class="barber-card"
                            type="button"
                            :class="{ 'is-selected': selectedBarberId === barber.id }"
                            @click="selectBarber(barber.id)"
                        >
                            <span class="barber-portrait" :style="barber.avatar_url ? `background-image: url('${barber.avatar_url}')` : ''">
                                <b x-show="!barber.avatar_url" x-text="barber.initial"></b>
                            </span>
                            <span class="barber-info">
                                <small>BARBER</small>
                                <strong x-text="barber.name"></strong>
                                <em x-text="barber.bio || 'متخصص اصلاح و استایل مردانه'"></em>
                            </span>
                            <span class="selection-mark">✓</span>
                        </button>
                    </template>
                </div>

                <div class="booking-empty" x-show="barbers.length === 0">
                    <strong>هنوز آرایشگر فعالی تعریف نشده است.</strong>
                    <p>لطفاً کمی بعد دوباره بررسی کنید یا با مجموعه تماس بگیرید.</p>
                </div>

                <div class="booking-actions">
                    <span></span>
                    <button class="button" type="button" :disabled="!selectedBarberId" @click="step = 2">ادامه و انتخاب خدمات</button>
                </div>
            </section>

            <section x-show="step === 2" x-cloak>
                <div class="section-heading">
                    <div><span>۰۲</span><h2>خدمات دلخواه</h2></div>
                    <p>انتخاب خدمات اضافه اختیاری است و مبلغ نهایی شفاف محاسبه می‌شود.</p>
                </div>

                <div class="service-list" x-show="availableServices.length > 0">
                    <template x-for="service in availableServices" :key="service.id">
                        <label class="service-card" :class="{ 'is-selected': selectedServiceIds.includes(service.id) }">
                            <input type="checkbox" :value="service.id" @change="toggleService(service.id)" :checked="selectedServiceIds.includes(service.id)">
                            <span>
                                <strong x-text="service.name"></strong>
                                <small x-text="service.description || `${service.duration_minutes} دقیقه زمان اضافه`"></small>
                            </span>
                            <b x-text="service.price_amount ? money(service.price_amount) : 'بدون هزینه اضافه'"></b>
                        </label>
                    </template>
                </div>

                <div class="booking-empty compact" x-show="availableServices.length === 0">
                    <p>خدمت اضافه‌ای برای این آرایشگر تعریف نشده است؛ می‌توانید مستقیم زمان را انتخاب کنید.</p>
                </div>

                <div class="booking-actions">
                    <button class="quiet-button" type="button" @click="step = 1">مرحله قبل</button>
                    <button class="button" type="button" @click="step = 3">ادامه و انتخاب زمان</button>
                </div>
            </section>

            <section x-show="step === 3" x-cloak>
                <div class="section-heading">
                    <div><span>۰۳</span><h2>روز و ساعت مناسب</h2></div>
                    <p>ساعت‌ها به‌صورت زنده و بر اساس برنامه آرایشگر نمایش داده می‌شوند.</p>
                </div>

                <div class="date-strip">
                    <template x-for="date in dates" :key="date.value">
                        <button type="button" :class="{ 'is-selected': selectedDate === date.value }" @click="chooseDate(date.value)">
                            <small x-text="date.weekday"></small>
                            <strong x-text="date.day"></strong>
                            <span x-text="date.month"></span>
                        </button>
                    </template>
                </div>

                <div class="availability-state" x-show="loadingSlots">
                    <span class="loader"></span><p>در حال بررسی زمان‌های آزاد…</p>
                </div>

                <div class="time-grid" x-show="!loadingSlots && slots.length > 0">
                    <template x-for="slot in slots" :key="slot">
                        <button type="button" :class="{ 'is-selected': selectedSlot === slot }" @click="selectedSlot = slot" x-text="time(slot)"></button>
                    </template>
                </div>

                <div class="booking-empty compact" x-show="!loadingSlots && selectedDate && slots.length === 0">
                    <p>برای این روز ساعت آزادی باقی نمانده است. روز دیگری را انتخاب کنید.</p>
                </div>

                <p class="form-error" role="alert" x-show="error" x-text="error"></p>

                <div class="quote-bar" x-show="quote">
                    <span>مدت تقریبی: <b x-text="`${durationMinutes} دقیقه`"></b></span>
                    <span>مبلغ بیعانه: <b x-text="quote ? money(quote.deposit_amount) : '—'"></b></span>
                </div>

                <div class="booking-actions">
                    <button class="quiet-button" type="button" @click="step = 2">مرحله قبل</button>
                    <button class="button" type="button" :disabled="!selectedSlot" @click="step = 4">ادامه و ثبت مشخصات</button>
                </div>
            </section>

            <section x-show="step === 4" x-cloak>
                <div class="section-heading">
                    <div><span>۰۴</span><h2>مشخصات و تأیید نهایی</h2></div>
                    <p>برای ثبت نوبت نیازی به ساخت حساب کاربری نیست.</p>
                </div>

                <div class="details-layout">
                    <form class="customer-form" @submit.prevent="submitHold">
                        <label for="full_name">نام و نام خانوادگی</label>
                        <input id="full_name" x-model.trim="customer.full_name" type="text" maxlength="120" required autocomplete="name" placeholder="مثلاً آرشام رضایی">

                        <label for="mobile">شماره موبایل</label>
                        <input id="mobile" x-model.trim="customer.mobile" type="tel" inputmode="numeric" required autocomplete="tel" placeholder="۰۹۱۲۱۲۳۴۵۶۷">

                        <label for="notes">توضیحات اختیاری</label>
                        <textarea id="notes" x-model.trim="customer.notes" maxlength="1000" rows="3" placeholder="اگر نکته‌ای هست اینجا بنویسید"></textarea>

                        <p class="form-error" role="alert" x-show="error" x-text="error"></p>
                    </form>

                    <aside class="booking-summary">
                        <p class="eyebrow">خلاصه رزرو</p>
                        <dl>
                            <div><dt>آرایشگر</dt><dd x-text="selectedBarber?.name"></dd></div>
                            <div><dt>تاریخ</dt><dd x-text="longDate(selectedDate)"></dd></div>
                            <div><dt>ساعت</dt><dd x-text="time(selectedSlot)"></dd></div>
                            <div><dt>مبلغ کل</dt><dd x-text="quote ? money(quote.total_amount) : '—'"></dd></div>
                            <div class="summary-total"><dt>بیعانه قابل پرداخت</dt><dd x-text="quote ? money(quote.deposit_amount) : '—'"></dd></div>
                        </dl>
                    </aside>
                </div>

                <div class="booking-actions">
                    <button class="quiet-button" type="button" @click="step = 3">مرحله قبل</button>
                    <button class="button" type="button" :disabled="submitting || !customer.full_name || !customer.mobile" @click="submitHold">
                        <span x-text="submitting ? 'در حال ثبت…' : 'ثبت رزرو و ادامه پرداخت'"></span>
                    </button>
                </div>
            </section>
        </main>

        <p class="booking-trust">زمان انتخاب‌شده هنگام ثبت، دوباره روی سرور بررسی می‌شود تا رزرو تکراری ایجاد نشود.</p>
    </div>
</x-layouts.app>
