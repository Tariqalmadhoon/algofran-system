<x-guest-shell title="تعيين كلمة مرور جديدة">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div>
            <label class="form-label" for="password">كلمة المرور الجديدة</label>
            <input class="form-input" id="password" name="password" type="password" autocomplete="new-password" required>
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div>
            <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
            <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="btn-primary w-full" type="submit">حفظ كلمة المرور</button>
    </form>
</x-guest-shell>
