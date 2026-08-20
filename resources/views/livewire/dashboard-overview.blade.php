@php
    $stats = $analytics['stats'];
    $role = auth()->user()->getRoleNames()->first();
    $dashboardLabel = match($role) {
        'teacher' => 'لوحة المحفظ',
        'academic-supervisor' => 'لوحة المشرف الأكاديمي',
        'student', 'guardian' => 'تحليل تقدم الطالب',
        default => 'لوحة الإدارة',
    };
@endphp

<div class="space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="eyebrow">{{ $dashboardLabel }}</p><h1 class="page-title">السلام عليكم، {{ auth()->user()->name }}</h1><p class="page-subtitle">مؤشرات محسوبة من سجلات الحضور والتسميع الفعلية، وليست قيمًا تجريبية.</p></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900">PHASE 3 · التحليل والتنبيهات</div>
    </header>

    <section class="panel">
        <div class="mb-4"><p class="eyebrow">الفلاتر</p><h2 class="section-title">نطاق التحليل</h2></div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <label><span class="form-label">من</span><input wire:model.live="dateFrom" type="date" class="form-input"></label>
            <label><span class="form-label">إلى</span><input wire:model.live="dateTo" type="date" class="form-input"></label>
            @can('organization.view')
                <label><span class="form-label">الفرع</span><select wire:model.live="branchId" class="form-input"><option value="">الكل</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
                <label><span class="form-label">الحلقة</span><select wire:model.live="halaqaId" class="form-input"><option value="">الكل</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></label>
                <label><span class="form-label">المحفظ</span><select wire:model.live="teacherId" class="form-input"><option value="">الكل</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select></label>
                <label><span class="form-label">البرنامج</span><select wire:model.live="program" class="form-input"><option value="">الكل</option>@foreach($programs as $programName)<option value="{{ $programName }}">{{ $programName }}</option>@endforeach</select></label>
            @endcan
            <label><span class="form-label">الطالب</span><select wire:model.live="studentId" class="form-input"><option value="">الكل</option>@foreach($studentsForFilter as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select></label>
        </div>
    </section>

    @if($analytics['teacher_today'])
        <section class="panel bg-emerald-950 text-white">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-bold text-emerald-200">حلقة اليوم</p><h2 class="mt-1 text-2xl font-black">{{ $analytics['teacher_today']['recorded'] }} من {{ $analytics['teacher_today']['students'] }} طالب تم تسجيلهم</h2><p class="mt-2 text-sm text-emerald-100">متبقٍ {{ $analytics['teacher_today']['missing'] }} طالب · {{ $analytics['teacher_today']['halaqas']->pluck('name')->join('، ') ?: 'لا توجد حلقة مسندة اليوم' }}</p></div><a href="{{ route('teacher.daily') }}" class="rounded-xl bg-white px-5 py-3 text-center text-sm font-black text-emerald-950">بدء تسجيل جلسات اليوم</a></div>
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="مؤشرات الأداء">
        @foreach([
            ['الطلاب الفعالون', $stats['active_students'], '♙'],
            ['المحفظون', $stats['teachers'], '♟'],
            ['الحلقات', $stats['halaqas'], '◌'],
            ['الحضور في الفترة', $stats['present'], '✓'],
            ['الغياب في الفترة', $stats['absent'], '!'],
            ['جلسات التسميع', $stats['daily_records'], '◉'],
            ['الحفظ الجديد', $stats['new_memorization'], '◈'],
            ['المراجعات', $stats['revisions'], '↻'],
            ['بحاجة للمتابعة', $stats['needs_followup'], '⚑'],
            ['التنبيهات المفتوحة', $stats['open_alerts'], '△'],
        ] as [$label, $value, $icon])
            <article class="stat-card"><div class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-xl font-black text-emerald-800">{{ $icon }}</div><div><p class="text-3xl font-black text-emerald-950">{{ number_format($value) }}</p><p class="mt-1 text-sm text-slate-500">{{ $label }}</p></div></article>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <article class="panel">
            <div class="mb-6 flex items-end justify-between"><div><p class="eyebrow">اتجاه أسبوعي</p><h2 class="section-title">جودة التسميع</h2></div><p class="text-3xl font-black text-emerald-800">{{ number_format($analytics['evaluation_average'], 1) }}<span class="text-sm text-slate-400">/100</span></p></div>
            <div class="flex h-52 items-end gap-3 border-b border-slate-200 px-2">
                @foreach($analytics['trend'] as $point)
                    <div class="flex h-full flex-1 flex-col justify-end gap-2 text-center" title="{{ $point['date'] }}: {{ $point['value'] }}"><span class="text-xs font-bold text-slate-500">{{ number_format($point['value']) }}</span><div class="min-h-1 rounded-t-xl bg-emerald-600" style="height: {{ max(2, $point['value']) }}%"></div><span class="pb-2 text-xs text-slate-400">{{ $point['label'] }}</span></div>
                @endforeach
            </div>
            <div class="mt-6"><p class="mb-3 text-sm font-black text-slate-700">الاتجاه الشهري</p><div class="grid grid-cols-3 gap-2 sm:grid-cols-6">@foreach($analytics['monthly_trend'] as $point)<div class="rounded-xl bg-slate-50 p-3 text-center"><p class="text-lg font-black text-emerald-800">{{ number_format($point['value'], 0) }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $point['label'] }}</p></div>@endforeach</div></div>
        </article>
        <article class="panel">
            <div class="mb-5"><p class="eyebrow">توزيع المستويات</p><h2 class="section-title">درجات الطلاب</h2></div>
            <div class="space-y-4">
                @foreach(['excellent' => ['ممتاز 80+', 'bg-emerald-600'], 'good' => ['جيد 60+', 'bg-sky-500'], 'needs_support' => ['يحتاج دعمًا', 'bg-amber-500'], 'critical' => ['حرج', 'bg-red-500'], 'no_data' => ['دون بيانات', 'bg-slate-400']] as $key => [$label, $color])
                    @php($total = max(1, array_sum($analytics['levels'])))
                    <div><div class="mb-1 flex justify-between text-sm"><span class="font-bold">{{ $label }}</span><span>{{ $analytics['levels'][$key] }}</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full {{ $color }}" style="width: {{ ($analytics['levels'][$key] / $total) * 100 }}%"></div></div></div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="panel">
            <div class="mb-5"><p class="eyebrow">أداء الحلقات</p><h2 class="section-title">متوسط الجودة</h2></div>
            <div class="space-y-3">
                @forelse($analytics['halaqa_performance'] as $halaqa)
                    <div class="rounded-2xl border border-slate-100 p-4"><div class="mb-2 flex justify-between"><div><p class="font-bold">{{ $halaqa['name'] }}</p><p class="text-xs text-slate-400">{{ $halaqa['records'] }} سجل</p></div><span class="font-black text-emerald-800">{{ number_format($halaqa['average'], 1) }}</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-600" style="width: {{ $halaqa['average'] }}%"></div></div></div>
                @empty <p class="py-8 text-center text-sm text-slate-400">لا توجد تقييمات في النطاق المحدد.</p> @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="mb-5 flex items-end justify-between"><div><p class="eyebrow">متابعة ذكية</p><h2 class="section-title">التنبيهات المفتوحة</h2></div>@can('alerts.view')<a href="{{ route('alerts.index') }}" class="text-sm font-bold text-emerald-700">مركز التنبيهات</a>@endcan</div>
            <div class="space-y-3">
                @forelse($analytics['alerts'] as $alert)
                    <article class="rounded-2xl border p-4 {{ $alert->severity->value === 'critical' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }}"><div class="flex justify-between gap-3"><div><p class="font-bold">{{ $alert->student->full_name }}</p><p class="mt-1 text-sm text-slate-600">{{ $alert->reason }}</p></div><span class="text-xs font-black">{{ $alert->severity->label() }}</span></div></article>
                @empty <p class="py-8 text-center text-sm text-slate-400">لا توجد تنبيهات مفتوحة ضمن هذا النطاق.</p> @endforelse
            </div>
        </article>
    </section>

    <section class="panel">
        <div class="mb-5"><p class="eyebrow">الأولوية الأكاديمية</p><h2 class="section-title">طلاب يحتاجون المراجعة</h2></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الحلقة</th><th>الدرجة</th><th>الحفظ</th><th>الحضور</th><th></th></tr></thead><tbody>
            @forelse($analytics['students'] as $student)
                <tr><td class="font-bold">{{ $student->full_name }}</td><td>{{ $student->currentHalaqa?->name ?? '—' }}</td><td>{{ $student->latestProgress ? number_format($student->latestProgress->score, 1) : '—' }}</td><td>{{ $student->latestProgress ? number_format($student->latestProgress->memorized_percentage, 2).'%' : '—' }}</td><td>{{ $student->latestProgress ? number_format($student->latestProgress->attendance_rate, 1).'%' : '—' }}</td><td><a class="font-bold text-emerald-700" href="{{ route('students.show', $student) }}">فتح الملف</a></td></tr>
            @empty <tr><td colspan="6" class="py-8 text-center text-slate-400">لا يوجد طلاب ضمن النطاق.</td></tr> @endforelse
        </tbody></table></div>
    </section>
</div>
