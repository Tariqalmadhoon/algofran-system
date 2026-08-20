<x-guest-shell title="تأكيد كلمة المرور">
    <div class="mb-6 text-center">
        <p class="text-sm font-extrabold text-emerald-800">إعادة مصادقة</p>
        <p class="mt-2 text-sm leading-6 text-slate-500">هذه منطقة حساسة. أكّد كلمة مرورك للمتابعة لمدة 15 دقيقة.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="password">كلمة المرور الحالية</label>
            <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required autofocus>
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <button class="btn-primary w-full" type="submit">تأكيد ومتابعة</button>
    </form>
</x-guest-shell>
