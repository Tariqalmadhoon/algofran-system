@php
    $roleLabels = [
        'super-admin' => 'مدير النظام', 'center-manager' => 'مدير مركز', 'academic-supervisor' => 'مشرف أكاديمي',
        'registrar' => 'مسجل', 'teacher' => 'محفظ', 'student' => 'طالب', 'guardian' => 'ولي أمر',
        'website-editor' => 'محرر الموقع', 'report-viewer' => 'متابع تقارير',
    ];
    $roleName = $user->getRoleNames()->first();
    $roleLabel = $roleLabels[$roleName] ?? 'مستخدم النظام';
    $nameParts = preg_split('/\s+/u', trim($user->name));
    $initials = collect($nameParts)->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('');
    $avatarUrl = $user->avatar ? route('private-files.preview', $user->avatar) : null;
@endphp

<x-app-shell title="الملف الشخصي">
    <div class="space-y-6">
        <x-flash-messages inline consume />

        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-700 p-6 text-white shadow-[0_28px_70px_-38px_rgba(6,78,59,.85)] sm:p-8">
            <div class="absolute -left-16 -top-20 -z-10 size-64 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -z-10 text-white/[.045]"><x-islamic-icon name="mosque" class="size-64 translate-y-20" /></div>
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <div class="relative size-28 shrink-0">
                    <div class="grid size-full place-items-center overflow-hidden rounded-[1.75rem] border-4 border-white/15 bg-white/10 text-3xl font-black shadow-2xl backdrop-blur">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="صورة {{ $user->name }}" class="size-full object-cover">
                        @else
                            {{ $initials }}
                        @endif
                    </div>
                    <span class="absolute -bottom-1 -left-1 grid size-8 place-items-center rounded-xl border-2 border-emerald-900 bg-emerald-300 text-emerald-950"><x-islamic-icon name="star" class="size-4" /></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-black tracking-widest text-emerald-200">حسابي في {{ config('app.name') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">{{ $user->name }}</h1>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-emerald-50/80">
                        <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">{{ $roleLabel }}</span>
                        <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">{{ $user->email }}</span>
                        <span class="flex items-center gap-1.5 rounded-full border border-white/10 bg-white/10 px-3 py-1.5"><span class="size-2 rounded-full bg-emerald-300"></span> الحساب فعال</span>
                    </div>
                </div>
                <a class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-black backdrop-blur hover:-translate-y-0.5 hover:bg-white/15" href="{{ route('profile.security') }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                    أمان الحساب والأجهزة
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_.95fr]">
            <section class="panel overflow-hidden">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div><p class="eyebrow">الهوية الشخصية</p><h2 class="section-title">البيانات الأساسية</h2><p class="page-subtitle">حدّث بيانات التواصل والصورة الظاهرة داخل النظام.</p></div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-nav-icon name="profile" /></span>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5" x-data='avatarPreview(@json($avatarUrl))'>
                    @csrf @method('PATCH')
                    <div class="rounded-3xl border border-dashed border-emerald-200 bg-gradient-to-l from-emerald-50/80 to-white p-4">
                        <div class="flex flex-col items-center gap-4 sm:flex-row">
                            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-3xl bg-emerald-900 text-2xl font-black text-white shadow-lg">
                                <template x-if="preview"><img :src="preview" alt="معاينة الصورة الشخصية" class="size-full object-cover"></template>
                                <template x-if="!preview"><span>{{ $initials }}</span></template>
                            </div>
                            <div class="flex-1 text-center sm:text-right">
                                <p class="font-black text-slate-800">الصورة الشخصية</p>
                                <p class="mt-1 text-xs leading-6 text-slate-500">JPG أو PNG أو WEBP، وبحجم أقصى 3 ميجابايت. يفضّل استخدام صورة مربعة وواضحة.</p>
                                <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                                    <label class="btn-secondary cursor-pointer"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg><span x-text="preview ? 'تغيير الصورة' : 'اختيار صورة'"></span><input x-ref="avatar" @change="select($event)" class="sr-only" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
                                    <button x-show="preview" x-cloak @click="clear()" type="button" class="inline-flex items-center rounded-xl px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50">إزالة الصورة</button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="remove_avatar" :value="remove ? 1 : 0">
                        <x-input-error :messages="$errors->get('avatar')" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="sm:col-span-2"><span class="form-label">الاسم الكامل</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-slate-400"><x-nav-icon name="profile" class="size-5" /></span><input class="form-input pr-11" id="name" name="name" value="{{ old('name', $user->name) }}" required></div><x-input-error :messages="$errors->get('name')" /></label>
                        <label><span class="form-label">البريد الإلكتروني</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 5h18v14H3z"/><path d="m3 6 9 7 9-7"/></svg></span><input class="form-input pr-11" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required dir="ltr"></div><x-input-error :messages="$errors->get('email')" /></label>
                        <label><span class="form-label">رقم التواصل</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-4-2-2 2c-3.5-1.5-6.5-4.5-8-8l2-2-2-4Z"/></svg></span><input class="form-input pr-11" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr"></div><x-input-error :messages="$errors->get('phone')" /></label>
                    </div>
                    <button class="btn-primary w-full sm:w-auto" type="submit"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3m-8 18v-7h8v7"/></svg>حفظ البيانات</button>
                </form>
            </section>

            <section class="panel" x-data='profilePassword(@json(route('profile.password.check')), @json(csrf_token()))'>
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div><p class="eyebrow">حماية الحساب</p><h2 class="section-title">تغيير كلمة المرور</h2><p class="page-subtitle">تحقق فوري وآمن قبل اعتماد كلمة المرور الجديدة.</p></div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-slate-900 text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/></svg></span>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4" @submit="submitting = true">
                    @csrf @method('PUT')
                    <label><span class="form-label">كلمة المرور الحالية</span><div class="relative"><input x-model="current" @input.debounce.650ms="verifyCurrent()" @change="verifyCurrent()" class="form-input pl-12" id="current_password" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" required><button @click="showCurrent = !showCurrent" type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700" :aria-label="showCurrent ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"><svg x-show="!showCurrent" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-show="showCurrent" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m3 3 18 18M10.6 6.1A10 10 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3-.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button></div>
                        <div class="mt-2 min-h-5 text-xs font-bold"><span x-show="currentState === 'checking'" class="text-sky-600">جارٍ التحقق بأمان…</span><span x-show="currentState === 'valid'" x-cloak class="text-emerald-700">✓ كلمة المرور الحالية صحيحة</span><span x-show="currentState === 'invalid'" x-cloak class="text-rose-600">✕ كلمة المرور الحالية غير صحيحة</span><span x-show="currentState === 'error'" x-cloak class="text-amber-700">تعذّر التحقق الآن، وسيتم التحقق عند الحفظ.</span></div>
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                    </label>

                    <label><span class="form-label">كلمة المرور الجديدة</span><div class="relative"><input x-model="password" class="form-input pl-12" id="new_password" name="password" :type="showNew ? 'text' : 'password'" autocomplete="new-password" required><button @click="showNew = !showNew" type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700" :aria-label="showNew ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><x-input-error :messages="$errors->updatePassword->get('password')" /></label>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                        <div class="mb-3 flex items-center justify-between"><span class="text-xs font-black text-slate-600">قوة كلمة المرور</span><span class="text-xs font-black" :class="strengthColor" x-text="strengthLabel"></span></div>
                        <div class="mb-4 grid grid-cols-5 gap-1"><template x-for="index in 5" :key="index"><span class="h-1.5 rounded-full transition-all duration-300" :class="index <= strength ? strengthBar : 'bg-slate-200'"></span></template></div>
                        <div class="grid grid-cols-2 gap-2 text-[11px] font-bold">
                            <span :class="password.length >= 10 ? 'text-emerald-700' : 'text-slate-400'"><b x-text="password.length >= 10 ? '✓' : '○'"></b> 10 رموز على الأقل</span>
                            <span :class="/[a-z]/.test(password) ? 'text-emerald-700' : 'text-slate-400'"><b x-text="/[a-z]/.test(password) ? '✓' : '○'"></b> حرف صغير</span>
                            <span :class="/[A-Z]/.test(password) ? 'text-emerald-700' : 'text-slate-400'"><b x-text="/[A-Z]/.test(password) ? '✓' : '○'"></b> حرف كبير</span>
                            <span :class="/\d/.test(password) ? 'text-emerald-700' : 'text-slate-400'"><b x-text="/\d/.test(password) ? '✓' : '○'"></b> رقم واحد</span>
                            <span :class="/[^A-Za-z0-9]/.test(password) ? 'text-emerald-700' : 'text-slate-400'"><b x-text="/[^A-Za-z0-9]/.test(password) ? '✓' : '○'"></b> رمز خاص</span>
                        </div>
                    </div>

                    <label><span class="form-label">تأكيد كلمة المرور</span><div class="relative"><input x-model="confirmation" class="form-input pl-12" id="password_confirmation" name="password_confirmation" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" required><button @click="showConfirmation = !showConfirmation" type="button" class="absolute inset-y-0 left-2 grid w-9 place-items-center text-slate-400 hover:text-emerald-700"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><p x-show="confirmation" x-cloak class="mt-2 text-xs font-bold" :class="matches ? 'text-emerald-700' : 'text-rose-600'" x-text="matches ? '✓ كلمتا المرور متطابقتان' : '✕ كلمتا المرور غير متطابقتين'"></p></label>

                    <button class="btn-primary w-full" type="submit" :disabled="!ready || submitting"><span x-show="!submitting">اعتماد كلمة المرور الجديدة</span><span x-show="submitting" x-cloak>جارٍ الحفظ…</span></button>
                    <p x-show="!ready" class="text-center text-[11px] text-slate-400">يُفعّل الزر بعد صحة الكلمة الحالية واكتمال جميع المتطلبات.</p>
                </form>
            </section>
        </div>
    </div>

    @push('scripts-before-livewire')
        <script>
            window.avatarPreview = (initialPreview) => ({
                preview: initialPreview,
                remove: false,
                select(event) {
                    const file = event.target.files?.[0];
                    if (!file) return;
                    this.remove = false;
                    const reader = new FileReader();
                    reader.onload = (result) => this.preview = result.target.result;
                    reader.readAsDataURL(file);
                },
                clear() {
                    this.preview = null;
                    this.remove = true;
                    if (this.$refs.avatar) this.$refs.avatar.value = '';
                },
            });

            window.profilePassword = (checkUrl, csrf) => ({
                checkUrl, csrf,
                current: '', password: '', confirmation: '',
                currentState: 'idle', showCurrent: false, showNew: false, showConfirmation: false, submitting: false,
                async verifyCurrent() {
                    if (!this.current) { this.currentState = 'idle'; return; }
                    const value = this.current;
                    this.currentState = 'checking';
                    try {
                        const response = await fetch(this.checkUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                            body: JSON.stringify({ password: value }),
                        });
                        if (!response.ok) throw new Error('password-check-failed');
                        const result = await response.json();
                        if (this.current === value) this.currentState = result.valid ? 'valid' : 'invalid';
                    } catch (error) {
                        if (this.current === value) this.currentState = 'error';
                    }
                },
                get strength() {
                    return [this.password.length >= 10, /[a-z]/.test(this.password), /[A-Z]/.test(this.password), /\d/.test(this.password), /[^A-Za-z0-9]/.test(this.password)].filter(Boolean).length;
                },
                get strengthLabel() { return ['غير مكتملة', 'ضعيفة جدًا', 'ضعيفة', 'متوسطة', 'جيدة', 'قوية'][this.strength]; },
                get strengthColor() { return this.strength < 3 ? 'text-rose-600' : (this.strength < 5 ? 'text-amber-600' : 'text-emerald-700'); },
                get strengthBar() { return this.strength < 3 ? 'bg-rose-500' : (this.strength < 5 ? 'bg-amber-500' : 'bg-emerald-500'); },
                get matches() { return this.password.length > 0 && this.password === this.confirmation; },
                get ready() { return ['valid', 'error'].includes(this.currentState) && this.strength === 5 && this.matches; },
            });
        </script>
    @endpush
</x-app-shell>
