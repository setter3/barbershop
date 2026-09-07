<x-layouts.admin title="ویرایش {{ $service->name }}" heading="{{ $service->name }}" eyebrow="قیمت و زمان">
    <form method="POST" action="{{ route('admin.services.update', $service) }}">@csrf @method('PUT') @include('admin.services._form')</form>
</x-layouts.admin>
