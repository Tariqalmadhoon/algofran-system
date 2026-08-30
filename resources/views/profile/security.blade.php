<x-app-shell title="أمان الحساب">
    @php
        $twoFactorAvailable = (bool) config('system.identity.two_factor_enabled', false);
        $twoFactorEnabled = $twoFactorAvailable && $user->hasEnabledTwoFactorAuthentication();
    @endphp

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">الأمان والوصول</p>
            <h1 class="page-title">أمان الحساب والأجهزة</h1>
            <p class="page-subtitle">أدر الجلسات النشطة والأجهزة المرتبطة بالحساب.</p>
        </div>
        <a class="btn-secondary" href="{{ route('profile.edit') }}">العودة إلى الملف الشخصي</a>
    </div>

    <x-flash-messages inline consume />
    @if($twoFactorAvailable)
        <x-input-error :messages="$errors->get('two_factor')" />
    @endif

    <div @class(['grid gap-6', 'xl:grid-cols-[1.05fr_.95fr]' => $twoFactorAvailable])>
        @if($twoFactorAvailable)
            <section class="panel">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="section-title">المصادقة الثنائية</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">تحمي الحساب برمز متغير من تطبيق TOTP، حتى إذا انكشفت كلمة المرور.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $twoFactorEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $twoFactorEnabled ? 'مفعّلة' : 'غير مكتملة' }}
                </span>
            </div>

            @unless($user->two_factor_secret)
                <form method="POST" action="{{ route('profile.two-factor.enable') }}" class="mt-6">
                    @csrf
                    <button class="btn-primary" type="submit">بدء إعداد المصادقة الثنائية</button>
                </form>
            @endunless

            @if($qrCode)
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5">
                    <h3 class="font-black text-emerald-950">1. امسح الرمز من تطبيق المصادقة</h3>
                    <div class="mt-4 inline-flex rounded-2xl bg-white p-3 shadow-sm">{!! $qrCode !!}</div>
                    <h3 class="mt-5 font-black text-emerald-950">2. أكّد الرمز الحالي</h3>
                    <form method="POST" action="{{ route('profile.two-factor.confirm') }}" class="mt-3 flex flex-col gap-3 sm:flex-row">
                        @csrf
                        <input class="form-input max-w-xs text-center text-lg tracking-[.3em]" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>
                        <button class="btn-primary" type="submit">تأكيد التفعيل</button>
                    </form>
                    <x-input-error :messages="$errors->confirmTwoFactorAuthentication->get('code')" />
                </div>
            @endif

            @if($twoFactorEnabled)
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h3 class="font-black text-slate-900">رموز الاستعادة</h3><p class="mt-1 text-xs leading-5 text-slate-500">احفظها في مدير كلمات مرور. كل رمز يستخدم مرة واحدة.</p></div>
                        <form method="POST" action="{{ route('profile.two-factor.recovery') }}">@csrf<button class="btn-secondary" type="submit">توليد رموز جديدة</button></form>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2" dir="ltr">
                        @foreach($recoveryCodes as $recoveryCode)
                            <code class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm text-slate-700">{{ $recoveryCode }}</code>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('profile.two-factor.rotate') }}" @submit.prevent="$dispatch('app:confirm', { title: 'استبدال تطبيق المصادقة', message: 'سيتم تسجيل خروج الأجهزة الأخرى واستبدال المفتاح الحالي.', confirmLabel: 'استبدال المفتاح', tone: 'warning', action: () => $el.submit() })">
                        @csrf
                        <button class="btn-secondary" type="submit">استبدال تطبيق المصادقة</button>
                    </form>
                    @unless($user->requiresTwoFactorAuthentication())
                        <form method="POST" action="{{ route('profile.two-factor.disable') }}" @submit.prevent="$dispatch('app:confirm', { title: 'تعطيل المصادقة الثنائية', message: 'سيصبح الحساب محميًا بكلمة المرور فقط بعد هذا الإجراء.', confirmLabel: 'تعطيل الحماية', tone: 'danger', action: () => $el.submit() })">
                            @csrf @method('DELETE')
                            <button class="btn-ghost text-rose-700" type="submit">تعطيل المصادقة الثنائية</button>
                        </form>
                    @endunless
                </div>
            @endif
            </section>
        @endif

        <div class="space-y-6">
            <section class="panel">
                <div class="flex items-center justify-between gap-3">
                    <div><h2 class="section-title">جلسات الويب</h2><p class="mt-1 text-sm text-slate-500">الأجهزة التي ما زالت تحمل جلسة دخول.</p></div>
                    <form method="POST" action="{{ route('profile.sessions.destroy-others') }}">@csrf @method('DELETE')<button class="btn-secondary" type="submit">إنهاء الجلسات الأخرى</button></form>
                </div>
                <div class="mt-5 divide-y divide-slate-100">
                    @forelse($sessions as $session)
                        <div class="flex items-center gap-3 py-3">
                            <span class="grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-600"><x-nav-icon name="profile" /></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-black text-slate-800">{{ $session['device'] }}</p><p class="text-xs text-slate-400" dir="ltr">{{ $session['ip_address'] ?? '—' }} · {{ $session['last_active'] }}</p></div>
                            @if($session['current'])<span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-black text-emerald-800">الحالي</span>@endif
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-400">يعرض سجل الجلسات عند استخدام SESSION_DRIVER=database.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel">
                <h2 class="section-title">أجهزة API والجوال</h2>
                <p class="mt-1 text-sm text-slate-500">لا تُعرض الرموز السرية؛ يظهر اسم الجهاز ووقت الاستخدام فقط.</p>
                <div class="mt-5 divide-y divide-slate-100">
                    @forelse($tokens as $token)
                        <div class="py-3"><p class="text-sm font-black text-slate-800">{{ $token->name }}</p><p class="mt-1 text-xs text-slate-400">آخر استخدام: {{ $token->last_used_at?->diffForHumans() ?? 'لم يستخدم بعد' }} · الانتهاء: {{ $token->expires_at?->format('Y-m-d') ?? 'غير محدد' }}</p></div>
                    @empty
                        <p class="py-5 text-sm text-slate-400">لا توجد أجهزة API مرتبطة بالحساب.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-shell>
