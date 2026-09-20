<x-layouts.admin title="آرایشگرها" heading="مدیریت تیم" eyebrow="تیم و برنامه کاری">
    <div class="admin-page-actions"><p>اعضای تیم، خدمات قابل ارائه و ساعت‌های کاری را از این بخش مدیریت کنید.</p><a class="button" href="{{ route('admin.barbers.create') }}">افزودن آرایشگر</a></div>

    <section class="admin-card-grid">
        @forelse ($barbers as $barber)
            <article class="admin-profile-card panel">
                <div class="admin-avatar" @if($barber->avatar_path) style="background-image:url('{{ asset('storage/'.$barber->avatar_path) }}')" @endif><span>{{ mb_substr($barber->name, 0, 1) }}</span></div>
                <div class="admin-profile-copy">
                    <div><span class="admin-status {{ $barber->is_active ? 'is-confirmed' : 'is-cancelled' }}">{{ $barber->is_active ? 'فعال' : 'غیرفعال' }}</span><small>{{ $barber->slot_duration_minutes }} دقیقه‌ای</small></div>
                    <h2>{{ $barber->name }}</h2>
                    <p>{{ $barber->bio ?: 'هنوز معرفی کوتاهی ثبت نشده است.' }}</p>
                    <dl><div><dt>خدمات</dt><dd>{{ $barber->services_count }}</dd></div><div><dt>رزروها</dt><dd>{{ $barber->reservations_count }}</dd></div></dl>
                    <div class="admin-card-actions">
                        <a class="button button-secondary" href="{{ route('admin.barbers.edit', $barber) }}">ویرایش و برنامه کاری</a>
                        <form method="POST" action="{{ route('admin.barbers.destroy', $barber) }}" onsubmit="return confirm('این آرایشگر حذف شود؟');">@csrf @method('DELETE')<button class="danger-link" type="submit">حذف آرایشگر</button></form>
                    </div>
                </div>
            </article>
        @empty
            <div class="admin-empty panel"><strong>هنوز آرایشگری ثبت نشده</strong><span>اولین عضو تیم را اضافه کنید تا رزرو آنلاین فعال شود.</span></div>
        @endforelse
    </section>
</x-layouts.admin>
