<x-app-shell title="توزيع تطبيق المحفّظ">
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-bl from-emerald-950 via-emerald-900 to-teal-700 px-6 py-8 text-white shadow-[0_28px_70px_-36px_rgba(6,78,59,.85)] sm:px-8">
            <div class="absolute -left-16 -top-20 size-64 rounded-full bg-emerald-300/15 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-black tracking-widest text-emerald-200">بوابة الإدارة الآمنة</p>
                    <h1 class="mt-3 text-3xl font-black sm:text-4xl">توزيع تطبيق المحفّظ</h1>
                    <p class="mt-3 text-sm leading-7 text-emerald-50/80">من هنا تتأكد من حالة الإصدار الرسمي. التحميل مقصور على لوحات المحفّظين والحسابات الإدارية المصرح لها، والتطبيق يفحص التحديث بعد تسجيل الدخول وعند العودة إليه وبعد المزامنة.</p>
                </div>
                <a href="{{ $teacherAppUrl }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-emerald-950 shadow-lg shadow-emerald-950/20 transition hover:-translate-y-0.5">
                    فتح بوابة المحفّظ
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
            <article class="panel">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="eyebrow">حالة النشر</p>
                        @if($release)
                            <h2 class="section-title mt-1">الإصدار الرسمي متاح</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">الإصدار <span dir="ltr" class="font-black">{{ $release['version_name'] }}</span> · بناء <span dir="ltr" class="font-black">{{ $release['version_code'] }}</span> · {{ number_format($release['size_bytes'] / 1024 / 1024, 1) }} MB</p>
                        @else
                            <h2 class="section-title mt-1">لا يوجد إصدار منشور بعد</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">لن يظهر ملف غير مكتمل أو غير موثّق للمحفّظين. انشر حزمة الإصدار وملف البصمة والبيان معًا، ثم فعّل الإصدار في إعدادات الإنتاج.</p>
                        @endif
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black {{ $release ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}"><span class="size-2 rounded-full {{ $release ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $release ? 'متاح' : 'قيد التجهيز' }}</span>
                </div>

                @if($release)
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $downloadUrl }}" download class="btn-primary">تنزيل الإصدار الحالي</a>
                        <a href="{{ $teacherAppUrl }}" class="btn-secondary">فتح بوابة المحفّظ</a>
                    </div>
                    <details class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                        <summary class="cursor-pointer font-bold text-slate-800">بصمة التحقق SHA-256</summary>
                        <code dir="ltr" class="mt-3 block break-all leading-6">{{ $release['sha256'] }}</code>
                    </details>
                @endif
            </article>

            <aside class="panel border-emerald-100 bg-emerald-50/40">
                <p class="eyebrow">طريقة التحديث</p>
                <ol class="mt-4 space-y-4 text-sm leading-7 text-slate-700">
                    <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">1</span><span>أنشئ إصدار Android موقّعًا بنفس مفتاح الإصدار وارفع رقم البناء.</span></li>
                    <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">2</span><span>ارفع ملف APK مع ملفي <span dir="ltr" class="font-semibold">.sha256</span> و<span dir="ltr" class="font-semibold">.json</span> إلى التخزين الخاص.</span></li>
                    <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">3</span><span>حدّث متغيرات الإصدار وانشر الموقع؛ سيظهر التنبيه داخل التطبيق تلقائيًا.</span></li>
                </ol>
                <p class="mt-5 rounded-xl border border-emerald-200 bg-white/80 p-3 text-xs leading-6 text-emerald-950/75">لا تُرفع ملفات APK من نموذج عادي داخل اللوحة؛ الإجراء الموثّق يحافظ على التوقيع والبصمة ويمنع توزيع ملف غير صحيح.</p>
            </aside>
        </section>
    </div>
</x-app-shell>
