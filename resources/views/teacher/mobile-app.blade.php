<x-app-shell title="تطبيق المحفّظ">
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-bl from-emerald-950 via-emerald-900 to-teal-700 px-6 py-8 text-white shadow-[0_28px_70px_-36px_rgba(6,78,59,.85)] sm:px-8 sm:py-10">
            <div class="absolute -left-16 -top-20 -z-10 size-64 rounded-full bg-emerald-300/15 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-1/3 -z-10 size-64 rounded-full bg-amber-300/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative grid gap-7 lg:grid-cols-[1.2fr_.8fr] lg:items-center">
                <div>
                    <p class="text-xs font-black tracking-widest text-emerald-200">مساحة المحفّظ الخاصة</p>
                    <h1 class="mt-3 text-3xl font-black sm:text-4xl">تطبيق الحلقة على هاتفك</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-emerald-50/80">نزّل النسخة الرسمية إلى هاتفك، ثم سجّل الدخول بحسابك مرة واحدة وأنت متصل بالإنترنت. بعد تنزيل بيانات الحلقة تستطيع تسجيل الحضور والتسميع دون اتصال ومزامنتها عند عودته.</p>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-bold text-emerald-100"><span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">خاص بمحفظي المركز</span><span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Android</span><span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">مزامنة آمنة</span></div>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur sm:p-6">
                    @if($release)
                        <p class="text-xs font-black text-emerald-200">الإصدار المعتمد</p>
                        <p class="mt-2 text-3xl font-black" dir="ltr">{{ $release['version_name'] }}</p>
                        <p class="mt-1 text-sm text-emerald-100/75">بناء {{ $release['version_code'] }} · {{ number_format($release['size_bytes'] / 1024 / 1024, 1) }} MB</p>
                        <a href="{{ route('teacher.mobile.app.download', ['versionCode' => $release['version_code']]) }}" download class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-amber-300 px-4 py-3 text-sm font-black text-emerald-950 transition hover:-translate-y-0.5 hover:bg-amber-200"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M5 15v5h14v-5"/></svg>تحميل التطبيق</a>
                    @else
                        <p class="text-xs font-black text-emerald-200">حالة الإصدار</p>
                        <h2 class="mt-2 text-xl font-black">لا يوجد إصدار متاح الآن</h2>
                        <p class="mt-2 text-sm leading-7 text-emerald-100/75">سيظهر زر التحميل هنا بعد اعتماد الإدارة للإصدار الرسمي.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-3">
            @foreach([
                ['1', 'نزّل التطبيق', 'اضغط زر التحميل من هاتف Android فقط، ثم اسمح للمتصفح بتثبيت التطبيق من هذا المصدر عند طلب النظام ذلك.'],
                ['2', 'سجّل الدخول متصلًا', 'استخدم حساب المحفّظ الرسمي. سينزل التطبيق بيانات حلقتك وطلابك قبل أن تبدأ العمل خارج الاتصال.'],
                ['3', 'سجّل ثم زامن', 'تُحفظ التسجيلات داخل الهاتف أولًا. عند عودة الإنترنت افتح التطبيق أو استخدم زر المزامنة حتى تعتمد السجلات.'],
            ] as [$number, $heading, $description])
                <article class="panel"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100 text-sm font-black text-emerald-800">{{ $number }}</span><h2 class="mt-4 font-black text-emerald-950">{{ $heading }}</h2><p class="mt-2 text-sm leading-7 text-slate-600">{{ $description }}</p></article>
            @endforeach
        </section>

        @if($release)
            <section class="panel border-amber-200 bg-amber-50/60">
                <h2 class="font-black text-amber-950">التحديثات لا تضيع سجلاتك</h2>
                <p class="mt-2 text-sm leading-7 text-amber-950/80">عند وجود إصدار جديد سيظهر لك تنبيه داخل التطبيق بعد تسجيل الدخول. أكمل مزامنة السجلات المعلقة أولًا ثم وافق على التثبيت من Android. لا تحذف التطبيق قبل التأكد من اكتمال المزامنة.</p>
                @if($release['release_notes'])<p class="mt-4 whitespace-pre-line rounded-xl bg-white/70 p-3 text-sm leading-7 text-slate-700">{{ $release['release_notes'] }}</p>@endif
            </section>
        @endif
    </div>
</x-app-shell>
