<x-layouts.app title="داشبورد مدیریت | آرشام باربرشاپ" body-class="admin-page">
    <div class="admin-shell">
        <header class="admin-header">
            <div>
                <p class="eyebrow">ARSHAM ADMIN</p>
                <h1>داشبورد مدیریت</h1>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="text-button" type="submit">خروج</button>
            </form>
        </header>

        <main class="panel empty-state">
            <span class="brand-mark">A</span>
            <h2>Foundation آماده است</h2>
            <p>ماژول‌های عملیاتی مدیریت در فازهای رابط کاربری بعدی به این هسته متصل می‌شوند.</p>
        </main>
    </div>
</x-layouts.app>
