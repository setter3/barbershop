<x-layouts.admin title="خدمات" heading="منوی خدمات" eyebrow="قیمت و زمان">
    <div class="admin-page-actions"><p>خدمات جانبی، قیمت و مدت زمانی که به نوبت اضافه می‌شود.</p><a class="button" href="{{ route('admin.services.create') }}">افزودن خدمت</a></div>
    <section class="admin-section panel">
        @if ($services->isEmpty())
            <div class="admin-empty"><strong>هنوز خدمتی ثبت نشده</strong><span>خدمت اول را برای نمایش در مسیر رزرو اضافه کنید.</span></div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>خدمت</th><th>قیمت</th><th>زمان افزوده</th><th>آرایشگرها</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td><strong>{{ $service->name }}</strong><small>{{ $service->description }}</small></td>
                                <td>{{ number_format($service->price_amount) }} ریال</td>
                                <td>{{ $service->duration_minutes }} دقیقه</td>
                                <td>{{ $service->barbers_count }}</td>
                                <td><span class="admin-status {{ $service->is_active ? 'is-confirmed' : 'is-cancelled' }}">{{ $service->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                                <td><a class="row-link" href="{{ route('admin.services.edit', $service) }}">ویرایش</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
