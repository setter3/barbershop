<x-layouts.admin title="ویرایش {{ $service->name }}" heading="{{ $service->name }}" eyebrow="قیمت و زمان">
    <form method="POST" action="{{ route('admin.services.update', $service) }}">@csrf @method('PUT') @include('admin.services._form')</form>
    <section class="admin-section panel danger-zone narrow-form">
        <div class="admin-section-head"><div><p class="eyebrow">حذف خدمت</p><h2>حذف از منوی خدمات</h2></div><p>خدمتی که در رزروهای قبلی استفاده شده باشد، برای حفظ فاکتورها قابل حذف نیست.</p></div>
        <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('این خدمت حذف شود؟ این عملیات قابل بازگشت نیست.');">
            @csrf @method('DELETE')
            <button class="button button-danger" type="submit">حذف خدمت</button>
        </form>
    </section>
</x-layouts.admin>
