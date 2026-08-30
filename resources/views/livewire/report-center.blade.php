<div class="space-y-6" wire:poll.45s>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="eyebrow">البيانات وصناعة القرار</p><h2 class="page-title">مركز التقارير</h2><p class="page-subtitle">استعراض مباشر، تصدير Excel احترافي، وأرشفة آمنة للتقارير المرفوعة.</p></div>
        <div class="flex flex-wrap rounded-2xl bg-slate-200/60 p-1">
            @foreach(['reports' => 'التقارير', 'exports' => 'ملفات التصدير', 'uploads' => 'التقارير المرفوعة'] as $value => $label)
                <button type="button" wire:click="$set('tab', '{{ $value }}')" class="rounded-xl px-4 py-2.5 text-sm font-black transition {{ $tab === $value ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <x-flash-messages inline consume />

    @if($tab === 'reports')
        <section class="panel">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="md:col-span-2"><label class="form-label">نوع التقرير</label><select class="form-input" wire:model.live="reportType">@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                @if($reportType === 'student_comprehensive')
                    <div><label class="form-label">المركز</label><select class="form-input" wire:model.live="centerId"><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('centerId')" /></div>
                    <div class="md:col-span-2"><span class="form-label">نطاق الكشف</span><div class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5"><label class="cursor-pointer"><input type="radio" wire:model.live="comprehensiveScope" value="center" class="peer sr-only" @disabled(auth()->user()->requiresTeacherAssignmentScope())><span class="flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-black text-slate-500 transition peer-checked:bg-white peer-checked:text-emerald-800 peer-checked:shadow-sm">المركز كاملًا</span></label><label class="cursor-pointer"><input type="radio" wire:model.live="comprehensiveScope" value="teacher" class="peer sr-only"><span class="flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-black text-slate-500 transition peer-checked:bg-white peer-checked:text-emerald-800 peer-checked:shadow-sm">محفّظ محدد</span></label></div></div>
                    @if($comprehensiveScope === 'teacher')
                        <div class="md:col-span-2 xl:col-span-3"><label class="form-label">المحفّظ</label><select class="form-input" wire:model.live="teacherProfileId" @disabled(auth()->user()->requiresTeacherAssignmentScope())><option value="">اختر المحفّظ</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }} — {{ $teacher->employee_number }}</option>@endforeach</select><x-input-error :messages="$errors->get('teacherProfileId')" /></div>
                    @else
                        <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm leading-6 text-emerald-900">سيضم الملف جميع طلاب المركز المسموحين لحسابك، مرتبين بالاسم وبمتسلسل واضح.</div>
                    @endif
                @else
                    <div><label class="form-label">من تاريخ</label><input type="date" class="form-input" wire:model.live="dateFrom"></div>
                    <div><label class="form-label">إلى تاريخ</label><input type="date" class="form-input" wire:model.live="dateTo"><x-input-error :messages="$errors->get('dateTo')" /></div>
                    <div><label class="form-label">الحلقة</label><select class="form-input" wire:model.live="halaqaId"><option value="">كل الحلقات</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></div>
                    <div class="md:col-span-2"><label class="form-label">الطالب</label><select class="form-input" wire:model.live="studentId"><option value="">كل الطلاب المسموحين</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->student_number }} — {{ $student->full_name }}</option>@endforeach</select></div>
                @endif
                <div class="flex items-end md:col-span-2 xl:col-span-5 xl:justify-end">
                    @if($canExportSelectedReport)
                    <button type="button" class="btn-primary w-full xl:w-auto" wire:click="requestExport" wire:loading.attr="disabled" wire:target="requestExport">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>
                        <span wire:loading.remove wire:target="requestExport">تصدير Excel</span><span wire:loading wire:target="requestExport">جارٍ إعداد الملف...</span>
                    </button>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel p-0 overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-black text-emerald-950">{{ $preview['title'] }}</h3><p class="mt-1 text-xs text-slate-400">معاينة أول {{ min(count($preview['rows']), 100) }} من أصل {{ count($preview['rows']) }} سجل</p></div>
                <span class="badge-active">بيانات لحظية</span>
            </div>
            @if(count($preview['rows']))
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr>@foreach($preview['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
                        <tbody>@foreach(array_slice($preview['rows'], 0, 100) as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                    </table>
                </div>
            @else
                <div class="empty-state m-5"><div><span class="mx-auto mb-3 block text-3xl">⌁</span><p class="font-black text-slate-700">لا توجد بيانات مطابقة</p><p class="mt-1 text-sm">غيّر نوع التقرير أو مرشحات الفترة.</p></div></div>
            @endif
        </section>
    @elseif($tab === 'exports')
        <section class="panel p-0 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="section-title">ملفات Excel</h3><p class="mt-1 text-sm text-slate-500">الملفات الصغيرة تُجهّز فورًا، والكبيرة تستمر في الخلفية مع تحديث الحالة تلقائيًا.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($exports as $export)
                    <article class="flex flex-col gap-4 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-100 font-black text-emerald-800">XLS</span>
                        <div class="min-w-0 flex-1"><h3 class="font-black text-slate-900">{{ $types[$export->report_type] ?? $export->report_type }}</h3><p class="mt-1 text-xs text-slate-400">{{ $export->created_at->translatedFormat('j F Y، H:i') }} @if($export->rows_count) · {{ $export->rows_count }} صف @endif</p>@if($export->status === 'failed')<p class="mt-2 text-xs text-rose-600">تعذّر إنشاء الملف. أعد المحاولة من شاشة التقارير.</p>@endif</div>
                        <div class="flex items-center gap-2">
                            @if($export->status === 'ready' && $export->privateFile)<a class="btn-primary" href="{{ route('private-files.show', $export->privateFile) }}">تنزيل آمن</a>
                            @elseif($export->status === 'failed')<span class="inline-flex rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700">فشل</span>
                            @else<span class="badge-warning"><span class="size-2 animate-pulse rounded-full bg-amber-500"></span> قيد الإعداد</span>@endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state m-5"><div><p class="font-black text-slate-700">لا توجد ملفات تصدير بعد</p><button class="mt-3 text-sm font-bold text-emerald-700" wire:click="$set('tab', 'reports')">إنشاء أول تقرير</button></div></div>
                @endforelse
            </div>
        </section>
    @else
        @can('reports.upload')
            <section x-data="{ open: false }" class="panel">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-right"><span><span class="section-title block">رفع تقرير أو مستند</span><span class="mt-1 block text-sm text-slate-500">PDF وExcel وWord والصور حتى 20MB</span></span><span class="grid size-10 place-items-center rounded-xl bg-emerald-100 text-xl text-emerald-800 transition" :class="open ? 'rotate-45' : ''">＋</span></button>
                <form x-cloak x-show="open" x-collapse wire:submit="uploadReport" class="mt-6 border-t border-slate-100 pt-6">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="md:col-span-2"><label class="form-label">عنوان التقرير</label><input class="form-input" wire:model="uploadTitle"><x-input-error :messages="$errors->get('uploadTitle')" /></div>
                        <div><label class="form-label">النوع</label><select class="form-input" wire:model="uploadType"><option value="management">إداري</option><option value="academic">أكاديمي</option><option value="attendance">حضور</option><option value="financial">مالي</option><option value="other">آخر</option></select></div>
                        <div><label class="form-label">بداية الفترة</label><input type="date" class="form-input" wire:model="uploadPeriodStart"></div>
                        <div><label class="form-label">نهاية الفترة</label><input type="date" class="form-input" wire:model="uploadPeriodEnd"><x-input-error :messages="$errors->get('uploadPeriodEnd')" /></div>
                        <div><label class="form-label">الخصوصية</label><select class="form-input" wire:model="uploadPrivacy"><option value="internal">داخلي</option><option value="restricted">مقيّد للإدارة</option></select></div>
                        <div class="md:col-span-2"><label class="form-label">الملف</label><input type="file" class="form-input" wire:model="uploadFile"><x-input-error :messages="$errors->get('uploadFile')" /><p wire:loading wire:target="uploadFile" class="mt-2 text-xs font-bold text-emerald-700">جارٍ رفع الملف...</p></div>
                        <div><label class="form-label">ملاحظات</label><textarea class="form-input" rows="2" wire:model="uploadNotes"></textarea></div>
                    </div>
                    <div class="mt-5 flex justify-end"><button class="btn-primary" type="submit" wire:loading.attr="disabled">حفظ التقرير</button></div>
                </form>
            </section>
        @endcan
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($uploadedReports as $report)
                <article class="panel panel-interactive p-5">
                    <div class="flex items-start justify-between gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-slate-100 text-slate-600"><x-nav-icon name="reports" /></span><span class="{{ $report->privacy === 'restricted' ? 'badge-warning' : 'badge-inactive' }}">{{ $report->privacy === 'restricted' ? 'مقيّد' : 'داخلي' }}</span></div>
                    <h3 class="mt-4 font-black text-slate-900">{{ $report->title }}</h3><p class="mt-1 text-xs text-slate-400">رفعه {{ $report->uploader?->name ?? 'النظام' }} · {{ $report->created_at->translatedFormat('j F Y') }}</p>
                    @if($report->notes)<p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500">{{ $report->notes }}</p>@endif
                    @if($report->privateFile)<a class="btn-secondary mt-4 w-full" href="{{ route('private-files.show', $report->privateFile) }}">تنزيل الملف</a>@endif
                </article>
            @empty
                <div class="empty-state md:col-span-2 xl:col-span-3"><p class="font-black text-slate-700">لا توجد تقارير مرفوعة بعد</p></div>
            @endforelse
        </section>
    @endif
</div>
