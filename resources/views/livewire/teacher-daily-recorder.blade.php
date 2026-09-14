<div
    class="space-y-6 pb-24"
    x-on:daily-student-selected.window="$nextTick(() => document.getElementById('daily-session')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
    x-on:daily-history-opened.window="$nextTick(() => document.getElementById('student-history')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
>
    @php
        $enabledCount = collect($items)->where('enabled', true)->count();
        $completedRanges = collect($rangeSummaries)->where('complete', true)->where('valid', true)->count();
        $progressPercentage = $studentStats['total'] > 0 ? (int) round(($studentStats['recorded'] / $studentStats['total']) * 100) : 0;
        $attendanceLabel = collect($attendanceStatuses)->first(fn ($status) => $status->value === $attendanceStatus)?->label() ?? 'الحضور';
        $isAbsence = in_array($attendanceStatus, ['absent', 'excused'], true);
        $typeDescriptions = [
            'new_memorization' => 'المقدار الجديد الذي حفظه الطالب اليوم',
            'recent_revision' => 'مراجعة المحفوظ القريب وتثبيته',
            'old_revision' => 'مراجعة المحفوظ السابق والبعيد',
            'recitation' => 'تلاوة نظرًا من المصحف',
            'exam' => 'اختبار محدد في نطاق محفوظ',
            'tajweed' => 'تطبيق أحكام التجويد عمليًا',
        ];
    @endphp

    <header class="overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#064e3b_0%,#0b6b50_60%,#0f766e_100%)] p-6 text-white shadow-xl shadow-emerald-950/10 sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-2 text-xs font-black tracking-[0.2em] text-emerald-200">التسجيل الأكاديمي اليومي</p>
                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">جلسة واضحة من الطالب إلى الحفظ</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-emerald-50/80">اختر الطالب، ثبّت الحضور، ثم حدّد نطاق التسميع من قائمة سور قابلة للبحث. سيُراجع النظام البداية والنهاية قبل الحفظ.</p>
            </div>
            <div class="min-w-64 space-y-3">
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                    <div class="mb-2 flex items-center justify-between text-sm"><span class="font-bold text-emerald-50">إنجاز الحلقة</span><strong dir="ltr">{{ $studentStats['recorded'] }} / {{ $studentStats['total'] }}</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-black/15"><div class="h-full rounded-full bg-emerald-300 transition-all duration-500" style="width: {{ $progressPercentage }}%"></div></div>
                    <p class="mt-2 text-xs text-emerald-100">{{ $studentStats['waiting'] }} طلاب بانتظار التسجيل</p>
                </div>
                @can('recitations.export')
                    <button wire:click="$toggle('showExportPanel')" type="button" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white px-4 py-3 text-sm font-black text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>
                        تصدير سجلات الحفظ
                    </button>
                @endcan
            </div>
        </div>
    </header>

    @if($showExportPanel)
        <section class="panel overflow-hidden border-emerald-200 bg-gradient-to-l from-emerald-50/80 to-white" wire:key="memorization-export-panel">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl"><p class="eyebrow">Excel</p><h2 class="section-title">تصدير سجلات الحفظ والمراجعة</h2><p class="page-subtitle">يشمل الملف رقم الطالب، التاريخ واليوم، الاسم، الهوية، الحفظ، المراجعة والتقييم، ضمن طلابك وسجلاتك فقط.</p></div>
                <div class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-2xl lg:grid-cols-[1fr_1fr_auto]">
                    <label><span class="form-label">من تاريخ</span><input wire:model="exportDateFrom" type="date" max="{{ today()->toDateString() }}" class="form-input"><x-input-error :messages="$errors->get('exportDateFrom')" /></label>
                    <label><span class="form-label">إلى تاريخ</span><input wire:model="exportDateTo" type="date" max="{{ today()->toDateString() }}" class="form-input"><x-input-error :messages="$errors->get('exportDateTo')" /></label>
                    <button wire:click="exportMemorizationRecords" wire:loading.attr="disabled" wire:target="exportMemorizationRecords" type="button" class="btn-primary self-end sm:col-span-2 lg:col-span-1"><span wire:loading.remove wire:target="exportMemorizationRecords">إنشاء الملف</span><span wire:loading wire:target="exportMemorizationRecords">جارٍ الإعداد…</span></button>
                </div>
            </div>
            @if($latestExport)
                <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between" @if($latestExport->status === 'preparing') wire:poll.10s @endif>
                    <div><p class="text-sm font-black text-emerald-950">ملف سجلات الحفظ</p><p class="mt-1 text-xs text-slate-500">{{ $latestExport->rows_count ?? 0 }} سجل · {{ $latestExport->created_at->translatedFormat('j F Y، H:i') }}</p></div>
                    @if($latestExport->status === 'ready' && $latestExport->privateFile)<a href="{{ route('private-files.show', $latestExport->privateFile) }}" class="btn-primary">تنزيل ملف Excel</a>@elseif($latestExport->status === 'failed')<span class="inline-flex rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700">تعذّر إعداد الملف</span>@else<span class="badge-warning">قيد الإعداد</span>@endif
                </div>
            @endif
        </section>
    @endif

    <ol class="grid gap-3 sm:grid-cols-3" aria-label="مراحل التسجيل">
        @foreach([
            ['label' => 'الإعداد', 'hint' => 'التاريخ والحلقة', 'done' => $recordDate !== '' && $halaqaId !== ''],
            ['label' => 'الطالب', 'hint' => $selectedStudent?->full_name ?? 'اختر طالبًا', 'done' => $studentId !== ''],
            ['label' => 'الجلسة', 'hint' => $studentId ? 'الحضور والتسميع' : 'تبدأ بعد اختيار الطالب', 'done' => $studentId !== '' && ($isAbsence || $completedRanges > 0)],
        ] as $stepIndex => $step)
            <li class="flex items-center gap-3 rounded-2xl border px-4 py-3 transition {{ $step['done'] ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl text-sm font-black {{ $step['done'] ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $step['done'] ? '✓' : $stepIndex + 1 }}</span>
                <span class="min-w-0"><strong class="block text-sm text-slate-800">{{ $step['label'] }}</strong><span class="block truncate text-xs text-slate-500">{{ $step['hint'] }}</span></span>
            </li>
        @endforeach
    </ol>

    <x-flash-messages inline consume />

    @if ($errors->any())
        <x-feedback-alert type="error" title="تعذّر إكمال التسجيل" :duration="0">
            <p>راجع الحقول المعلّمة قبل المحاولة مرة أخرى:</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs font-bold">
                @foreach(array_slice(array_unique($errors->all()), 0, 5) as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </x-feedback-alert>
    @endif

    <section class="panel">
        <div class="mb-5 flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-2xl bg-emerald-100 font-black text-emerald-800">1</span>
            <div><p class="eyebrow !mb-0">إعداد الجلسة</p><h2 class="section-title">أين ومتى؟</h2></div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <label><span class="form-label">تاريخ التسجيل</span><input wire:model.live="recordDate" type="date" max="{{ today()->toDateString() }}" class="form-input"><x-input-error :messages="$errors->get('recordDate')" /></label>
            <label><span class="form-label">الحلقة المسندة إليّ</span><select wire:model.live="halaqaId" class="form-input"><option value="">لا توجد حلقة مسندة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaId')" /></label>
        </div>
    </section>

    <section class="panel">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-2xl bg-emerald-100 font-black text-emerald-800">2</span>
                <div><p class="eyebrow !mb-0">طلاب الحلقة</p><h2 class="section-title">من الطالب؟</h2></div>
            </div>
            <label class="relative w-full sm:w-72">
                <span class="sr-only">بحث عن طالب</span>
                <svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input wire:model.live.debounce.250ms="studentSearch" class="form-input pr-10" placeholder="ابحث بالاسم أو رقم الطالب">
            </label>
        </div>

        <div class="mb-4 flex flex-wrap gap-2 text-xs font-bold">
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-600">الكل {{ $studentStats['total'] }}</span>
            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">تم {{ $studentStats['recorded'] }}</span>
            <span class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-700">بانتظارك {{ $studentStats['waiting'] }}</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="recordDate,halaqaId,studentSearch,selectStudent">
            @forelse($students as $student)
                <article
                    class="group relative overflow-hidden rounded-2xl border p-4 text-right transition duration-200 {{ (int) $studentId === $student->id ? 'border-emerald-600 bg-emerald-50 ring-2 ring-emerald-600/10' : ($student->recorded_for_date ? 'border-emerald-100 bg-emerald-50/35' : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md') }}"
                    wire:key="daily-student-{{ $student->id }}"
                >
                    <span class="flex items-start gap-3">
                        <x-student-avatar :student="$student" size="sm" :badge="$student->recorded_for_date ? '✓' : null" @class(['opacity-75' => $student->recorded_for_date]) />
                        <span class="min-w-0"><span class="block truncate font-black text-emerald-950">{{ $student->full_name }}</span><span class="mt-1 block text-xs text-slate-500" dir="ltr">{{ $student->student_number }}</span></span>
                    </span>
                    <span class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                        <span>السجلات السابقة: {{ $student->daily_records_count }}</span>
                        <span dir="ltr">{{ $student->daily_records_max_record_date ? substr($student->daily_records_max_record_date, 0, 10) : '—' }}</span>
                    </span>
                    <span class="mt-3 grid grid-cols-2 gap-2">
                        @if($student->recorded_for_date)
                            <span class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-100 px-2 text-xs font-black text-emerald-800">تم تسجيل اليوم</span>
                        @else
                            <button wire:click="selectStudent({{ $student->id }})" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-700 px-2 text-xs font-black text-white transition hover:bg-emerald-800">تسجيل اليوم</button>
                        @endif
                        <button wire:click="showStudentHistory({{ $student->id }})" type="button" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 text-xs font-black text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg>
                            الكشف السابق
                        </button>
                    </span>
                </article>
            @empty
                <div class="col-span-full py-10 text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-xl">⌕</span>
                    <p class="mt-3 text-sm font-bold text-slate-600">لا يوجد طلاب مطابقون</p>
                    <p class="mt-1 text-xs text-slate-400">تحقق من الحلقة والتاريخ أو غيّر عبارة البحث.</p>
                </div>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('studentId')" />
        <x-input-error :messages="$errors->get('historyStudentId')" />
    </section>

    @if($historyStudent)
        <section
            id="student-history"
            class="panel scroll-mt-24 overflow-hidden !p-0"
            wire:key="student-history-{{ $historyStudent->id }}"
            x-data="{ visible: false }"
            x-init="$nextTick(() => visible = true)"
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-3 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
        >
            <div class="relative overflow-hidden bg-[linear-gradient(135deg,#ecfdf5_0%,#ffffff_55%,#f0fdfa_100%)] p-5 sm:p-6">
                <span class="pointer-events-none absolute -left-12 -top-16 size-44 rounded-full bg-emerald-200/25 blur-2xl"></span>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-student-avatar :student="$historyStudent" />
                        <div class="min-w-0">
                            <p class="text-xs font-black text-emerald-700">الكشف السابق للطالب</p>
                            <h2 class="truncate text-xl font-black text-emerald-950">{{ $historyStudent->full_name }}</h2>
                            <p class="mt-1 text-xs text-slate-500"><span dir="ltr">{{ $historyStudent->student_number }}</span> · مرتب من الأحدث إلى الأقدم</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @can('view', $historyStudent)
                            <a href="{{ route('students.show', $historyStudent) }}" class="btn-secondary text-xs">فتح ملف الطالب الكامل</a>
                        @endcan
                        <button wire:click="closeStudentHistory" type="button" class="btn-ghost text-xs font-black text-slate-500">إغلاق الكشف</button>
                    </div>
                </div>

                <div class="relative mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white bg-white/80 p-3 shadow-sm">
                        <p class="text-[11px] font-bold text-slate-500">إجمالي الجلسات</p>
                        <p class="mt-1 text-2xl font-black text-emerald-900">{{ $historySummary['total'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-white bg-white/80 p-3 shadow-sm">
                        <p class="text-[11px] font-bold text-slate-500">نتائج التصفية</p>
                        <p class="mt-1 text-2xl font-black text-emerald-900">{{ $historySummary['filtered'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-white bg-white/80 p-3 shadow-sm">
                        <p class="text-[11px] font-bold text-slate-500">آخر جلسة مسجلة</p>
                        <p class="mt-2 text-sm font-black text-emerald-900" dir="ltr">{{ $historySummary['last_date'] ? substr($historySummary['last_date'], 0, 10) : 'لا توجد' }}</p>
                    </div>
                </div>
            </div>

            <div class="border-y border-slate-100 bg-white px-5 py-3 sm:px-6">
                <div class="flex flex-wrap gap-2" aria-label="تصفية الكشف السابق">
                    @foreach([
                        'all' => ['الكل', 'كل الحضور والتسميع'],
                        'memorization' => ['الحفظ الجديد', 'جلسات الحفظ فقط'],
                        'revision' => ['المراجعة', 'القريبة والقديمة'],
                    ] as $filterValue => $filterMeta)
                        <button
                            wire:click="setHistoryFilter('{{ $filterValue }}')"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-right transition {{ $historyFilter === $filterValue ? 'border-emerald-600 bg-emerald-700 text-white shadow-md shadow-emerald-900/10' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50' }}"
                        >
                            <strong class="block text-xs">{{ $filterMeta[0] }}</strong>
                            <span class="mt-0.5 block text-[10px] opacity-70">{{ $filterMeta[1] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-4 p-5 sm:p-6" wire:loading.class="opacity-50" wire:target="setHistoryFilter,loadMoreHistory">
                @forelse($historyRecords as $record)
                    @php
                        $attendanceValue = $record->attendance?->status?->value;
                        $attendanceStyle = match($attendanceValue) {
                            'present' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
                            'late' => 'bg-amber-50 text-amber-700 ring-amber-600/10',
                            'excused' => 'bg-sky-50 text-sky-700 ring-sky-600/10',
                            default => 'bg-rose-50 text-rose-700 ring-rose-600/10',
                        };
                        $evaluationStyle = match($record->general_evaluation?->value) {
                            'excellent' => 'bg-emerald-100 text-emerald-800',
                            'very_good' => 'bg-teal-100 text-teal-800',
                            'good' => 'bg-amber-100 text-amber-800',
                            default => 'bg-rose-100 text-rose-800',
                        };
                    @endphp
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white transition duration-200 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-950/5" wire:key="student-history-record-{{ $record->id }}">
                        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <time class="font-black text-emerald-950" datetime="{{ $record->record_date->toDateString() }}">{{ $record->record_date->translatedFormat('l، j F Y') }}</time>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $record->halaqa?->name ?? 'حلقة غير محددة' }} · {{ $record->teacher?->user?->name ?? 'محفّظ غير محدد' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $attendanceStyle }}">{{ $record->attendance?->status?->label() ?? 'دون حضور' }}</span>
                                @if($record->general_evaluation)
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $evaluationStyle }}">التقييم العام: {{ $record->general_evaluation->label() }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3 p-4">
                            @forelse($record->recitationItems as $item)
                                @php
                                    $startSurahName = $item->startAyah?->surah?->name_arabic;
                                    $endSurahName = $item->endAyah?->surah?->name_arabic;
                                    $errorCount = $item->memorization_errors + $item->tajweed_errors;
                                @endphp
                                <div class="grid gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-3 lg:grid-cols-[9rem_minmax(0,1fr)_auto] lg:items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-800">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                                        </span>
                                        <strong class="text-sm text-emerald-950">{{ $item->type->label() }}</strong>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold leading-6 text-slate-700">
                                            @if($startSurahName && $endSurahName)
                                                من {{ $startSurahName }} آية {{ $item->startAyah->ayah_number }} إلى {{ $endSurahName }} آية {{ $item->endAyah->ayah_number }}
                                            @else
                                                نطاق التسميع غير متاح
                                            @endif
                                        </p>
                                        @if($item->notes)<p class="mt-1 text-xs leading-5 text-slate-500">{{ $item->notes }}</p>@endif
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-[11px] font-bold lg:justify-end">
                                        <span class="rounded-lg bg-white px-2.5 py-1.5 text-emerald-700 shadow-sm">{{ $item->evaluation->label() }}</span>
                                        <span class="rounded-lg bg-white px-2.5 py-1.5 text-slate-600 shadow-sm">{{ $errorCount }} {{ $errorCount === 1 ? 'خطأ' : 'أخطاء' }}</span>
                                        @if($item->hesitation_count || $item->teacher_prompt_count)
                                            <span class="rounded-lg bg-white px-2.5 py-1.5 text-slate-600 shadow-sm">تردد {{ $item->hesitation_count }} · تلقين {{ $item->teacher_prompt_count }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">سُجل الحضور دون بنود تسميع.</div>
                            @endforelse

                            @if($record->attendance?->notes || $record->notes)
                                <div class="rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-xs leading-6 text-amber-950">
                                    @if($record->attendance?->notes)<p><strong>ملاحظة الحضور:</strong> {{ $record->attendance->notes }}</p>@endif
                                    @if($record->notes)<p><strong>ملاحظة الجلسة:</strong> {{ $record->notes }}</p>@endif
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/60 py-10 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                        </span>
                        <p class="mt-3 font-black text-slate-700">لا توجد سجلات ضمن هذا التصنيف</p>
                        <p class="mt-1 text-xs text-slate-400">ستظهر الجلسات هنا فور حفظها واعتمادها.</p>
                    </div>
                @endforelse

                @if($historySummary['filtered'] > $historyRecords->count() && $historyRecords->count() < 30)
                    <button wire:click="loadMoreHistory" wire:loading.attr="disabled" wire:target="loadMoreHistory" type="button" class="btn-secondary mx-auto flex min-w-48 justify-center">
                        <span wire:loading.remove wire:target="loadMoreHistory">عرض 5 سجلات أقدم</span>
                        <span wire:loading wire:target="loadMoreHistory">جارٍ التحميل…</span>
                    </button>
                @elseif($historySummary['filtered'] > $historyRecords->count())
                    <p class="text-center text-xs text-slate-400">يعرض الكشف السريع أحدث 30 جلسة. افتح ملف الطالب الكامل لبقية التاريخ.</p>
                @endif
            </div>
        </section>
    @endif

    @if($studentId && $selectedStudent)
        <form id="daily-session" wire:submit="save" class="scroll-mt-24 space-y-6">
            <section class="panel overflow-hidden !p-0">
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-emerald-50/60 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <x-student-avatar :student="$selectedStudent" />
                        <div><p class="text-xs font-bold text-emerald-700">تسجّل الآن للطالب</p><h2 class="text-lg font-black text-emerald-950">{{ $selectedStudent->full_name }}</h2><p class="text-xs text-slate-500" dir="ltr">{{ $selectedStudent->student_number }}</p></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button wire:click="showStudentHistory({{ $selectedStudent->id }})" type="button" class="btn-secondary inline-flex items-center gap-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg>
                            الكشف السابق
                        </button>
                        <button wire:click="clearSelectedStudent" type="button" class="btn-ghost font-black text-slate-600">تغيير الطالب</button>
                    </div>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-2xl bg-emerald-100 font-black text-emerald-800">3</span><div><p class="eyebrow !mb-0">الحضور</p><h2 class="section-title">حالة الطالب اليوم</h2></div></div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($attendanceStatuses as $status)
                            @php
                                $statusMeta = match($status->value) {
                                    'present' => ['icon' => '✓', 'active' => 'border-emerald-500 bg-emerald-50 text-emerald-900'],
                                    'late' => ['icon' => '◷', 'active' => 'border-amber-500 bg-amber-50 text-amber-900'],
                                    'excused' => ['icon' => '!', 'active' => 'border-sky-500 bg-sky-50 text-sky-900'],
                                    default => ['icon' => '×', 'active' => 'border-red-400 bg-red-50 text-red-900'],
                                };
                            @endphp
                            <label class="cursor-pointer rounded-2xl border p-3 transition {{ $attendanceStatus === $status->value ? $statusMeta['active'].' ring-2 ring-current/10' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                                <input wire:model.live="attendanceStatus" type="radio" value="{{ $status->value }}" class="sr-only">
                                <span class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-xl bg-white/80 text-lg font-black shadow-sm">{{ $statusMeta['icon'] }}</span><strong>{{ $status->label() }}</strong></span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('attendanceStatus')" />
                    <div class="grid gap-4 lg:grid-cols-2">
                        @if(! $isAbsence)
                            <label><span class="form-label">التقييم العام <span class="font-normal text-slate-400">(اختياري)</span></span><select wire:model="generalEvaluation" class="form-input"><option value="">دون تقييم عام</option>@foreach($evaluations as $evaluation)<option value="{{ $evaluation->value }}">{{ $evaluation->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('generalEvaluation')" /></label>
                        @else
                            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-bold text-sky-800">لا يوجد تقييم أكاديمي للطالب الغائب.</div>
                        @endif
                        <label><span class="form-label">ملاحظة الحضور <span class="font-normal text-slate-400">(اختياري)</span></span><input wire:model="attendanceNotes" class="form-input" placeholder="مثال: حضر متأخرًا عشر دقائق"><x-input-error :messages="$errors->get('attendanceNotes')" /></label>
                    </div>
                </div>
            </section>

            @if(! $isAbsence)
                <section class="space-y-4">
                    <div class="panel">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div><p class="eyebrow">بنود الجلسة</p><h2 class="section-title">ماذا سمّع الطالب؟</h2><p class="page-subtitle">الحفظ الجديد مفعّل تلقائيًا. أضف المراجعة أو الاختبار عند الحاجة.</p></div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">{{ $enabledCount }} بنود مفعّلة</span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($items as $index => $item)
                                @php $type = $recitationTypes->get($item['type']); @endphp
                                <button wire:click="toggleItem({{ $index }})" type="button" class="flex items-center gap-3 rounded-2xl border p-3 text-right transition {{ $item['enabled'] ? 'border-emerald-500 bg-emerald-50 text-emerald-950 ring-2 ring-emerald-500/10' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200' }}">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl text-lg font-black {{ $item['enabled'] ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">{{ $item['enabled'] ? '✓' : '+' }}</span>
                                    <span><strong class="block text-sm">{{ $type?->label() }}</strong><span class="mt-0.5 block text-[11px] leading-5 text-slate-500">{{ $typeDescriptions[$item['type']] }}</span></span>
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('items')" />
                    </div>

                    @foreach($items as $index => $item)
                        @continue(!$item['enabled'])
                        @php
                            $startSurah = $surahs->firstWhere('id', (int) $item['start_surah_id']);
                            $endSurah = $surahs->firstWhere('id', (int) $item['end_surah_id']);
                            $endSurahs = $startSurah ? $surahs->where('id', '>=', $startSurah->id)->values() : $surahs;
                            $type = $recitationTypes->get($item['type']);
                            $summary = $rangeSummaries[$index];
                            $endMinimum = $startSurah && $endSurah && $startSurah->id === $endSurah->id && (int) $item['start_ayah_number'] > 0 ? (int) $item['start_ayah_number'] : 1;
                        @endphp
                        <article class="panel overflow-visible !p-0" wire:key="recitation-item-{{ $item['type'] }}">
                            <div class="flex items-center justify-between gap-3 rounded-t-3xl border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                                <div><p class="text-xs font-bold text-emerald-700">بند تسميع</p><h3 class="text-lg font-black text-emerald-950">{{ $type?->label() }}</h3></div>
                                <button wire:click="toggleItem({{ $index }})" type="button" class="btn-ghost text-xs text-red-600 hover:bg-red-50 hover:text-red-700">إزالة البند</button>
                            </div>

                            <div class="space-y-6 p-5 sm:p-6">
                                <div>
                                    <div class="mb-4 flex items-start gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-xl bg-emerald-100 text-sm font-black text-emerald-800">أ</span><div><h4 class="font-black text-slate-800">حدّد نطاق التسميع</h4><p class="mt-1 text-xs leading-5 text-slate-500">ابحث عن السورة بالاسم أو الرقم. عند اختيار البداية ستُضبط سورة النهاية تلقائيًا، ويمكنك توسيع النطاق إلى سورة لاحقة.</p></div></div>

                                    <div class="grid gap-4 rounded-3xl border border-emerald-100 bg-emerald-50/35 p-4 xl:grid-cols-[minmax(0,1fr)_10rem_2.5rem_minmax(0,1fr)_10rem] xl:items-end">
                                        <label>
                                            <span class="form-label">سورة البداية</span>
                                            <select wire:model.live="items.{{ $index }}.start_surah_id" class="form-input">
                                                <option value="">اختر سورة البداية</option>
                                                @foreach($surahs as $surah)<option value="{{ $surah->id }}">{{ $surah->id }}. {{ $surah->name_arabic }} — {{ $surah->verses_count }} آية</option>@endforeach
                                            </select>
                                            <span class="mt-1 block text-[11px] text-slate-400">يمكنك الكتابة بعد فتح القائمة للانتقال السريع إلى اسم السورة.</span>
                                            <x-input-error :messages="$errors->get('items.'.$index.'.start_surah_id')" />
                                        </label>
                                        <label><span class="form-label">آية البداية</span><select wire:key="start-ayah-{{ $item['type'] }}-{{ $item['start_surah_id'] ?: 'none' }}" wire:model.live="items.{{ $index }}.start_ayah_number" class="form-input" @disabled(!$startSurah)><option value="">{{ $startSurah ? 'اختر رقم الآية' : 'اختر السورة أولًا' }}</option>@if($startSurah)@for($ayah = 1; $ayah <= $startSurah->verses_count; $ayah++)<option value="{{ $ayah }}">الآية {{ $ayah }}</option>@endfor@endif</select><x-input-error :messages="$errors->get('items.'.$index.'.start_ayah_number')" /></label>

                                        <button wire:click="copyStartToEnd({{ $index }})" type="button" class="mb-1 hidden size-10 place-items-center rounded-xl border border-emerald-200 bg-white text-lg font-black text-emerald-700 shadow-sm hover:bg-emerald-50 xl:grid" title="اجعل النهاية مثل البداية" aria-label="اجعل النهاية مثل البداية">←</button>

                                        <label>
                                            <span class="form-label">سورة النهاية</span>
                                            <select wire:model.live="items.{{ $index }}.end_surah_id" class="form-input" @disabled(!$startSurah)>
                                                <option value="">{{ $startSurah ? 'اختر سورة النهاية' : 'اختر سورة البداية أولًا' }}</option>
                                                @foreach($endSurahs as $surah)<option value="{{ $surah->id }}">{{ $surah->id }}. {{ $surah->name_arabic }} — {{ $surah->verses_count }} آية</option>@endforeach
                                            </select>
                                            <span wire:loading wire:target="items.{{ $index }}.start_surah_id,items.{{ $index }}.end_surah_id" class="mt-1 block text-[11px] font-bold text-emerald-700">جارٍ تحميل أرقام الآيات…</span>
                                            <x-input-error :messages="$errors->get('items.'.$index.'.end_surah_id')" />
                                        </label>
                                        <label><span class="form-label">آية النهاية</span><select wire:key="end-ayah-{{ $item['type'] }}-{{ $item['end_surah_id'] ?: 'none' }}-{{ $endMinimum }}" wire:model.live="items.{{ $index }}.end_ayah_number" class="form-input" @disabled(!$endSurah)><option value="">{{ $endSurah ? 'اختر رقم الآية' : 'اختر السورة أولًا' }}</option>@if($endSurah)@for($ayah = $endMinimum; $ayah <= $endSurah->verses_count; $ayah++)<option value="{{ $ayah }}">الآية {{ $ayah }}</option>@endfor@endif</select><x-input-error :messages="$errors->get('items.'.$index.'.end_ayah_number')" /></label>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3 rounded-2xl border px-4 py-3 {{ $summary['complete'] && $summary['valid'] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : ($summary['complete'] ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200 bg-slate-50 text-slate-500') }}">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-sm font-black shadow-sm">{{ $summary['complete'] && $summary['valid'] ? '✓' : '…' }}</span>
                                        <span class="min-w-0"><strong class="block text-sm">{{ $summary['complete'] && $summary['valid'] ? 'النطاق جاهز للحفظ' : 'ملخص النطاق' }}</strong><span class="block text-xs leading-5">{{ $summary['label'] }} @if($summary['valid']) · {{ $summary['count'] }} آية @endif</span></span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-5">
                                    <div class="mb-4 flex items-start gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-xl bg-amber-100 text-sm font-black text-amber-800">ب</span><div><h4 class="font-black text-slate-800">قيّم الأداء</h4><p class="mt-1 text-xs text-slate-500">اختر التقييم وسجّل مؤشرات الدقة عند الحاجة.</p></div></div>
                                    <div class="grid gap-4 lg:grid-cols-[1.1fr_2fr]">
                                        <div>
                                            <span class="form-label">تقييم هذا البند</span>
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach($evaluations as $evaluation)
                                                    <label wire:key="evaluation-{{ $item['type'] }}-{{ $evaluation->value }}" class="cursor-pointer rounded-xl border px-3 py-2.5 text-center text-xs font-bold transition duration-200 {{ $item['evaluation'] === $evaluation->value ? 'border-emerald-500 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-500/10' : 'border-slate-200 bg-white text-slate-600 hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50/40' }}"><input wire:model.live="items.{{ $index }}.evaluation" type="radio" value="{{ $evaluation->value }}" class="sr-only"><span class="inline-flex items-center justify-center gap-1.5"><span class="grid size-4 place-items-center rounded-full border text-[9px] {{ $item['evaluation'] === $evaluation->value ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 bg-white text-transparent' }}">✓</span>{{ $evaluation->label() }}</span></label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <label><span class="form-label text-xs">أخطاء الحفظ</span><input wire:model="items.{{ $index }}.memorization_errors" type="number" min="0" max="999" inputmode="numeric" class="form-input text-center"></label>
                                            <label><span class="form-label text-xs">أخطاء التجويد</span><input wire:model="items.{{ $index }}.tajweed_errors" type="number" min="0" max="999" inputmode="numeric" class="form-input text-center"></label>
                                            <label><span class="form-label text-xs">مرات التردد</span><input wire:model="items.{{ $index }}.hesitation_count" type="number" min="0" max="999" inputmode="numeric" class="form-input text-center"></label>
                                            <label><span class="form-label text-xs">مرات التلقين</span><input wire:model="items.{{ $index }}.teacher_prompt_count" type="number" min="0" max="999" inputmode="numeric" class="form-input text-center"></label>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('items.'.$index.'.evaluation')" />
                                    <x-input-error :messages="array_merge($errors->get('items.'.$index.'.memorization_errors'), $errors->get('items.'.$index.'.tajweed_errors'), $errors->get('items.'.$index.'.hesitation_count'), $errors->get('items.'.$index.'.teacher_prompt_count'))" />
                                    <label class="mt-4 block"><span class="form-label">ملاحظة خاصة بالبند <span class="font-normal text-slate-400">(اختياري)</span></span><input wire:model="items.{{ $index }}.notes" class="form-input" placeholder="نقطة قوة أو أمر يحتاج متابعة"><x-input-error :messages="$errors->get('items.'.$index.'.notes')" /></label>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>
            @else
                <section @class(['rounded-3xl border p-6 text-center', 'border-sky-200 bg-sky-50' => $attendanceStatus === 'excused', 'border-red-200 bg-red-50' => $attendanceStatus === 'absent'])>
                    <span @class(['mx-auto grid size-12 place-items-center rounded-2xl bg-white text-2xl font-black shadow-sm', 'text-sky-600' => $attendanceStatus === 'excused', 'text-red-500' => $attendanceStatus === 'absent'])>{{ $attendanceStatus === 'excused' ? '!' : '×' }}</span>
                    <h2 @class(['mt-3 text-lg font-black', 'text-sky-900' => $attendanceStatus === 'excused', 'text-red-900' => $attendanceStatus === 'absent'])>{{ $attendanceStatus === 'excused' ? 'الطالب غائب بعذر' : 'الطالب مسجّل غائبًا' }}</h2>
                    <p @class(['mt-1 text-sm', 'text-sky-700' => $attendanceStatus === 'excused', 'text-red-700' => $attendanceStatus === 'absent'])>سيُحفظ الغياب دون أي تسميع أو تقييم. أضف ملاحظة إن لزم ثم احفظ الحضور.</p>
                </section>
            @endif

            <section class="panel">
                <label><span class="form-label">ملاحظات الجلسة <span class="font-normal text-slate-400">(اختياري)</span></span><textarea wire:model="notes" rows="3" class="form-input" placeholder="ملاحظات عامة تساعد في متابعة الطالب في الجلسة القادمة"></textarea><x-input-error :messages="$errors->get('notes')" /></label>
            </section>

            <div class="sticky bottom-4 z-30 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-[0_16px_45px_-18px_rgba(15,23,42,.4)] backdrop-blur">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><p class="truncate text-sm font-black text-emerald-950">{{ $selectedStudent->full_name }} · {{ $attendanceLabel }}</p><p class="text-xs text-slate-500">{{ $isAbsence ? 'سيُحفظ الحضور دون تسميع أو تقييم' : $enabledCount.' بنود مفعّلة · '.$completedRanges.' نطاقات مكتملة' }}</p></div>
                    <button class="btn-primary min-w-44" wire:loading.attr="disabled" type="submit"><span wire:loading.remove wire:target="save">تأكيد وحفظ السجل</span><span wire:loading wire:target="save">جارٍ التحقق والحفظ…</span></button>
                </div>
            </div>
        </form>
    @endif
</div>
