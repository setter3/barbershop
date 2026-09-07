<x-layouts.admin title="آرایشگر جدید" heading="افزودن عضو جدید" eyebrow="تیم و برنامه کاری">
    <form method="POST" action="{{ route('admin.barbers.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.barbers._form')
    </form>
</x-layouts.admin>
