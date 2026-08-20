<div class="space-y-6" wire:poll.60s>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="eyebrow">المواعيد والبرامج</p>
            <h2 class="page-title">التقويم الموحّد</h2>
            <p class="page-subtitle">مواعيد الحلقات والدورات والاختبارات والفعاليات واجتماعات المركز في مكان واحد.</p>
        </div>
        @can('calendar.manage')
            <button type="button" class="btn-primary" wire:click="$set('showEventForm', true)">
                <span class="text-lg leading-none">＋</span> إضافة موعد
            </button>
        @endcan
    </div>

    <section class="panel p-4 sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary px-3" wire:click="move(1)" aria-label="التالي">‹</button>
                <button type="button" class="btn-secondary" wire:click="today">اليوم</button>
                <button type="button" class="btn-secondary px-3" wire:click="move(-1)" aria-label="السابق">›</button>
                <p class="mr-2 text-base font-black text-emerald-950">
                    {{ $viewMode === 'day' ? $from->translatedFormat('l، j F Y') : ($viewMode === 'week' ? $from->translatedFormat('j F').' — '.$to->translatedFormat('j F Y') : \Carbon\Carbon::parse($anchorDate)->translatedFormat('F Y')) }}
                </p>
            </div>
            <div class="xl:mr-auto flex flex-wrap gap-2">
                <select class="form-input w-auto min-w-36" wire:model.live="typeFilter" aria-label="نوع الموعد">
                    <option value="">كل الأنواع</option>
                    @foreach($eventTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <select class="form-input w-auto min-w-36" wire:model.live="halaqaFilter" aria-label="الحلقة">
                    <option value="">كل الحلقات</option>
                    @foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach
                </select>
                <div class="flex rounded-xl bg-slate-100 p-1">
                    @foreach(['day' => 'يوم', 'week' => 'أسبوع', 'month' => 'شهر'] as $mode => $label)
                        <button type="button" wire:click="setView('{{ $mode }}')" class="rounded-lg px-3 py-2 text-xs font-black {{ $viewMode === $mode ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @php
        $styles = [
            'halaqa' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'course' => 'border-blue-200 bg-blue-50 text-blue-800',
            'exam' => 'border-rose-200 bg-rose-50 text-rose-800', 'meeting' => 'border-violet-200 bg-violet-50 text-violet-800',
            'activity' => 'border-amber-200 bg-amber-50 text-amber-800', 'revision' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
            'event' => 'border-slate-200 bg-slate-50 text-slate-700',
        ];
    @endphp

    @if($viewMode === 'month')
        <section class="panel overflow-hidden p-0">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                @foreach(['السبت','الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة'] as $weekday)
                    <div class="px-2 py-3 text-center text-[11px] font-black text-slate-500 sm:text-xs">{{ $weekday }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach($days as $day)
                    @php $dayEvents = $events->filter(fn ($event) => $event['start']->isSameDay($day)); @endphp
                    <div wire:key="calendar-day-{{ $day->toDateString() }}" class="min-h-28 border-b border-l border-slate-100 p-1.5 sm:min-h-36 sm:p-2 {{ $day->month !== \Carbon\Carbon::parse($anchorDate)->month ? 'bg-slate-50/70' : 'bg-white' }}">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="grid size-7 place-items-center rounded-full text-xs font-black {{ $day->isToday() ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : ($day->month !== \Carbon\Carbon::parse($anchorDate)->month ? 'text-slate-300' : 'text-slate-600') }}">{{ $day->day }}</span>
                            @if($dayEvents->count() > 3)<span class="text-[9px] font-bold text-slate-400">+{{ $dayEvents->count() - 3 }}</span>@endif
                        </div>
                        <div class="space-y-1">
                            @foreach($dayEvents->take(3) as $event)
                                <div title="{{ $event['description'] }}" class="truncate rounded-lg border px-1.5 py-1 text-[9px] font-bold sm:text-[11px] {{ $styles[$event['type']] ?? $styles['event'] }}">
                                    @unless($event['all_day'])<span class="opacity-65">{{ $event['start']->format('H:i') }}</span>@endunless {{ $event['title'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <div class="grid gap-4 {{ $viewMode === 'week' ? 'md:grid-cols-2 xl:grid-cols-4' : '' }}">
            @foreach($days as $day)
                @php $dayEvents = $events->filter(fn ($event) => $event['start']->isSameDay($day)); @endphp
                <section class="panel p-4 {{ $day->isToday() ? 'border-emerald-300 ring-4 ring-emerald-50' : '' }}">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                        <div><p class="text-xs font-bold text-emerald-700">{{ $day->translatedFormat('l') }}</p><p class="text-lg font-black text-slate-900">{{ $day->translatedFormat('j F') }}</p></div>
                        <span class="text-xs font-bold text-slate-400">{{ $dayEvents->count() }} موعد</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($dayEvents as $event)
                            <article class="rounded-2xl border p-3 {{ $styles[$event['type']] ?? $styles['event'] }}">
                                <div class="flex items-start justify-between gap-2"><h3 class="text-sm font-black">{{ $event['title'] }}</h3><span class="shrink-0 text-[10px] font-bold opacity-70">{{ $event['all_day'] ? 'طوال اليوم' : $event['start']->format('H:i') }}</span></div>
                                @if($event['description'])<p class="mt-1 line-clamp-2 text-xs leading-5 opacity-75">{{ $event['description'] }}</p>@endif
                                @if($event['location'])<p class="mt-2 text-[10px] font-bold opacity-70">المكان: {{ $event['location'] }}</p>@endif
                            </article>
                        @empty
                            <p class="py-8 text-center text-xs text-slate-400">لا توجد مواعيد</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    @if($showEventForm)
        <div class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/45 p-0 backdrop-blur-sm sm:items-center sm:p-5" wire:click.self="$set('showEventForm', false)">
            <form wire:submit="saveEvent" class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:max-w-2xl sm:rounded-3xl">
                <div class="mb-6 flex items-center justify-between"><div><p class="eyebrow">موعد جديد</p><h3 class="section-title">إضافة إلى التقويم</h3></div><button type="button" class="btn-ghost text-xl" wire:click="$set('showEventForm', false)">×</button></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="form-label">العنوان</label><input class="form-input" wire:model="eventTitle"><x-input-error :messages="$errors->get('eventTitle')" /></div>
                    <div><label class="form-label">النوع</label><select class="form-input" wire:model="eventType">@foreach($eventTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="form-label">الخصوصية</label><select class="form-input" wire:model="eventPrivacy"><option value="internal">داخلي</option><option value="restricted">مقيّد</option></select></div>
                    <div><label class="form-label">يبدأ في</label><input type="datetime-local" class="form-input" wire:model="eventStartsAt"><x-input-error :messages="$errors->get('eventStartsAt')" /></div>
                    <div><label class="form-label">ينتهي في</label><input type="datetime-local" class="form-input" wire:model="eventEndsAt"><x-input-error :messages="$errors->get('eventEndsAt')" /></div>
                    <div><label class="form-label">المركز</label><select class="form-input" wire:model="eventCenterId"><option value="">عام</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select></div>
                    <div><label class="form-label">الحلقة</label><select class="form-input" wire:model="eventHalaqaId"><option value="">بدون حلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="form-label">المكان</label><input class="form-input" wire:model="eventLocation"></div>
                    <div class="sm:col-span-2"><label class="form-label">الوصف</label><textarea rows="3" class="form-input" wire:model="eventDescription"></textarea></div>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-700" wire:model="eventAllDay"> موعد طوال اليوم</label>
                </div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" class="btn-secondary" wire:click="$set('showEventForm', false)">إلغاء</button><button class="btn-primary" type="submit" wire:loading.attr="disabled">حفظ الموعد</button></div>
            </form>
        </div>
    @endif
</div>
