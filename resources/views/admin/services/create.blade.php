<x-layouts.admin title="خدمت جدید" heading="افزودن خدمت" eyebrow="قیمت و زمان">
    <form method="POST" action="{{ route('admin.services.store') }}">@csrf @include('admin.services._form')</form>
</x-layouts.admin>
