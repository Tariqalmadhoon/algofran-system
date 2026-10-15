<x-guest-shell title="استعادة كلمة المرور">
    <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
        <p class="text-sm font-black text-emerald-950">استعادة آمنة عبر البريد الإلكتروني</p>
        <p class="mt-1 text-xs leading-6 text-emerald-800/75">سنرسل رابطًا صالحًا لمدة 60 دقيقة إلى البريد المرتبط بالحساب، دون الكشف عمّا إذا كان البريد مسجلًا.</p>
    </div>
    <form x-data="{ submitting: false }" x-on:submit="submitting = true" method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" dir="ltr" inputmode="email" autocomplete="email" placeholder="name@gmail.com" required autofocus>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <button class="btn-primary w-full" type="submit" x-bind:disabled="submitting">
            <span x-show="!submitting">إرسال رابط الاستعادة</span>
            <span x-cloak x-show="submitting">جارٍ إرسال الرسالة…</span>
        </button>
        <p class="text-center text-xs leading-5 text-slate-400">بعد الإرسال افحص البريد الوارد ومجلد الرسائل غير المرغوب فيها.</p>
        <a class="block text-center text-sm font-semibold text-emerald-700" href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </form>
</x-guest-shell>
