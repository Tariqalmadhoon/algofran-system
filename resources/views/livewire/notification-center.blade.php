<div class="space-y-6" wire:poll.30s>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="eyebrow">تحديثات النظام</p><h2 class="page-title">مركز الإشعارات</h2><p class="page-subtitle">تابع المواعيد ونتائج التصدير والأحداث المهمة من شاشة واحدة.</p></div>
        @if($unreadCount)<button type="button" class="btn-secondary" wire:click="markAllRead">تعليم الكل كمقروء</button>@endif
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <button type="button" wire:click="$set('scope', 'unread')" class="stat-card text-right {{ $scope === 'unread' ? 'border-emerald-300 ring-4 ring-emerald-50' : '' }}"><span class="grid size-12 place-items-center rounded-2xl bg-emerald-100 text-xl font-black text-emerald-800">{{ $unreadCount }}</span><span><span class="block text-sm font-black text-slate-900">غير مقروءة</span><span class="text-xs text-slate-400">تحتاج انتباهك</span></span></button>
        <button type="button" wire:click="$set('scope', 'all')" class="stat-card text-right {{ $scope === 'all' ? 'border-emerald-300 ring-4 ring-emerald-50' : '' }}"><span class="grid size-12 place-items-center rounded-2xl bg-slate-100 text-xl font-black text-slate-700">{{ $totalCount }}</span><span><span class="block text-sm font-black text-slate-900">جميع الإشعارات</span><span class="text-xs text-slate-400">السجل الكامل</span></span></button>
    </div>
    <section class="panel p-0 overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                @php $kind = data_get($notification->data, 'kind', 'info'); @endphp
                <article class="flex gap-4 p-4 transition hover:bg-emerald-50/35 sm:p-5 {{ $notification->read_at ? 'opacity-65' : '' }}">
                    <span class="mt-1 grid size-10 shrink-0 place-items-center rounded-2xl {{ $kind === 'error' ? 'bg-rose-100 text-rose-700' : ($kind === 'calendar' ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700') }}"><x-nav-icon :name="$kind === 'calendar' ? 'calendar' : 'bell'" /></span>
                    <div class="min-w-0 flex-1"><div class="flex items-center gap-2"><h3 class="font-black text-slate-900">{{ data_get($notification->data, 'title', 'إشعار جديد') }}</h3>@unless($notification->read_at)<span class="size-2 rounded-full bg-emerald-500"></span>@endunless</div><p class="mt-1 text-sm leading-6 text-slate-500">{{ data_get($notification->data, 'message') }}</p><p class="mt-2 text-[11px] font-bold text-slate-400">{{ $notification->created_at->diffForHumans() }}</p></div>
                    <div class="flex shrink-0 items-center gap-1">@unless($notification->read_at)<button type="button" class="btn-ghost text-xs font-bold" wire:click="markRead('{{ $notification->id }}')">فتح</button>@endunless<button type="button" class="btn-ghost text-rose-500" wire:click="delete('{{ $notification->id }}')" wire:confirm="حذف هذا الإشعار؟">×</button></div>
                </article>
            @empty
                <div class="empty-state m-5"><div><span class="mx-auto mb-3 grid size-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><x-nav-icon name="bell" class="size-6" /></span><p class="font-black text-slate-700">لا توجد إشعارات في هذا القسم</p><p class="mt-1 text-sm">ستظهر هنا التحديثات فور وصولها.</p></div></div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="border-t border-slate-100 p-4">{{ $notifications->links() }}</div>@endif
    </section>
</div>
