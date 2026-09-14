@php
    $stats = $analytics['stats'];
    $attendance = $analytics['attendance'];
    $coverage = $analytics['coverage'];
    $studentRankings = $analytics['student_rankings'];
    $role = auth()->user()->getRoleNames()->first();
    $teachingContext = $analytics['teacher_today'];
    $dashboardLabel = match(true) {
        auth()->user()->hasRole('center-manager') && $teachingContext => 'مدير المركز · محفّظ',
        auth()->user()->hasRole('teacher') => 'لوحة المحفظ · مساحة العمل',
        $role === 'academic-supervisor' => 'لوحة المشرف الأكاديمي',
        in_array($role, ['student', 'guardian'], true) => 'تحليل تقدم الطالب',
        default => 'لوحة الإدارة',
    };
    $attendanceTotal = max(1, $attendance['total']);
    $presentEnd = ($attendance['present'] / $attendanceTotal) * 100;
    $lateEnd = $presentEnd + (($attendance['late'] / $attendanceTotal) * 100);
    $absentEnd = $lateEnd + (($attendance['absent'] / $attendanceTotal) * 100);
    $levelTotal = max(1, array_sum($analytics['levels']));
@endphp

<div class="relative space-y-6" x-data="{ filtersOpen: false }">
    <div wire:loading.delay class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1 overflow-hidden bg-emerald-100">
        <div class="h-full w-1/3 animate-[dashboard-loading_1.2s_ease-in-out_infinite] rounded-full bg-emerald-500"></div>
    </div>

    <header class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-700 px-6 py-7 text-white shadow-[0_28px_70px_-36px_rgba(6,78,59,.85)] sm:px-8 sm:py-9">
        <div class="absolute -left-16 -top-24 -z-10 size-64 rounded-full bg-emerald-300/15 blur-3xl"></div>
        <div class="absolute -bottom-28 right-1/3 -z-10 size-72 rounded-full bg-teal-300/10 blur-3xl"></div>
        <svg class="absolute bottom-0 left-0 -z-10 h-full w-1/2 text-white/[.035]" viewBox="0 0 500 250" fill="none" aria-hidden="true"><path d="M520 242C388 86 243 338 80 113-1 1-79 38-115 64" stroke="currentColor" stroke-width="52" stroke-linecap="round"/></svg>

        <div class="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs font-black text-emerald-100">
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">{{ $dashboardLabel }}</span>
                    @if($teachingContext['center'] ?? null)
                        <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-emerald-100">{{ $teachingContext['center']->name }}</span>
                    @endif
                    <span class="size-1.5 animate-pulse rounded-full bg-emerald-300"></span>
                    <span>{{ now()->translatedFormat('l، d F Y') }}</span>
                </div>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">السلام عليكم، {{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-emerald-50/75 sm:text-base">صورة تشغيلية مباشرة للأداء الأكاديمي والحضور وأولويات المتابعة، محسوبة من البيانات الفعلية ضمن صلاحياتك.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($teachingContext)
                    <a href="{{ route('teacher.mobile.app') }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200/30 bg-emerald-300/10 px-4 py-2.5 text-sm font-bold text-emerald-50 backdrop-blur transition hover:-translate-y-0.5 hover:bg-emerald-300/20">
                        <x-nav-icon name="mobile" class="size-5" />
                        تطبيق المحفّظ
                    </a>
                @endif
                @if(auth()->user()->hasRole('super-admin'))
                    <a href="{{ route('mobile.distribution') }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200/30 bg-emerald-300/10 px-4 py-2.5 text-sm font-bold text-emerald-50 backdrop-blur transition hover:-translate-y-0.5 hover:bg-emerald-300/20">
                        <x-nav-icon name="mobile" class="size-5" />
                        توزيع التطبيق
                    </a>
                @endif
                <button @click="filtersOpen = ! filtersOpen" type="button" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:-translate-y-0.5 hover:bg-white/15">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16M7 12h10m-7 7h4" stroke-linecap="round"/><circle cx="8" cy="5" r="2" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="2" fill="currentColor" stroke="none"/></svg>
                    تخصيص النطاق
                </button>
                <button wire:click="$refresh" wire:loading.attr="disabled" type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-emerald-950 shadow-lg shadow-emerald-950/15 hover:-translate-y-0.5">
                    <svg wire:loading.class="animate-spin" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7h-5V2M4 17h5v5"/><path d="M6.1 9A7 7 0 0 1 18.6 6L20 7M4 17l1.4 1A7 7 0 0 0 18 15" stroke-linecap="round"/></svg>
                    تحديث البيانات
                </button>
            </div>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-xs text-emerald-50/70">
            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-300"></span> الفترة: {{ $analytics['period']['from'] }} — {{ $analytics['period']['to'] }}</span>
            <span>آخر تحديث: {{ now()->translatedFormat('h:i A') }}</span>
        </div>
    </header>

    <section x-cloak x-show="filtersOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3" class="panel">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div><p class="eyebrow">مرشحات تفاعلية</p><h2 class="section-title">نطاق التحليل</h2></div>
            <button wire:click="resetFilters" type="button" class="btn-secondary">إعادة الضبط</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <label><span class="form-label">من</span><input wire:model.live="dateFrom" type="date" class="form-input"></label>
            <label><span class="form-label">إلى</span><input wire:model.live="dateTo" type="date" class="form-input"></label>
            <label><span class="form-label">الحلقة</span><select wire:model.live="halaqaId" class="form-input"><option value="">كل الحلقات المتاحة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></label>
            @can('organization.view')
                <label><span class="form-label">المحفظ</span><select wire:model.live="teacherId" class="form-input"><option value="">كل المحفظين</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select></label>
                <label><span class="form-label">البرنامج</span><select wire:model.live="program" class="form-input"><option value="">كل البرامج</option>@foreach($programs as $programName)<option value="{{ $programName }}">{{ $programName }}</option>@endforeach</select></label>
            @endcan
            <label><span class="form-label">الطالب</span><select wire:model.live="studentId" class="form-input"><option value="">كل الطلاب</option>@foreach($studentsForFilter as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select></label>
        </div>
        <div wire:loading.delay class="mt-4 text-xs font-bold text-emerald-700">جارٍ إعادة احتساب المؤشرات ضمن النطاق الجديد…</div>
    </section>

    @if($teachingContext)
        @php
            $teacherCompletion = $teachingContext['students'] > 0
                ? round(($teachingContext['recorded'] / $teachingContext['students']) * 100)
                : 0;
        @endphp
        <section class="relative isolate overflow-hidden rounded-[2rem] border border-emerald-200 bg-white p-5 shadow-[0_24px_65px_-40px_rgba(6,78,59,.65)] sm:p-6">
            <div class="absolute inset-y-0 right-0 -z-10 w-full bg-[radial-gradient(circle_at_12%_20%,rgba(16,185,129,.12),transparent_30%),linear-gradient(110deg,rgba(236,253,245,.95),transparent_60%)]"></div>
            <svg class="absolute -left-8 -top-8 -z-10 size-48 text-emerald-900/[.035]" viewBox="0 0 100 100" fill="none" aria-hidden="true"><path d="M50 5 62 36l33 14-33 14-12 31-12-31L5 50l33-14L50 5Z" stroke="currentColor" stroke-width="4"/><circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="3"/></svg>

            <div class="flex flex-col gap-5 border-b border-emerald-100 pb-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative grid size-16 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-emerald-950 to-emerald-700 text-white shadow-xl shadow-emerald-900/20"><x-islamic-icon name="mosque" class="size-8" /><span class="absolute -bottom-1 -left-1 size-4 rounded-full border-2 border-white bg-emerald-400"></span></div>
                    <div><p class="text-xs font-black tracking-widest text-emerald-700">مساحة عملي اليوم</p><h2 class="mt-1 text-2xl font-black text-emerald-950">{{ $teachingContext['center']?->name ?? config('app.name') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $teachingContext['halaqas']->count() }} حلقة مسندة · {{ $teachingContext['students'] }} طالبًا ضمن مسؤوليتك</p></div>
                </div>
                <div class="flex w-full items-center gap-4 lg:max-w-md">
                    <div class="flex-1"><div class="mb-2 flex justify-between text-xs font-bold text-slate-600"><span>إنجاز التسجيل اليومي</span><span>{{ $teachingContext['recorded'] }} / {{ $teachingContext['students'] }}</span></div><div class="h-3 overflow-hidden rounded-full bg-emerald-100"><div class="h-full rounded-full bg-gradient-to-l from-emerald-700 to-teal-400 transition-all duration-1000" style="width: {{ $teacherCompletion }}%"></div></div></div>
                    <a href="{{ route('teacher.daily') }}" class="btn-primary shrink-0">فتح جلسة اليوم</a>
                </div>
            </div>

            <div class="mt-5 grid gap-3 border-b border-emerald-100 pb-5 sm:grid-cols-2">
                <a href="{{ route('teacher.daily') }}" class="group flex items-center gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-lg hover:shadow-emerald-900/5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-800 text-white transition group-hover:scale-105"><x-nav-icon name="daily" class="size-5" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-xs font-black text-emerald-600">عملي كمحفّظ</span><span class="mt-1 block font-black text-emerald-950">فتح التسجيل اليومي</span></span>
                    <span class="text-xl text-emerald-500">←</span>
                </a>
                @can('alerts.view')
                    <a href="{{ route('alerts.index', ['scope' => 'teaching']) }}" class="group flex items-center gap-4 rounded-2xl border border-amber-100 bg-amber-50/65 p-4 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-50 hover:shadow-lg hover:shadow-amber-900/5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-amber-500 text-white transition group-hover:scale-105"><x-nav-icon name="alerts" class="size-5" /></span>
                        <span class="min-w-0 flex-1"><span class="block text-xs font-black text-amber-600">متابعة طلاب حلقتي</span><span class="mt-1 block font-black text-amber-950">تنبيهات الطلاب</span></span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-amber-700">{{ $teachingContext['open_alerts'] }}</span>
                    </a>
                @endcan
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($teachingContext['halaqas'] as $halaqa)
                    @php
                        $halaqaCompletion = $halaqa->active_students_count > 0 ? round(($halaqa->recorded_today_count / $halaqa->active_students_count) * 100) : 0;
                        $nextSchedule = $halaqa->schedules->first();
                    @endphp
                    <a href="{{ route('teacher.daily') }}" class="group rounded-2xl border border-slate-200 bg-white/90 p-4 transition duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-xl hover:shadow-emerald-900/8">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-[11px] font-black text-emerald-600">حلقتي الرسمية</p><h3 class="mt-1 text-lg font-black text-emerald-950">{{ $halaqa->name }}</h3></div><span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-800 group-hover:text-white"><x-islamic-icon name="quran" class="size-5" /></span></div>
                        <div class="mt-4 flex items-center justify-between text-xs text-slate-500"><span>{{ $halaqa->recorded_today_count }} من {{ $halaqa->active_students_count }} مسجل</span><span>{{ $nextSchedule ? substr($nextSchedule->starts_at, 0, 5) : 'موعد مرن' }}</span></div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500 transition-all duration-1000" style="width: {{ $halaqaCompletion }}%"></div></div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50 p-5 md:col-span-2 xl:col-span-3"><p class="font-black text-amber-900">لا توجد حلقة مسندة حاليًا</p><p class="mt-1 text-sm text-amber-700">يظهر اسم الحلقة ومسار التسجيل فور إتمام الإسناد من إدارة المركز.</p></div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="المؤشرات الرئيسية">
        @foreach([
            ['الطلاب الفعالون', $stats['active_students'], 'طالب ضمن النطاق الحالي', 'emerald', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-1 2 2 4-4"/>'],
            ['معدل الحضور', number_format($attendance['rate'], 1).'%', $attendance['total'].' سجل حضور', 'sky', '<path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/><path d="m8 12 2.5 2.5L16 9"/>'],
            ['متوسط الأداء', number_format($coverage['average_student_score'], 1), 'من 100 لآخر تقدم', 'violet', '<path d="M4 19V9m5 10V5m5 14v-7m5 7V3"/>'],
            ['أولوية المتابعة', $stats['needs_followup'], $stats['open_alerts'].' تنبيه مفتوح', $stats['open_alerts'] > 0 ? 'amber' : 'emerald', '<path d="M12 9v4m0 4h.01M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z"/>'],
        ] as [$label, $value, $caption, $tone, $icon])
            @php
                $tones = [
                    'emerald' => ['from-emerald-50', 'bg-emerald-100 text-emerald-800', 'text-emerald-950'],
                    'sky' => ['from-sky-50', 'bg-sky-100 text-sky-700', 'text-sky-950'],
                    'violet' => ['from-violet-50', 'bg-violet-100 text-violet-700', 'text-violet-950'],
                    'amber' => ['from-amber-50', 'bg-amber-100 text-amber-700', 'text-amber-950'],
                ];
                [$surface, $iconTone, $valueTone] = $tones[$tone];
            @endphp
            <article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br {{ $surface }} to-white p-5 shadow-[0_16px_45px_-34px_rgba(15,23,42,.55)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_-34px_rgba(6,78,59,.4)]">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm font-bold text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black tracking-tight {{ $valueTone }}">{{ $value }}</p><p class="mt-1 text-xs text-slate-400">{{ $caption }}</p></div>
                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $iconTone }} transition duration-300 group-hover:rotate-3 group-hover:scale-105"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['المحفظون', $stats['teachers']], ['الحلقات', $stats['halaqas']], ['جلسات التسميع', $stats['daily_records']],
            ['الحفظ الجديد', $stats['new_memorization']], ['المراجعات', $stats['revisions']], ['تغطية التقدم', number_format($coverage['rate'], 0).'%'],
        ] as [$label, $value])
            <article class="rounded-2xl border border-slate-200/75 bg-white px-4 py-4 transition duration-300 hover:border-emerald-200 hover:bg-emerald-50/30"><p class="text-2xl font-black text-slate-800">{{ $value }}</p><p class="mt-1 text-xs font-bold text-slate-400">{{ $label }}</p></article>
        @endforeach
    </section>

    @can('reports.view')
        <section class="relative isolate overflow-hidden rounded-[2rem] border border-emerald-200/80 bg-white shadow-[0_30px_80px_-48px_rgba(6,78,59,.6)]">
            <div class="pointer-events-none absolute -left-24 -top-24 -z-10 size-72 rounded-full bg-amber-200/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 right-1/3 -z-10 size-72 rounded-full bg-emerald-200/20 blur-3xl"></div>

            <div class="border-b border-emerald-100 bg-gradient-to-l from-emerald-50/90 via-white to-amber-50/60 p-5 sm:p-7">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="mb-2 flex items-center gap-2 text-xs font-black text-emerald-700"><span class="grid size-7 place-items-center rounded-lg bg-emerald-800 text-white">١</span><span>لوحة الالتزام والإنجاز</span></div>
                        <h2 class="text-2xl font-black text-emerald-950">ترتيب الطلاب خلال الفترة</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">الحفظ يُحسب من الآيات الفريدة المسجلة كحفظ جديد دون مضاعفة النطاقات المتداخلة، والالتزام يعتبر «بعذر» غيابًا وليس حضورًا.</p>
                    </div>
                    <span class="w-fit rounded-2xl border border-emerald-200 bg-white px-4 py-2 text-xs font-black text-emerald-800" dir="ltr">{{ $studentRankings['period']['from'] }} → {{ $studentRankings['period']['to'] }}</span>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @php
                        $topMemorizer = $studentRankings['top_memorizer'];
                    @endphp
                    <article class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-400 via-amber-500 to-orange-500 p-5 text-white shadow-xl shadow-amber-900/15 transition duration-300 hover:-translate-y-1">
                        <span class="absolute -left-8 -top-10 text-[8rem] leading-none text-white/10">★</span>
                        <div class="relative flex items-center gap-4">
                            <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/20 text-2xl backdrop-blur">🏆</span>
                            @if($topMemorizer)
                                <x-student-avatar :student="$topMemorizer['student']" />
                                <div class="min-w-0 flex-1"><p class="text-xs font-black text-amber-100">أكثر الطلاب حفظًا</p><p class="mt-1 truncate text-lg font-black">{{ $topMemorizer['student']->full_name }}</p><p class="mt-1 text-xs text-white/80">{{ number_format($topMemorizer['equivalent_juz'], 2) }} جزء تقريبًا · {{ $topMemorizer['memorized_ayahs'] }} آية</p></div>
                            @else
                                <div><p class="font-black">لم يُسجل حفظ جديد</p><p class="mt-1 text-xs text-white/80">سيظهر بطل الفترة مع أول سجل.</p></div>
                            @endif
                        </div>
                    </article>

                    @php
                        $mostCommitted = $studentRankings['most_committed'];
                    @endphp
                    <article class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-600 p-5 text-white shadow-xl shadow-emerald-950/15 transition duration-300 hover:-translate-y-1">
                        <span class="absolute -left-6 -top-12 text-[9rem] leading-none text-white/[.06]">✓</span>
                        <div class="relative flex items-center gap-4">
                            <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/15 text-2xl backdrop-blur">✓</span>
                            @if($mostCommitted)
                                <x-student-avatar :student="$mostCommitted['student']" />
                                <div class="min-w-0 flex-1"><p class="text-xs font-black text-emerald-100">الأكثر التزامًا</p><p class="mt-1 truncate text-lg font-black">{{ $mostCommitted['student']->full_name }}</p><p class="mt-1 text-xs text-white/75">{{ number_format($mostCommitted['commitment_rate'], 1) }}% · {{ $mostCommitted['attendance_days'] }} أيام مسجلة</p></div>
                            @else
                                <div><p class="font-black">لا توجد بيانات حضور</p><p class="mt-1 text-xs text-white/75">سيبدأ الترتيب بعد أول تسجيل يومي.</p></div>
                            @endif
                        </div>
                    </article>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
                    @foreach([
                        ['الطلاب', $studentRankings['summary']['students']],
                        ['الآيات المحفوظة', $studentRankings['summary']['memorized_ayahs']],
                        ['ما يعادل من الأجزاء', number_format($studentRankings['summary']['equivalent_juz'], 2)],
                        ['متوسط الالتزام', $studentRankings['summary']['average_commitment'] === null ? '—' : number_format($studentRankings['summary']['average_commitment'], 1).'%'],
                        ['الغياب', $studentRankings['summary']['absent_days'].' + '.$studentRankings['summary']['excused_days'].' بعذر'],
                    ] as [$label, $value])
                        <div class="rounded-2xl border border-white bg-white/80 p-3 shadow-sm backdrop-blur"><p class="text-[11px] font-bold text-slate-400">{{ $label }}</p><p class="mt-1 text-xl font-black text-emerald-950">{{ $value }}</p></div>
                    @endforeach
                </div>
            </div>

            <div class="p-4 sm:p-6" x-data="{ expanded: false }">
                <div class="mb-4 flex items-center justify-between gap-3"><div><h3 class="font-black text-slate-800">الترتيب التفصيلي</h3><p class="mt-1 text-xs text-slate-400">يُرتب الجدول بكمية الحفظ، ثم نسبة الالتزام عند التعادل.</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600">{{ $studentRankings['rankings']->count() }} طالب</span></div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-[1050px] w-full text-right text-sm">
                        <thead class="bg-slate-900 text-[11px] font-black text-white"><tr><th class="px-4 py-3">الترتيب</th><th class="px-4 py-3">الطالب</th><th class="px-4 py-3">الحلقة / المحفّظ</th><th class="px-4 py-3">حفظ الفترة</th><th class="px-4 py-3">الحضور</th><th class="px-4 py-3">غياب</th><th class="px-4 py-3">بعذر</th><th class="px-4 py-3">الالتزام</th><th class="px-4 py-3">الحالة</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($studentRankings['rankings'] as $row)
                                @php
                                    $statusMeta = match($row['commitment_status']) {
                                        'excellent' => ['ملتزم جدًا', 'bg-emerald-50 text-emerald-700'],
                                        'good' => ['التزام جيد', 'bg-sky-50 text-sky-700'],
                                        'needs_contact' => ['يلزم التواصل', 'bg-red-50 text-red-700'],
                                        'over_excused' => ['تجاوز المسموح', 'bg-amber-50 text-amber-700'],
                                        'needs_followup' => ['يحتاج متابعة', 'bg-amber-50 text-amber-700'],
                                        default => ['دون بيانات', 'bg-slate-100 text-slate-500'],
                                    };
                                @endphp
                                <tr x-cloak class="group transition hover:bg-emerald-50/40" x-show="expanded || {{ $row['rank'] }} <= 8" x-transition.opacity>
                                    <td class="px-4 py-3"><span @class(['grid size-9 place-items-center rounded-xl text-xs font-black', 'bg-amber-100 text-amber-800 ring-1 ring-amber-300' => $row['rank'] === 1, 'bg-slate-100 text-slate-600' => $row['rank'] > 1])>{{ $row['rank'] }}</span></td>
                                    <td class="px-4 py-3"><div class="flex items-center gap-3"><x-student-avatar :student="$row['student']" size="sm" /><div class="min-w-0">@can('view', $row['student'])<a href="{{ route('students.show', $row['student']) }}" class="block max-w-48 truncate font-black text-slate-800 transition hover:text-emerald-700">{{ $row['student']->full_name }}</a>@else<span class="block max-w-48 truncate font-black text-slate-800">{{ $row['student']->full_name }}</span>@endcan<p class="mt-0.5 text-[10px] text-slate-400">حفظ #{{ $row['memorization_rank'] }} · التزام #{{ $row['commitment_rank'] ?? '—' }}</p></div></div></td>
                                    <td class="px-4 py-3"><p class="font-bold text-slate-700">{{ $row['halaqa_name'] }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $row['teacher_name'] }}</p></td>
                                    <td class="px-4 py-3"><strong class="text-emerald-800">{{ number_format($row['equivalent_juz'], 2) }} جزء</strong><p class="mt-1 text-[11px] text-slate-400">{{ $row['memorized_ayahs'] }} آية · {{ $row['memorization_sessions'] }} جلسات</p></td>
                                    <td class="px-4 py-3"><span class="font-black text-emerald-700">{{ $row['present_days'] }}</span><span class="mx-1 text-slate-300">+</span><span class="font-black text-amber-600">{{ $row['late_days'] }} متأخر</span></td>
                                    <td class="px-4 py-3"><span @class(['font-black', 'text-red-600' => $row['absent_days'] > 0, 'text-slate-400' => $row['absent_days'] === 0])>{{ $row['absent_days'] }}</span></td>
                                    <td class="px-4 py-3"><span @class(['font-black', 'text-amber-600' => $row['excused_days'] > 3, 'text-sky-600' => $row['excused_days'] <= 3])>{{ $row['excused_days'] }}</span><span class="mr-1 text-[10px] text-slate-400">/ 3</span></td>
                                    <td class="px-4 py-3">@if($row['commitment_rate'] !== null)<div class="w-24"><div class="mb-1 flex justify-between text-[10px]"><strong class="text-slate-700">{{ number_format($row['commitment_rate'], 1) }}%</strong></div><div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-l from-emerald-600 to-teal-400 transition-all duration-700" style="width: {{ $row['commitment_rate'] }}%"></div></div></div>@else<span class="text-slate-400">—</span>@endif</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-5 py-12 text-center text-sm font-bold text-slate-400">لا يوجد طلاب ضمن النطاق الحالي.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($studentRankings['rankings']->count() > 8)
                    <button @click="expanded = ! expanded" type="button" class="btn-secondary mx-auto mt-4"><span x-text="expanded ? 'عرض الأوائل فقط' : 'عرض كل الطلاب'"></span></button>
                @endif
            </div>
        </section>
    @endcan

    <section class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <article class="panel overflow-hidden">
            <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="eyebrow">اتجاه الأداء · آخر 7 أيام</p><h2 class="section-title">جودة التسميع اليومية</h2><p class="mt-1 text-xs text-slate-400">ارتفاع العمود يعني تحسن متوسط تقييم بنود التسميع</p></div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-2 text-left"><p class="text-[10px] font-black text-emerald-600">المتوسط العام</p><p class="text-2xl font-black text-emerald-900">{{ number_format($analytics['evaluation_average'], 1) }}<span class="text-xs text-emerald-600"> /100</span></p></div>
            </div>
            <div class="relative flex h-60 items-end gap-2 border-b border-slate-200 px-1 sm:gap-4">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-slate-100"></div>
                <div class="pointer-events-none absolute inset-x-0 top-1/2 h-px bg-slate-100"></div>
                @foreach($analytics['trend'] as $point)
                    <div wire:key="weekly-{{ $point['date'] }}" class="group flex h-full flex-1 flex-col justify-end gap-2 text-center" title="{{ $point['date'] }}: {{ $point['value'] }}">
                        <span class="translate-y-1 text-[11px] font-black text-slate-500 opacity-0 transition group-hover:translate-y-0 group-hover:opacity-100">{{ number_format($point['value']) }}</span>
                        <div class="relative mx-auto w-full max-w-11 flex-1"><div class="absolute inset-x-0 bottom-0 min-h-1 origin-bottom rounded-t-xl bg-gradient-to-t from-emerald-700 to-emerald-400 shadow-[0_8px_20px_-8px_rgba(5,150,105,.7)] transition-all duration-700 group-hover:from-emerald-800 group-hover:to-teal-400" style="height: {{ max(2, $point['value']) }}%"></div></div>
                        <span class="pb-3 text-xs font-bold text-slate-400">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach($analytics['monthly_trend'] as $point)
                    <div class="group rounded-2xl bg-slate-50 p-3 text-center transition hover:bg-emerald-50"><div class="mx-auto mb-2 flex h-8 w-1.5 items-end overflow-hidden rounded-full bg-slate-200"><span class="block w-full rounded-full bg-emerald-500 transition-all duration-700" style="height: {{ max(8, $point['value']) }}%"></span></div><p class="text-base font-black text-emerald-900">{{ number_format($point['value'], 0) }}</p><p class="mt-0.5 truncate text-[10px] text-slate-400">{{ $point['label'] }}</p></div>
                @endforeach
            </div>
        </article>

        <article class="panel">
            <div class="mb-6"><p class="eyebrow">تحليل الانتظام</p><h2 class="section-title">توزيع الحضور</h2></div>
            <div class="grid place-items-center">
                <div class="relative grid size-44 place-items-center rounded-full transition duration-700 hover:scale-[1.03]" style="background: conic-gradient(#059669 0% {{ $presentEnd }}%, #f59e0b {{ $presentEnd }}% {{ $lateEnd }}%, #ef4444 {{ $lateEnd }}% {{ $absentEnd }}%, #38bdf8 {{ $absentEnd }}% 100%)">
                    <div class="grid size-32 place-items-center rounded-full bg-white text-center shadow-inner"><div><p class="text-3xl font-black text-emerald-950">{{ number_format($attendance['rate'], 0) }}%</p><p class="text-xs font-bold text-slate-400">نسبة الحضور</p></div></div>
                </div>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-3">
                @foreach([
                    ['حاضر', $attendance['present'], 'bg-emerald-500'], ['متأخر', $attendance['late'], 'bg-amber-500'],
                    ['غائب', $attendance['absent'], 'bg-red-500'], ['بعذر', $attendance['excused'], 'bg-sky-400'],
                ] as [$label, $value, $dot])
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5"><span class="flex items-center gap-2 text-xs font-bold text-slate-500"><span class="size-2 rounded-full {{ $dot }}"></span>{{ $label }}</span><strong class="text-sm text-slate-800">{{ $value }}</strong></div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
        <article class="panel">
            <div class="mb-6"><p class="eyebrow">توزيع أكاديمي</p><h2 class="section-title">مستويات الطلاب</h2></div>
            <div class="space-y-5">
                @foreach([
                    'excellent' => ['ممتاز · 80 فأعلى', 'bg-emerald-600'],
                    'good' => ['جيد · 60 فأعلى', 'bg-sky-500'],
                    'needs_support' => ['يحتاج دعمًا', 'bg-amber-500'],
                    'critical' => ['حالة حرجة', 'bg-red-500'],
                    'no_data' => ['دون بيانات', 'bg-slate-400'],
                ] as $key => [$label, $color])
                    @php
                        $percentage = ($analytics['levels'][$key] / $levelTotal) * 100;
                    @endphp
                    <div><div class="mb-2 flex items-center justify-between text-xs"><span class="font-bold text-slate-600">{{ $label }}</span><span class="font-black text-slate-800">{{ $analytics['levels'][$key] }} <span class="font-normal text-slate-400">({{ number_format($percentage, 0) }}%)</span></span></div><div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $color }} transition-all duration-700" style="width: {{ $percentage }}%"></div></div></div>
                @endforeach
            </div>
        </article>

        <article class="panel">
            <div class="mb-6 flex items-end justify-between"><div><p class="eyebrow">مقارنة تشغيلية</p><h2 class="section-title">أداء الحلقات</h2></div><span class="text-xs font-bold text-slate-400">أفضل 8 حلقات</span></div>
            <div class="space-y-3">
                @forelse($analytics['halaqa_performance'] as $index => $halaqa)
                    <div class="group grid grid-cols-[auto_1fr_auto] items-center gap-3 rounded-2xl border border-slate-100 p-3.5 transition duration-300 hover:border-emerald-200 hover:bg-emerald-50/35">
                        <span class="grid size-9 place-items-center rounded-xl {{ $index < 3 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }} text-xs font-black">{{ $index + 1 }}</span>
                        <div class="min-w-0"><div class="mb-2 flex items-center justify-between gap-3"><p class="truncate text-sm font-black text-slate-700">{{ $halaqa['name'] }}</p><p class="text-[11px] text-slate-400">{{ $halaqa['records'] }} سجل</p></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-l from-emerald-700 to-emerald-400 transition-all duration-700" style="width: {{ $halaqa['average'] }}%"></div></div></div>
                        <strong class="w-10 text-left text-sm text-emerald-800">{{ number_format($halaqa['average'], 0) }}</strong>
                    </div>
                @empty
                    <div class="empty-state min-h-48"><div><p class="font-black text-slate-600">لا توجد تقييمات بعد</p><p class="mt-1 text-sm">ستظهر مقارنة الحلقات عند تسجيل بيانات التسميع.</p></div></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="panel">
            <div class="mb-5 flex items-end justify-between gap-3"><div><p class="eyebrow">متابعة استباقية</p><h2 class="section-title">التنبيهات ذات الأولوية</h2></div>@can('alerts.view')<a href="{{ route('alerts.index') }}" class="text-xs font-black text-emerald-700 hover:text-emerald-900">عرض مركز التنبيهات ←</a>@endcan</div>
            <div class="space-y-3">
                @forelse($analytics['alerts'] as $alert)
                    <article class="group rounded-2xl border p-4 transition duration-300 hover:-translate-y-0.5 {{ $alert->severity->value === 'critical' ? 'border-red-200 bg-red-50/70 hover:shadow-red-100' : 'border-amber-200 bg-amber-50/70 hover:shadow-amber-100' }} hover:shadow-lg">
                        <div class="flex items-start gap-3"><x-student-avatar :student="$alert->student" size="sm" /><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="font-black text-slate-800">{{ $alert->student->full_name }}</p><span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-black {{ $alert->severity->value === 'critical' ? 'text-red-700' : 'text-amber-700' }}">{{ $alert->severity->label() }}</span></div><p class="mt-1.5 line-clamp-2 text-sm leading-6 text-slate-600">{{ $alert->reason }}</p><p class="mt-2 text-[11px] text-slate-400">{{ $alert->halaqa?->name ?? 'دون حلقة' }}</p></div><span class="mt-1 size-2 shrink-0 animate-pulse rounded-full {{ $alert->severity->value === 'critical' ? 'bg-red-500' : 'bg-amber-500' }}"></span></div>
                    </article>
                @empty
                    <div class="empty-state min-h-48"><div><div class="mx-auto mb-3 grid size-11 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</div><p class="font-black text-slate-600">لا توجد تنبيهات مفتوحة</p><p class="mt-1 text-sm">النطاق الحالي لا يحتوي حالات تحتاج إجراءً.</p></div></div>
                @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="mb-5"><p class="eyebrow">جودة البيانات</p><h2 class="section-title">اكتمال ملفات التقدم</h2></div>
            <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-emerald-950 p-6 text-white">
                <div class="flex items-end justify-between gap-4"><div><p class="text-sm font-bold text-emerald-200">نسبة التغطية</p><p class="mt-2 text-4xl font-black">{{ number_format($coverage['rate'], 0) }}%</p></div><div class="text-left text-xs leading-6 text-emerald-100/70"><p>{{ $coverage['students_with_progress'] }} ملف محسوب</p><p>{{ $coverage['students_without_progress'] }} دون تقدم حديث</p></div></div>
                <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-gradient-to-l from-emerald-300 to-teal-400 transition-all duration-700" style="width: {{ $coverage['rate'] }}%"></div></div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-2xl font-black text-emerald-900">{{ $coverage['students_with_progress'] }}</p><p class="mt-1 text-xs font-bold text-emerald-700">لديهم مؤشر تقدم</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-slate-700">{{ $coverage['students_without_progress'] }}</p><p class="mt-1 text-xs font-bold text-slate-500">بحاجة إلى احتساب</p></div>
            </div>
        </article>
    </section>

    <section class="panel overflow-hidden p-0">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="eyebrow">الأولوية الأكاديمية</p><h2 class="section-title">طلاب يحتاجون المراجعة</h2></div><p class="text-xs text-slate-400">مرتّب حسب أقل درجة تقدم</p></div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>الطالب</th><th>الحلقة</th><th>الدرجة</th><th>الحفظ</th><th>الحضور</th><th></th></tr></thead>
                <tbody>
                    @forelse($analytics['students'] as $student)
                        @php
                            $score = $student->latestProgress?->score;
                        @endphp
                        <tr><td><div class="flex items-center gap-3"><x-student-avatar :student="$student" size="sm" /><span class="font-black text-slate-800">{{ $student->full_name }}</span></div></td><td>{{ $student->currentHalaqa?->name ?? '—' }}</td><td><span class="inline-flex min-w-12 justify-center rounded-lg px-2 py-1 text-xs font-black {{ $score === null ? 'bg-slate-100 text-slate-500' : ($score < 40 ? 'bg-red-50 text-red-700' : ($score < 60 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700')) }}">{{ $score !== null ? number_format($score, 1) : '—' }}</span></td><td>{{ $student->latestProgress ? number_format($student->latestProgress->memorized_percentage, 2).'%' : '—' }}</td><td>{{ $student->latestProgress ? number_format($student->latestProgress->attendance_rate, 1).'%' : '—' }}</td><td><a class="inline-flex items-center gap-1 font-black text-emerald-700 hover:text-emerald-900" href="{{ route('students.show', $student) }}">فتح الملف <span aria-hidden="true">←</span></a></td></tr>
                    @empty
                        <tr><td colspan="6"><div class="py-10 text-center text-sm text-slate-400">لا يوجد طلاب ضمن نطاق التحليل المحدد.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
