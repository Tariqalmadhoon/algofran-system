<x-guest-shell title="استعادة كلمة المرور">
    <p class="mb-5 text-sm leading-7 text-slate-600">أدخل بريدك الإلكتروني وسنرسل رابطًا آمنًا لإعادة تعيين كلمة المرور إذا كان الحساب مسجلًا.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <button class="btn-primary w-full" type="submit">إرسال رابط الاستعادة</button>
        <a class="block text-center text-sm font-semibold text-emerald-700" href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </form>
</x-guest-shell>
