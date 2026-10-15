<x-guest-shell title="تعيين كلمة مرور جديدة">
    <form
        method="POST"
        action="{{ route('password.store') }}"
        class="space-y-5"
        x-data="{
            password: '',
            confirmation: '',
            showPassword: false,
            showConfirmation: false,
            get score() {
                return [this.password.length >= 10, /[a-z]/.test(this.password), /[A-Z]/.test(this.password), /[0-9]/.test(this.password), /[^A-Za-z0-9]/.test(this.password)].filter(Boolean).length;
            },
            get matches() { return this.confirmation.length > 0 && this.password === this.confirmation; }
        }"
    >
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" dir="ltr" autocomplete="email" required>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div>
            <label class="form-label" for="password">كلمة المرور الجديدة</label>
            <div class="relative">
                <input class="form-input pl-12" id="password" name="password" x-model="password" x-bind:type="showPassword ? 'text' : 'password'" autocomplete="new-password" required>
                <button type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700" x-on:click="showPassword = !showPassword" x-bind:aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
            <div class="mt-3 grid grid-cols-5 gap-1" aria-hidden="true"><template x-for="part in 5"><span class="h-1.5 rounded-full transition" x-bind:class="score >= part ? (score >= 5 ? 'bg-emerald-500' : 'bg-amber-400') : 'bg-slate-200'"></span></template></div>
            <p class="mt-2 text-[11px] leading-5 text-slate-500">10 أحرف على الأقل، وتتضمن حرفًا كبيرًا وصغيرًا ورقمًا ورمزًا خاصًا.</p>
        </div>
        <div>
            <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
            <div class="relative">
                <input class="form-input pl-12" id="password_confirmation" name="password_confirmation" x-model="confirmation" x-bind:type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" required>
                <button type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700" x-on:click="showConfirmation = !showConfirmation" x-bind:aria-label="showConfirmation ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <p x-cloak x-show="confirmation" class="mt-2 text-xs font-bold" x-bind:class="matches ? 'text-emerald-700' : 'text-rose-600'" x-text="matches ? '✓ كلمتا المرور متطابقتان' : '✕ كلمتا المرور غير متطابقتين'"></p>
        </div>
        <button class="btn-primary w-full" type="submit">حفظ كلمة المرور</button>
    </form>
</x-guest-shell>
