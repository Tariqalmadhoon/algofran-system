<x-public-shell title="الرئيسية" description="مركز الغفران لتحفيظ القرآن الكريم — تعليم متقن، متابعة يومية، ورعاية تربوية متكاملة.">
    <section class="public-hero relative isolate overflow-hidden border-b border-emerald-950/8 bg-[#f7fbf8]">
        <div aria-hidden="true" class="public-hero-grid-pattern absolute inset-0 opacity-[.035]" style="background-image:linear-gradient(#065f46 1px,transparent 1px),linear-gradient(90deg,#065f46 1px,transparent 1px);background-size:52px 52px"></div>
        <div aria-hidden="true" class="absolute -right-40 -top-52 size-[34rem] rounded-full border-[90px] border-emerald-800/[.045]"></div>
        <div aria-hidden="true" class="absolute -bottom-48 left-[12%] size-[30rem] rounded-full bg-amber-200/25 blur-3xl"></div>
        <span aria-hidden="true" class="public-hero-orb public-hero-orb--emerald"></span>
        <span aria-hidden="true" class="public-hero-orb public-hero-orb--amber"></span>

        <div data-reveal-group="up" data-reveal-stagger="90" class="relative mx-auto grid min-h-[720px] max-w-7xl items-center gap-14 px-4 py-16 sm:px-6 lg:grid-cols-[1.02fr_.98fr] lg:px-8 lg:py-20">
            <div class="text-center lg:text-right">
                <div class="inline-flex items-center gap-2.5 rounded-full border border-emerald-200/80 bg-white/85 px-4 py-2 text-xs font-black text-emerald-800 shadow-[0_8px_30px_-18px_rgba(6,78,59,.45)] backdrop-blur">
                    <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-50"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-600"></span></span>
                    نبني جيلًا يحيا بالقرآن
                </div>

                <h1 class="mt-7 text-4xl font-black leading-[1.32] tracking-[-.035em] text-emerald-950 sm:text-6xl lg:text-[4.25rem]">
                    نغرس القرآن
                    <span class="relative block text-emerald-700">
                        علمًا وسلوكًا
                        <svg aria-hidden="true" class="absolute -bottom-2 right-1/2 h-3 w-48 translate-x-1/2 text-amber-400/70 lg:right-0 lg:translate-x-0" viewBox="0 0 200 12" fill="none"><path d="M3 8.5C55 2.5 116 2.5 197 7" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg>
                    </span>
                </h1>

                <p class="mx-auto mt-8 max-w-xl text-base leading-8 text-slate-600 lg:mx-0 lg:text-lg">
                    في مركز الغفران نصنع للطالب مسارًا واضحًا للحفظ والمراجعة، ونرافق تقدّمه بمتابعة يومية ورعاية تربوية تعينه على الارتباط بكتاب الله.
                </p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                    <a href="{{ route('public.programs') }}" class="btn-primary min-h-12 px-7 text-base">استكشف البرامج <span aria-hidden="true">←</span></a>
                    <a href="{{ route('public.about') }}" class="btn-secondary min-h-12 px-7 text-base">تعرّف على المركز</a>
                </div>

                <div class="mt-9 flex flex-wrap justify-center gap-x-6 gap-y-3 text-xs font-bold text-slate-500 lg:justify-start">
                    @foreach(['متابعة يومية دقيقة', 'منهج تربوي متكامل', 'بيئة آمنة ومحفّزة'] as $benefit)
                        <span class="inline-flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span>{{ $benefit }}</span>
                    @endforeach
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-[34rem] px-3 py-8 sm:px-8">
                <div aria-hidden="true" class="absolute inset-x-8 top-1/2 h-[70%] -translate-y-1/2 rounded-[4rem] bg-emerald-700/10 blur-2xl"></div>
                <div data-public-tilt class="public-hero-card relative overflow-hidden rounded-[2.75rem] border border-white/15 bg-[linear-gradient(145deg,#0b5b43_0%,#063b31_62%,#052d27_100%)] p-6 text-white shadow-[0_45px_100px_-40px_rgba(3,52,42,.8)] sm:p-8">
                    <div aria-hidden="true" class="absolute inset-0 opacity-[.12]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1.5px);background-size:26px 26px"></div>
                    <div aria-hidden="true" class="absolute -left-24 -top-24 size-64 rounded-full border-[50px] border-emerald-200/10"></div>

                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3"><x-brand-logo size="sm" /><div><p class="text-[10px] font-bold text-emerald-200/70">مسار طالب القرآن</p><p class="mt-1 text-sm font-black">من الحفظ إلى الإتقان</p></div></div>
                            <span class="grid size-10 place-items-center rounded-2xl border border-white/10 bg-white/10 text-emerald-200"><x-islamic-icon name="quran" class="size-5" /></span>
                        </div>

                        <div class="my-8 h-px bg-gradient-to-l from-transparent via-white/20 to-transparent"></div>

                        <p class="text-center text-[11px] font-bold tracking-[.18em] text-emerald-200/65">قال الله تعالى</p>
                        <p class="mt-4 text-center text-2xl font-black leading-[2.1] sm:text-3xl">﴿ وَرَتِّلِ الْقُرْآنَ تَرْتِيلًا ﴾</p>

                        <div class="mt-8 rounded-3xl border border-white/10 bg-white/[.07] p-4 backdrop-blur-sm">
                            <div class="flex items-end justify-between"><div><p class="text-[10px] font-bold text-emerald-100/55">رحلة هذا الأسبوع</p><p class="mt-1 text-sm font-black">حفظ · مراجعة · تثبيت</p></div><p class="text-2xl font-black text-emerald-300">87<span class="text-xs">%</span></p></div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-black/20"><div class="public-progress-fill h-full w-[87%] rounded-full bg-gradient-to-l from-emerald-300 to-amber-300"></div></div>
                        </div>
                    </div>
                </div>

                <div class="home-float absolute -right-1 top-2 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-xl shadow-emerald-950/10 sm:right-0">
                    <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><x-islamic-icon name="certificate" class="size-4" /></span><div><p class="text-[10px] font-bold text-slate-400">متابعة موثّقة</p><p class="text-xs font-black text-emerald-950">تقدّم يومي واضح</p></div></div>
                </div>
                <div class="home-float-delayed absolute -bottom-1 left-0 rounded-2xl border border-amber-100 bg-white px-4 py-3 shadow-xl shadow-emerald-950/10 sm:left-2">
                    <p class="text-2xl font-black text-emerald-800">{{ number_format($stats['students']) }}+</p><p class="text-[10px] font-bold text-slate-400">طالبًا في رحلة القرآن</p>
                </div>
            </div>
        </div>
    </section>

    @if($announcements->isNotEmpty())
        <section class="relative z-10 mx-auto -mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('public.contact') }}" class="group flex flex-col gap-3 rounded-3xl border border-amber-200/70 bg-[#fffaf0] px-5 py-4 shadow-[0_18px_50px_-28px_rgba(120,72,15,.35)] transition hover:-translate-y-0.5 sm:flex-row sm:items-center">
                <span class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-amber-500 px-3 py-1.5 text-[11px] font-black text-white sm:self-auto"><span class="size-1.5 rounded-full bg-white"></span> إعلان المركز</span>
                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-black text-amber-950">{{ $announcements->first()->title }}</span><span class="mt-0.5 block truncate text-xs text-amber-800/65">{{ $announcements->first()->excerpt }}</span></span>
                <span class="text-sm font-black text-amber-700 transition group-hover:-translate-x-1">التفاصيل ←</span>
            </a>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
        <div class="overflow-hidden rounded-[2rem] border border-emerald-950/8 bg-white shadow-[0_24px_80px_-48px_rgba(6,78,59,.4)]">
            <div data-reveal-group="up" data-reveal-stagger="55" class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-x-reverse sm:divide-y-0 lg:grid-cols-4">
                @foreach([
                    ['students', 'طالبًا وطالبة', 'ينمون مع كتاب الله', 'quran'],
                    ['halaqas', 'حلقة نشطة', 'تعليم قريب ومنظّم', 'mosque'],
                    ['programs', 'برنامجًا نوعيًا', 'مسارات تناسب الاحتياج', 'crescent'],
                    ['achievements', 'إنجازًا موثّقًا', 'ثمرة متابعة وإتقان', 'certificate'],
                ] as [$key, $label, $caption, $icon])
                    <div class="public-stat-item group flex items-center gap-4 p-6 transition duration-300 hover:bg-emerald-50/50 lg:p-7">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 transition duration-300 group-hover:bg-emerald-700 group-hover:text-white"><x-islamic-icon :name="$icon" /></span>
                        <span><span class="block text-2xl font-black text-emerald-950"><span class="public-counter" data-public-counter="{{ $stats[$key] }}">{{ number_format($stats[$key]) }}</span>+</span><span class="block text-sm font-black text-slate-700">{{ $label }}</span><span class="mt-1 block text-[11px] text-slate-400">{{ $caption }}</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="eyebrow">منهجية تصنع الفرق</p>
            <h2 class="text-3xl font-black leading-tight text-emerald-950 sm:text-4xl">رحلة تعليمية واضحة في كل خطوة</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-500 sm:text-base">لسنا مساحة للحفظ فقط؛ نبني حول الطالب منظومة تجمع التلقّي الصحيح، التثبيت المستمر، والمتابعة التي تحوّل الجهد إلى إنجاز.</p>
        </div>

        <div data-reveal-group="up" data-reveal-stagger="70" class="relative mt-12 grid gap-5 lg:grid-cols-3">
            <div aria-hidden="true" class="absolute right-[16%] top-11 hidden h-px w-[68%] bg-gradient-to-l from-transparent via-emerald-200 to-transparent lg:block"></div>
            @foreach([
                ['01', 'تلقٍّ صحيح', 'يتلقى الطالب القرآن على يد معلّم مؤهل، بعناية في النطق والتجويد من البداية.', 'quran'],
                ['02', 'متابعة مستمرة', 'يُسجّل الحفظ والمراجعة والتقييم بوضوح، لتبقى صورة التقدّم دقيقة كل يوم.', 'mosque'],
                ['03', 'إتقان ونمو', 'تُعالج مواطن الضعف بخطة مناسبة، ويُحتفى بكل محطة نجاح في رحلة الطالب.', 'certificate'],
            ] as [$number, $heading, $description, $icon])
                <article class="public-feature-card group relative rounded-[2rem] border border-slate-200/80 bg-white p-7 shadow-[0_20px_70px_-48px_rgba(6,78,59,.45)] transition duration-300 hover:border-emerald-200 hover:shadow-[0_28px_80px_-45px_rgba(6,78,59,.5)]">
                    <div class="flex items-center justify-between"><span class="grid size-14 place-items-center rounded-2xl bg-emerald-950 text-emerald-200 shadow-lg shadow-emerald-950/15"><x-islamic-icon :name="$icon" class="size-6" /></span><span class="text-4xl font-black text-emerald-950/[.07]">{{ $number }}</span></div>
                    <h3 class="mt-6 text-xl font-black text-emerald-950">{{ $heading }}</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-500">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="relative isolate overflow-hidden bg-emerald-950 py-24 text-white">
        <div aria-hidden="true" class="absolute inset-0 opacity-[.09]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1.5px);background-size:30px 30px"></div>
        <div aria-hidden="true" class="absolute -left-32 -top-48 size-[32rem] rounded-full border-[80px] border-emerald-300/10"></div>
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-black tracking-[.18em] text-emerald-300">مسارات تعليمية متكاملة</p><h2 class="mt-3 text-3xl font-black sm:text-4xl">برنامج يناسب كل رحلة</h2><p class="mt-3 max-w-xl text-sm leading-7 text-emerald-100/60">برامج مدروسة تجمع بين جودة التعليم، وضوح الهدف، والمتابعة المستمرة.</p></div>
                <a href="{{ route('public.programs') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-white/15 px-4 py-2.5 text-sm font-black text-emerald-200 hover:bg-white/10 sm:self-auto">عرض كل البرامج <span>←</span></a>
            </div>

            <div data-reveal-group="up" data-reveal-stagger="60" class="mt-11 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @forelse($programs as $index => $program)
                    <article class="public-program-card group flex min-h-64 flex-col rounded-[2rem] border border-white/10 bg-white/[.065] p-6 backdrop-blur-sm transition duration-300 hover:border-emerald-300/30 hover:bg-white/[.1]">
                        <div class="flex items-center justify-between"><span class="grid size-11 place-items-center rounded-2xl bg-emerald-300/15 text-emerald-200"><x-islamic-icon :name="['quran', 'crescent', 'mosque', 'certificate'][$index % 4]" /></span><span class="text-xs font-black text-white/20">0{{ $index + 1 }}</span></div>
                        <h3 class="mt-6 text-lg font-black leading-7">{{ $program->name }}</h3>
                        <p class="mt-2 line-clamp-3 text-sm leading-7 text-emerald-100/55">{{ $program->description ?: 'برنامج تعليمي نوعي مصمم بعناية ليصنع تقدّمًا واضحًا ومستمرًا.' }}</p>
                        <div class="mt-auto flex items-center gap-2 pt-5 text-xs font-bold text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-300"></span>{{ $program->hours }} ساعة تعليمية</div>
                    </article>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-white/15 px-6 py-12 text-center text-emerald-100/55">ستُعلن البرامج الجديدة قريبًا.</div>
                @endforelse
            </div>
        </div>
    </section>

    @if($news->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="mb-10 flex items-end justify-between gap-4">
                <div><p class="eyebrow">من قلب المركز</p><h2 class="text-3xl font-black text-emerald-950 sm:text-4xl">آخر الأخبار</h2><p class="mt-3 text-sm text-slate-500">محطات من حياة طلابنا وبرامجنا.</p></div>
                <a href="{{ route('news.index') }}" class="hidden rounded-xl border border-emerald-200 px-4 py-2.5 text-sm font-black text-emerald-700 hover:bg-emerald-50 sm:inline-flex">كل الأخبار ←</a>
            </div>
            <div data-reveal-group="up" data-reveal-stagger="65" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">@foreach($news as $item)<x-public-content-card :item="$item" route-name="news.show" />@endforeach</div>
            <a href="{{ route('news.index') }}" class="btn-secondary mt-6 w-full sm:hidden">كل الأخبار</a>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 pb-6 pt-10 sm:px-6 lg:px-8">
        <div class="relative isolate overflow-hidden rounded-[2.75rem] bg-[linear-gradient(120deg,#0d7657,#06493a)] px-6 py-14 text-center text-white shadow-[0_35px_90px_-45px_rgba(6,78,59,.7)] sm:px-12 sm:py-16">
            <div aria-hidden="true" class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:24px 24px"></div>
            <div aria-hidden="true" class="absolute -right-24 -top-32 size-72 rounded-full border-[50px] border-white/10"></div>
            <div data-reveal-group="up" data-reveal-stagger="55" class="relative mx-auto max-w-2xl">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl border border-white/15 bg-white/10 text-emerald-100"><x-islamic-icon name="quran" class="size-6" /></span>
                <p class="mt-5 text-sm font-bold text-emerald-200">باب الخير مفتوح</p>
                <h2 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">ابدأ رحلتك مع القرآن اليوم</h2>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-emerald-100/70">تواصل معنا، وسنساعدك في اختيار المسار الأنسب للالتحاق بإحدى حلقات مركز الغفران.</p>
                <a href="{{ route('public.contact') }}" class="mt-7 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-white px-7 font-black text-emerald-900 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">تواصل معنا <span>←</span></a>
            </div>
        </div>
    </section>
</x-public-shell>
