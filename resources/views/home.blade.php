<x-layouts.app body-class="home-page">
    <div class="home-glow" aria-hidden="true"></div>

    <div class="site-shell home-shell">
        <header class="site-header" aria-label="ناوبری اصلی">
            <a class="brand" href="{{ route('home') }}" aria-label="آرشام باربرشاپ">
                <span class="brand-mark">A</span>
                <span>ARSHAM <small>BARBERSHOP</small></span>
            </a>

            <div class="header-actions">
                <span class="header-note">رزرو سریع، بدون ساخت حساب</span>
                <a class="header-booking-link" href="{{ route('booking.create') }}">رزرو نوبت <span>←</span></a>
            </div>
        </header>

        <main>
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="eyebrow">PREMIUM GROOMING · TEHRAN</p>
                    <h1 id="hero-title">اصلاح،<br><span>یک هنر است.</span></h1>
                    <p class="lead">تجربه‌ای دقیق و شخصی برای استایل شما؛ آرایشگر دلخواه، روز و ساعت مناسب را انتخاب کنید و نوبتتان را ساده و مطمئن ثبت کنید.</p>

                    <div class="hero-actions">
                        <a class="button button-arrow" href="{{ route('booking.create') }}">
                            <span>رزرو آنلاین</span><b aria-hidden="true">←</b>
                        </a>
                        <a class="button button-quiet" href="#booking-guide">روند رزرو</a>
                    </div>

                    <div class="hero-trust" aria-label="مزیت‌های رزرو">
                        <span><i>✓</i> انتخاب آرایشگر</span>
                        <span><i>✓</i> مشاهده زمان آزاد</span>
                        <span><i>✓</i> تأیید مطمئن</span>
                    </div>
                </div>

                <div class="hero-visual" aria-hidden="true">
                    <div class="hero-frame">
                        <div class="hero-art">
                            <div class="gold-orbit"></div>
                            <div class="monogram">AB</div>
                            <p>EST. 2026</p>
                        </div>
                    </div>
                    <div class="floating-note note-top"><small>ARSHAM</small><strong>دقت در جزئیات</strong></div>
                    <div class="floating-note note-bottom"><span>01</span><p>استایل مخصوص<br>چهره‌ی شما</p></div>
                </div>
            </section>

            <section class="booking-guide" id="booking-guide" aria-labelledby="guide-title">
                <div class="guide-heading">
                    <div>
                        <p class="eyebrow">SIMPLE BOOKING</p>
                        <h2 id="guide-title">نوبت شما، در سه قدم کوتاه</h2>
                    </div>
                    <p>بدون تماس تلفنی و انتظار؛ زمان‌های آزاد را همان لحظه ببینید و انتخاب کنید.</p>
                </div>

                <div class="guide-grid">
                    <article>
                        <span>۰۱</span>
                        <div><strong>آرایشگر</strong><p>متخصص موردنظرتان را انتخاب کنید.</p></div>
                    </article>
                    <article>
                        <span>۰۲</span>
                        <div><strong>خدمت و زمان</strong><p>خدمات، روز و ساعت آزاد را ببینید.</p></div>
                    </article>
                    <article>
                        <span>۰۳</span>
                        <div><strong>ثبت نهایی</strong><p>مشخصات را وارد کنید و نوبت را بسازید.</p></div>
                    </article>
                </div>
            </section>

            <section class="home-cta" aria-label="شروع رزرو">
                <div>
                    <p class="eyebrow">YOUR TIME, YOUR STYLE</p>
                    <h2>برای یک استایل تازه آماده‌اید؟</h2>
                    <p>زمان مناسب خودتان را همین حالا انتخاب کنید.</p>
                </div>
                <a class="button button-arrow" href="{{ route('booking.create') }}"><span>شروع رزرو</span><b aria-hidden="true">←</b></a>
            </section>
        </main>

        <footer class="site-footer">
            <a class="brand footer-brand" href="{{ route('home') }}">
                <span class="brand-mark">A</span>
                <span>ARSHAM <small>BARBERSHOP</small></span>
            </a>
            <div class="footer-features">
                <span>انتخاب آرایشگر</span><i></i><span>زمان‌بندی زنده</span><i></i><span>{{ $usesOnlineDeposit ? 'پرداخت بیعانه' : 'تأیید فوری رزرو' }}</span>
            </div>
            <div class="enamad-seal">
                <a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=7783788&Code=f1gVk32A8hyaXFJDNwj3aArlBYRhwaAS'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=7783788&Code=f1gVk32A8hyaXFJDNwj3aArlBYRhwaAS' alt='' style='cursor:pointer' code='f1gVk32A8hyaXFJDNwj3aArlBYRhwaAS'></a>
            </div>
            <small>ظاهر بهتر، روزهای بهتر.</small>
        </footer>
    </div>
</x-layouts.app>
