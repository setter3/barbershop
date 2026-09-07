<section class="admin-form-section panel narrow-form">
    <div class="admin-section-head"><div><p class="eyebrow">جزئیات خدمت</p><h2>{{ $service->exists ? 'ویرایش خدمت' : 'خدمت جدید' }}</h2></div></div>
    <div class="field-grid two-columns">
        <label class="field"><span>نام خدمت</span><input name="name" value="{{ old('name', $service->name) }}" required></label>
        <label class="field"><span>شناسه لاتین <small>اختیاری</small></span><input name="slug" dir="ltr" value="{{ old('slug', $service->slug) }}" placeholder="beard-design"></label>
        <label class="field"><span>قیمت به ریال</span><input name="price_amount" type="number" min="0" value="{{ old('price_amount', $service->price_amount ?: 0) }}" required></label>
        <label class="field"><span>زمان افزوده به دقیقه</span><input name="duration_minutes" type="number" min="0" value="{{ old('duration_minutes', $service->duration_minutes ?: 0) }}" required></label>
        <label class="field"><span>ترتیب نمایش</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $service->sort_order ?: 0) }}"></label>
    </div>
    <label class="field"><span>توضیح کوتاه</span><textarea name="description" rows="4">{{ old('description', $service->description) }}</textarea></label>
    <input name="is_active" type="hidden" value="0">
    <label class="switch-row"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $service->exists ? $service->is_active : true))><span><strong>فعال و قابل انتخاب</strong><small>غیرفعال‌سازی، سوابق رزروهای قبلی را حذف نمی‌کند.</small></span></label>
</section>
<div class="sticky-save narrow-form"><a class="quiet-link" href="{{ route('admin.services.index') }}">انصراف</a><button class="button" type="submit">ذخیره خدمت</button></div>
