<x-layouts.app title="ورود مدیریت | آرشام باربرشاپ" body-class="auth-page">
    <main class="auth-shell">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark">A</span>
            <span>ARSHAM <small>ADMIN</small></span>
        </a>

        <section class="panel auth-panel">
            <p class="eyebrow">بخش مدیریت</p>
            <h1>ورود امن</h1>

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <label for="email">ایمیل</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror

                <label for="password">رمز عبور</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">

                <label class="checkbox-row">
                    <input name="remember" type="checkbox" value="1">
                    <span>مرا به خاطر بسپار</span>
                </label>

                <button class="button" type="submit">ورود به داشبورد</button>
            </form>
        </section>
    </main>
</x-layouts.app>
