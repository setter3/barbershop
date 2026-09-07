@props(['title', 'heading', 'eyebrow' => 'مرکز عملیات'])

<x-layouts.app :title="$title.' | مدیریت آرشام'" body-class="admin-page">
    <div class="admin-app">
        <aside class="admin-sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark">A</span>
                <span>ARSHAM <small>CONTROL ROOM</small></span>
            </a>

            <nav class="admin-nav" aria-label="منوی مدیریت">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><span>01</span> نمای کلی</a>
                <a class="{{ request()->routeIs('admin.reservations.*') ? 'is-active' : '' }}" href="{{ route('admin.reservations.index') }}"><span>02</span> رزروها</a>
                <a class="{{ request()->routeIs('admin.barbers.*') ? 'is-active' : '' }}" href="{{ route('admin.barbers.index') }}"><span>03</span> آرایشگرها</a>
                <a class="{{ request()->routeIs('admin.services.*') ? 'is-active' : '' }}" href="{{ route('admin.services.index') }}"><span>04</span> خدمات</a>
                <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}"><span>05</span> تنظیمات</a>
            </nav>

            <div class="admin-sidebar-footer">
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="text-button" type="submit">خروج امن</button>
                </form>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">{{ $eyebrow }}</p>
                    <h1>{{ $heading }}</h1>
                </div>
                <a class="admin-site-link" href="{{ route('home') }}" target="_blank">مشاهده سایت <span>↗</span></a>
            </header>

            @if (session('success'))
                <div class="admin-alert is-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="admin-alert is-error">
                    <strong>ذخیره انجام نشد:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <main class="admin-content">{{ $slot }}</main>
        </div>
    </div>
</x-layouts.app>
