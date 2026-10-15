<x-guest-shell title="تسجيل الدخول">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between">
                <label class="form-label" for="password">كلمة المرور</label>
                <a class="text-xs font-semibold text-emerald-700 hover:text-emerald-900" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
            </div>
            <div class="relative">
                <input class="form-input pl-12" id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" autocomplete="current-password" required>
                <button type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700" x-on:click="showPassword = !showPassword" x-bind:aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                    <svg x-show="!showPassword" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-cloak x-show="showPassword" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m3 3 18 18M10.6 6.1A10 10 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3-.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" name="remember" type="checkbox">
            تذكرني
        </label>
        <button class="btn-primary w-full" type="submit">دخول آمن</button>
    </form>
</x-guest-shell>
