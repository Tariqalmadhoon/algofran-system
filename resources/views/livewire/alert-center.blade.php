<div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="eyebrow">Alerts Engine</p><h1 class="page-title">مركز التنبيهات الأكاديمية</h1><p class="page-subtitle">أسباب واضحة، أدلة من السجلات، ومسار متابعة ومعالجة قابل للتدقيق.</p></div>@can('alerts.manage')<button wire:click="refreshInsights" wire:loading.attr="disabled" class="btn-primary flex" type="button">تحديث التحليلات الآن</button>@endcan</header>
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif

    <section class="panel grid gap-3 md:grid-cols-4">
        <label><span class="form-label">بحث عن طالب</span><input wire:model.live.debounce.350ms="search" class="form-input"></label>
        <label><span class="form-label">الحالة</span><select wire:model.live="statusFilter" class="form-input"><option value="active">المفتوحة وقيد المتابعة</option>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
        <label><span class="form-label">الخطورة</span><select wire:model.live="severityFilter" class="form-input"><option value="">الكل</option>@foreach($severities as $severity)<option value="{{ $severity->value }}">{{ $severity->label() }}</option>@endforeach</select></label>
        <label><span class="form-label">الحلقة</span><select wire:model.live="halaqaFilter" class="form-input"><option value="">الكل</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></label>
    </section>

    <section class="space-y-4">
        @forelse($alerts as $alert)
            <article class="panel {{ $alert->severity->value === 'critical' ? 'border-red-200' : ($alert->severity->value === 'warning' ? 'border-amber-200' : 'border-sky-200') }}" wire:key="alert-{{ $alert->id }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-xs font-black {{ $alert->severity->value === 'critical' ? 'bg-red-100 text-red-700' : ($alert->severity->value === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700') }}">{{ $alert->severity->label() }}</span><span class="badge-inactive">{{ $alert->status->label() }}</span><span class="text-xs text-slate-400">تكرار {{ $alert->occurrence_count }}</span></div><h2 class="mt-3 text-lg font-black text-emerald-950"><a href="{{ route('students.show', $alert->student) }}">{{ $alert->student->full_name }}</a></h2><p class="mt-2 text-sm leading-7 text-slate-700">{{ $alert->reason }}</p><p class="mt-2 text-xs text-slate-400">{{ $alert->halaqa?->name ?? 'دون حلقة' }} · {{ $alert->teacher?->user?->name ?? 'دون محفظ' }} · {{ $alert->generated_at->format('Y-m-d H:i') }}</p></div>
                    @can('update', $alert)
                        @if(in_array($alert->status->value, ['open', 'acknowledged']))
                            <div class="w-full shrink-0 space-y-2 lg:w-80">@if($alert->status->value === 'open')<button wire:click="acknowledge({{ $alert->id }})" class="btn-secondary w-full" type="button">بدء المتابعة</button>@endif<label><span class="form-label">إجراء المعالجة</span><textarea wire:model="resolutionNotes.{{ $alert->id }}" rows="2" class="form-input" placeholder="ما الإجراء الذي تم؟"></textarea></label><button wire:click="resolve({{ $alert->id }})" class="btn-primary flex w-full" type="button">إغلاق بعد المعالجة</button></div>
                        @elseif($alert->resolution_notes)
                            <div class="w-full rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 lg:w-80"><p class="font-black">إجراء المعالجة</p><p class="mt-2">{{ $alert->resolution_notes }}</p></div>
                        @endif
                    @endcan
                </div>
            </article>
        @empty <div class="panel py-14 text-center text-slate-400">لا توجد تنبيهات مطابقة.</div> @endforelse
        {{ $alerts->links() }}
    </section>
</div>
