<div class="space-y-6">
    <header class="relative isolate overflow-hidden rounded-[2.25rem] bg-[linear-gradient(125deg,#052f29,#075844_62%,#0b6b50)] p-6 text-white shadow-[0_28px_80px_-42px_rgba(4,47,41,.8)] sm:p-8">
        <div aria-hidden="true" class="absolute inset-0 -z-10 opacity-10" style="background-image:radial-gradient(circle,#fff 1px,transparent 1.5px);background-size:27px 27px"></div>
        <div aria-hidden="true" class="absolute -left-28 -top-36 -z-10 size-80 rounded-full border-[55px] border-emerald-200/10"></div>
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <div class="mb-4 flex items-center gap-3"><span class="grid size-12 place-items-center rounded-2xl border border-white/10 bg-white/10 text-emerald-200"><x-nav-icon name="alerts" class="size-6" /></span><span class="text-xs font-black tracking-[.18em] text-emerald-200">الرصد والمتابعة الأكاديمية</span></div>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">مركز التنبيهات الأكاديمية</h1>
                <p class="mt-3 text-sm leading-7 text-emerald-100/65">قراءة منظمة للمؤشرات، أولوية واضحة للحالات، ومسار معالجة موثّق من الرصد حتى الإغلاق.</p>
            </div>
            @can('alerts.manage')
                <button wire:click="refreshInsights" wire:loading.attr="disabled" wire:target="refreshInsights" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-white px-5 text-sm font-black text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50 disabled:opacity-60" type="button">
                    <svg wire:loading.class="animate-spin" wire:target="refreshInsights" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12a8 8 0 1 1-2.3-5.7"/><path d="M20 4v6h-6"/></svg>
                    <span wire:loading.remove wire:target="refreshInsights">تحديث التحليلات</span><span wire:loading wire:target="refreshInsights">جارٍ تحليل السجلات…</span>
                </button>
            @endcan
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            @foreach([
                ['critical', 'حالات حرجة', 'تحتاج تدخلاً مباشرًا', 'bg-rose-400', 'text-rose-100'],
                ['warning', 'تحتاج متابعة', 'مؤشرات تستحق الانتباه', 'bg-amber-300', 'text-amber-100'],
                ['info', 'مؤشرات معلوماتية', 'للمراجعة والتنظيم', 'bg-sky-300', 'text-sky-100'],
            ] as [$severity, $label, $caption, $dot, $tone])
                <button type="button" wire:click="$set('severityFilter', '{{ $severityFilter === $severity ? '' : $severity }}')" class="flex items-center gap-3 rounded-2xl border p-4 text-right transition duration-300 {{ $severityFilter === $severity ? 'border-white/35 bg-white/15 ring-4 ring-white/5' : 'border-white/10 bg-black/10 hover:bg-white/10' }}">
                    <span class="relative flex size-3 shrink-0"><span class="absolute inline-flex size-full rounded-full {{ $dot }} opacity-40 {{ $severity === 'critical' && ($activeSeverityCounts[$severity] ?? 0) ? 'animate-ping' : '' }}"></span><span class="relative inline-flex size-3 rounded-full {{ $dot }}"></span></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-black">{{ $label }}</span><span class="mt-0.5 block truncate text-[10px] {{ $tone }} opacity-65">{{ $caption }}</span></span>
                    <strong class="text-2xl font-black">{{ $activeSeverityCounts[$severity] ?? 0 }}</strong>
                </button>
            @endforeach
        </div>
    </header>

    <x-flash-messages inline consume />

    @if($hasTeachingProfile)
        <section class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-emerald-800 text-white"><x-islamic-icon name="mosque" class="size-5" /></span>
                <div><p class="text-sm font-black text-emerald-950">{{ $teachingScope ? 'نطاق المحفّظ · طلاب حلقاتي فقط' : 'نطاق الإدارة · جميع الطلاب المتاحين' }}</p><p class="mt-1 text-xs text-emerald-700/70">يمكنك الانتقال بين مسؤوليتك التعليمية ونطاق الإدارة دون تغيير حسابك.</p></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('alerts.index', ['scope' => 'teaching']) }}" class="{{ $teachingScope ? 'btn-primary' : 'btn-secondary' }}">تنبيهات حلقاتي</a>
                @can('alerts.manage')<a href="{{ route('alerts.index') }}" class="{{ ! $teachingScope ? 'btn-primary' : 'btn-secondary' }}">كل تنبيهات المركز</a>@endcan
            </div>
        </section>
    @endif

    <section class="rounded-[2rem] border border-slate-200/80 bg-white p-4 shadow-[0_18px_60px_-42px_rgba(15,23,42,.3)] sm:p-5">
        <div class="mb-4 flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16M7 12h10m-7 7h4"/></svg></span><div><h2 class="text-sm font-black text-slate-800">تصفية قائمة العمل</h2><p class="text-[11px] text-slate-400">حدّد الحالات المطلوبة للوصول السريع.</p></div></div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label><span class="form-label">بحث عن طالب</span><input wire:model.live.debounce.350ms="search" class="form-input" placeholder="الاسم أو رقم الطالب"></label>
            <label><span class="form-label">حالة المعالجة</span><select wire:model.live="statusFilter" class="form-input"><option value="active">المفتوحة وقيد المتابعة</option>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
            <label><span class="form-label">درجة الأولوية</span><select wire:model.live="severityFilter" class="form-input"><option value="">كل الأولويات</option>@foreach($severities as $severity)<option value="{{ $severity->value }}">{{ $severity->label() }}</option>@endforeach</select></label>
            <label><span class="form-label">الحلقة</span><select wire:model.live="halaqaFilter" class="form-input"><option value="">كل الحلقات</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="space-y-4" aria-label="قائمة التنبيهات الأكاديمية">
        @forelse($alerts as $alert)
            @php
                $severityUi = match($alert->severity->value) {
                    'critical' => ['border-rose-200/90', 'bg-rose-500', 'bg-rose-50', 'text-rose-700', 'عاجل'],
                    'warning' => ['border-amber-200/90', 'bg-amber-500', 'bg-amber-50', 'text-amber-700', 'متابعة'],
                    default => ['border-sky-200/90', 'bg-sky-500', 'bg-sky-50', 'text-sky-700', 'معلومة'],
                };
                [$alertBorder, $alertAccent, $alertIconBackground, $alertText, $priorityLabel] = $severityUi;
            @endphp
            <article class="relative isolate overflow-hidden rounded-[2rem] border {{ $alertBorder }} bg-white shadow-[0_16px_55px_-40px_rgba(15,23,42,.4)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_24px_65px_-38px_rgba(15,23,42,.42)]" wire:key="alert-{{ $alert->id }}">
                <span aria-hidden="true" class="absolute inset-y-0 right-0 w-1.5 {{ $alertAccent }}"></span>
                <div class="p-5 pr-6 sm:p-6 sm:pr-7">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="relative shrink-0"><x-student-avatar :student="$alert->student" /><span class="absolute -bottom-1 -left-1 grid size-6 place-items-center rounded-lg border-2 border-white {{ $alertIconBackground }} {{ $alertText }}"><span class="size-2 rounded-full {{ $alertAccent }}"></span></span></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-[10px] font-black {{ $alertIconBackground }} {{ $alertText }}">{{ $priorityLabel }} · {{ $alert->severity->label() }}</span>
                                    <span class="badge-inactive">{{ $alert->status->label() }}</span>
                                    <span class="text-[11px] font-bold text-slate-400">تكرّر {{ $alert->occurrence_count }} مرة</span>
                                </div>
                                <h2 class="mt-3 text-lg font-black text-emerald-950"><a class="hover:text-emerald-700" href="{{ route('students.show', $alert->student) }}">{{ $alert->student->full_name }}</a></h2>
                                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-slate-600">{{ $alert->reason }}</p>
                                <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-bold text-slate-500">
                                    <span class="rounded-xl bg-slate-50 px-2.5 py-1.5">الحلقة: {{ $alert->halaqa?->name ?? 'غير محددة' }}</span>
                                    <span class="rounded-xl bg-slate-50 px-2.5 py-1.5">المحفّظ: {{ $alert->teacher?->user?->name ?? 'غير محدد' }}</span>
                                    <span class="rounded-xl bg-slate-50 px-2.5 py-1.5">الرصد: {{ $alert->generated_at->translatedFormat('j F Y، H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        @can('update', $alert)
                            @if(in_array($alert->status->value, ['open', 'acknowledged']))
                                <div class="w-full shrink-0 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 xl:w-80">
                                    <p class="mb-3 text-xs font-black text-slate-700">مسار المعالجة</p>
                                    @if($alert->status->value === 'open')<button wire:click="acknowledge({{ $alert->id }})" class="btn-secondary mb-3 w-full" type="button">بدء المتابعة الآن</button>@endif
                                    <label><span class="form-label">الإجراء المتخذ</span><textarea wire:model="resolutionNotes.{{ $alert->id }}" rows="2" class="form-input" placeholder="دوّن ما تم عمله مع الطالب"></textarea></label>
                                    <x-input-error :messages="$errors->get('resolutionNotes.'.$alert->id)" />
                                    <button wire:click="resolve({{ $alert->id }})" wire:loading.attr="disabled" wire:target="resolve({{ $alert->id }})" class="btn-primary mt-3 w-full" type="button">إغلاق بعد المعالجة</button>
                                </div>
                            @elseif($alert->resolution_notes)
                                <div class="w-full shrink-0 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 text-sm text-emerald-800 xl:w-80"><div class="flex items-center gap-2 font-black"><span class="grid size-6 place-items-center rounded-lg bg-emerald-600 text-xs text-white">✓</span>إجراء المعالجة</div><p class="mt-3 leading-7">{{ $alert->resolution_notes }}</p></div>
                            @endif
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <div class="grid min-h-72 place-items-center rounded-[2rem] border border-dashed border-emerald-200 bg-gradient-to-b from-emerald-50/60 to-white p-8 text-center"><div><span class="mx-auto grid size-16 place-items-center rounded-3xl bg-emerald-100 text-emerald-700"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/></svg></span><p class="mt-4 font-black text-emerald-950">لا توجد تنبيهات مطابقة</p><p class="mt-2 text-sm text-slate-500">لا توجد حالات تحتاج إجراءً ضمن عوامل التصفية الحالية.</p></div></div>
        @endforelse
        {{ $alerts->links() }}
    </section>
</div>
