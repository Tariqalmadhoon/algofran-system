<x-guest-shell title="تسجيل الدخول">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label class="form-label" for="password">كلمة المرور</label>
                <a class="text-xs font-semibold text-emerald-700 hover:text-emerald-900" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
            </div>
            <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required>
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" name="remember" type="checkbox">
            تذكرني
        </label>
        <button class="btn-primary w-full" type="submit">دخول آمن</button>
    </form>
</x-guest-shell>
