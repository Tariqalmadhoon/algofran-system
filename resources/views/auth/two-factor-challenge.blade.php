<x-guest-shell title="التحقق بخطوتين">
    <div class="mb-6 text-center">
        <p class="text-sm font-extrabold text-emerald-800">التحقق بخطوتين</p>
        <p class="mt-2 text-sm leading-6 text-slate-500">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال الدخول.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="form-label" for="code">رمز تطبيق المصادقة</label>
            <input class="form-input text-center text-xl tracking-[.35em]" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus>
            <x-input-error :messages="$errors->get('code')" />
        </div>
        <button class="btn-primary w-full" type="submit">تحقق ودخول</button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>أو<span class="h-px flex-1 bg-slate-200"></span></div>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="form-label" for="recovery_code">رمز استعادة احتياطي</label>
            <input class="form-input font-mono" id="recovery_code" name="recovery_code" autocomplete="one-time-code" dir="ltr">
            <x-input-error :messages="$errors->get('recovery_code')" />
        </div>
        <button class="btn-secondary w-full" type="submit">استخدام رمز الاستعادة</button>
    </form>

    <a class="mt-5 block text-center text-xs font-bold text-slate-500 hover:text-emerald-700" href="{{ route('login') }}">العودة إلى تسجيل الدخول</a>
</x-guest-shell>
