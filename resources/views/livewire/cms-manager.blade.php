<div class="space-y-6">
    @php
        $statusLabels = ['published' => 'منشور', 'draft' => 'مسودة', 'archived' => 'مؤرشف'];
        $statusStyles = ['published' => 'bg-emerald-100 text-emerald-800', 'draft' => 'bg-amber-100 text-amber-800', 'archived' => 'bg-slate-200 text-slate-700'];
    @endphp

    <section class="relative overflow-hidden rounded-[2rem] bg-[linear-gradient(125deg,#064e3b_0%,#0f766e_58%,#b28a3d_145%)] p-6 text-white shadow-[0_28px_80px_-45px_rgba(6,78,59,.8)] sm:p-8" data-motion="reveal">
        <div class="absolute -left-20 -top-24 size-64 rounded-full border border-white/10"></div>
        <div class="absolute -bottom-28 right-1/3 size-56 rounded-full bg-amber-300/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-black text-emerald-50 backdrop-blur"><span class="size-2 rounded-full bg-amber-300 shadow-[0_0_0_5px_rgba(252,211,77,.12)]"></span>إدارة الحضور الرقمي</span>
                <h2 class="mt-4 text-3xl font-black sm:text-4xl">مركز إدارة الموقع</h2>
                <p class="mt-3 max-w-xl text-sm leading-7 text-emerald-50/75 sm:text-base">أنشئ الأخبار والأنشطة، اضبط ظهورها، وأدر الصور ورسائل الزوار من مساحة عمل واحدة واضحة.</p>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $statusCounts->sum() }}</strong><span class="text-[11px] text-emerald-100/75">كل المحتوى</span></div>
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $mediaCount }}</strong><span class="text-[11px] text-emerald-100/75">وسيط</span></div>
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $newMessages }}</strong><span class="text-[11px] text-emerald-100/75">بحاجة لمتابعة</span></div>
            </div>
        </div>
    </section>

    <nav class="panel flex flex-col gap-2 p-2 sm:flex-row" aria-label="أقسام إدارة الموقع" data-motion="reveal">
        @foreach(['content' => ['المحتوى', 'الأخبار والأنشطة والصفحات'], 'media' => ['مكتبة الوسائط', 'الصور والملفات والمعرض'], 'messages' => ['رسائل التواصل', 'متابعة استفسارات الزوار']] as $value => [$label, $hint])
            <button type="button" wire:click="$set('tab', '{{ $value }}')" class="relative flex flex-1 items-center gap-3 rounded-2xl px-4 py-3 text-right transition duration-300 {{ $tab === $value ? 'bg-emerald-950 text-white shadow-lg shadow-emerald-950/15' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-900' }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $tab === $value ? 'bg-white/12 text-amber-300' : 'bg-slate-100 text-slate-500' }}">
                    @if($value === 'content')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg>
                    @elseif($value === 'media')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m4 17 5-4 3 2 3-3 5 5"/></svg>
                    @else<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>@endif
                </span>
                <span class="min-w-0"><strong class="block text-sm font-black">{{ $label }}</strong><small class="hidden truncate text-[11px] opacity-65 lg:block">{{ $hint }}</small></span>
                @if($value === 'messages' && $newMessages)<span class="mr-auto grid min-w-6 place-items-center rounded-full bg-rose-500 px-1.5 text-[10px] font-black leading-6 text-white">{{ $newMessages }}</span>@endif
            </button>
        @endforeach
    </nav>

    <x-flash-messages inline consume />

    @if($tab === 'content')
        <section class="grid gap-4 sm:grid-cols-3" data-reveal-group="up" data-reveal-stagger="55">
            @foreach([['draft', 'المسودات', 'قبل النشر', 'bg-amber-100 text-amber-700'], ['published', 'المحتوى المنشور', 'ظاهر للزوار', 'bg-emerald-100 text-emerald-700'], ['archived', 'الأرشيف', 'مخفي عن الموقع', 'bg-slate-100 text-slate-600']] as [$status, $label, $hint, $style])
                <button type="button" wire:click="$set('statusFilter', '{{ $statusFilter === $status ? '' : $status }}')" class="stat-card text-right {{ $statusFilter === $status ? 'border-emerald-300 ring-4 ring-emerald-50' : '' }}"><span class="grid size-12 place-items-center rounded-2xl {{ $style }} text-xl font-black">{{ $statusCounts[$status] ?? 0 }}</span><span><strong class="block font-black text-slate-800">{{ $label }}</strong><small class="text-xs text-slate-400">{{ $hint }}</small></span></button>
            @endforeach
        </section>

        <section class="panel p-4 sm:p-5" data-motion="reveal">
            <div class="grid gap-3 lg:grid-cols-[minmax(240px,1.5fr)_minmax(160px,.65fr)_minmax(160px,.65fr)_auto] lg:items-end">
                <label><span class="form-label">البحث في المحتوى</span><span class="relative block"><svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="form-input pr-10" type="search" wire:model.live.debounce.350ms="contentSearch" placeholder="عنوان، ملخص أو رابط..."></span></label>
                <label><span class="form-label">النوع</span><select class="form-input" wire:model.live="typeFilter"><option value="">كل الأنواع</option>@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }} ({{ $typeCounts[$value] ?? 0 }})</option>@endforeach</select></label>
                <label><span class="form-label">الحالة</span><select class="form-input" wire:model.live="statusFilter"><option value="">كل الحالات</option><option value="draft">مسودة</option><option value="published">منشور</option><option value="archived">مؤرشف</option></select></label>
                <button type="button" class="btn-primary h-[46px]" wire:click="newContent"><span class="text-lg">＋</span> محتوى جديد</button>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2" data-reveal-group="up" data-reveal-stagger="45">
            @forelse($contents as $content)
                @php
                    $previewUrl = match ($content->type) {
                        'news' => route('news.show', $content),
                        'activity' => route('activities.show', $content),
                        'page' => route('public.page', $content),
                        default => null,
                    };
                @endphp
                <article wire:key="content-{{ $content->id }}" class="group overflow-hidden rounded-3xl border border-slate-200/75 bg-white shadow-[0_14px_45px_-32px_rgba(15,23,42,.45)] transition duration-300 hover:border-emerald-200 hover:shadow-[0_22px_55px_-32px_rgba(6,78,59,.4)]">
                    <div class="flex min-h-44 flex-col sm:flex-row">
                        <div class="relative h-40 shrink-0 overflow-hidden bg-gradient-to-br from-emerald-100 to-teal-50 sm:h-auto sm:w-44">@if($content->featuredMedia)<img class="size-full object-cover transition duration-700 group-hover:scale-105" src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt_text ?: $content->title }}">@else<div class="absolute inset-0 grid place-items-center"><x-brand-logo size="lg" class="opacity-50" /></div>@endif @if($content->featured)<span class="absolute right-3 top-3 rounded-full bg-amber-300 px-2.5 py-1 text-[10px] font-black text-amber-950 shadow">مميز</span>@endif</div>
                        <div class="flex min-w-0 flex-1 flex-col p-5">
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $statusStyles[$content->status] }}">{{ $statusLabels[$content->status] }}</span><span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-black text-teal-700">{{ $types[$content->type] ?? $content->type }}</span><span class="mr-auto text-[10px] text-slate-400">{{ $content->published_at?->translatedFormat('j F Y، H:i') ?: 'لم يحدد موعد النشر' }}</span></div>
                            <h3 class="mt-3 line-clamp-2 text-lg font-black leading-7 text-slate-900">{{ $content->title }}</h3><p dir="ltr" class="mt-1 truncate text-left text-[11px] text-slate-400">/{{ $content->slug }}</p>
                            <div class="mt-auto flex flex-wrap gap-2 pt-4"><button class="btn-secondary px-3 py-2 text-xs" wire:click="editContent({{ $content->id }})">تحرير</button>@if($content->status !== 'published')<button class="btn-ghost px-3 py-2 text-xs font-black text-emerald-700" wire:click="changeContentStatus({{ $content->id }}, 'published')">نشر الآن</button>@else @if($previewUrl)<a class="btn-ghost px-3 py-2 text-xs font-black text-teal-700" href="{{ $previewUrl }}" target="_blank" rel="noopener">معاينة ↗</a>@endif <button class="btn-ghost px-3 py-2 text-xs font-black text-slate-600" wire:click="changeContentStatus({{ $content->id }}, 'archived')">أرشفة</button>@endif<button class="btn-ghost mr-auto px-3 py-2 text-xs font-black text-rose-600" @click="$dispatch('app:confirm', { title: 'حذف المحتوى', message: 'سينقل المحتوى إلى المحذوفات ويختفي من الموقع العام.', confirmLabel: 'نقل إلى المحذوفات', tone: 'danger', action: () => $wire.deleteContent({{ $content->id }}) })">حذف</button></div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state xl:col-span-2"><div><span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-100 text-2xl text-emerald-700">◇</span><p class="mt-4 font-black text-slate-700">لا يوجد محتوى مطابق</p><p class="mt-1 text-sm">غيّر عوامل البحث أو أنشئ أول محتوى للموقع.</p></div></div>
            @endforelse
        </section>
    @elseif($tab === 'media')
        <section x-data="{ open: true }" class="panel overflow-hidden" data-motion="reveal">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 text-right"><span class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg></span><span><strong class="section-title block">رفع وسيط جديد</strong><small class="mt-1 block text-xs text-slate-500">صور JPG وPNG وWebP، مستندات PDF أو فيديو MP4 حتى 50MB</small></span></span><svg class="size-5 text-slate-400 transition duration-300" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>
            <form x-show="open" x-collapse wire:submit="uploadMedia" class="mt-6 border-t border-slate-100 pt-6">
                <div class="grid gap-5 xl:grid-cols-[240px_1fr]">
                    <label class="group relative grid min-h-48 cursor-pointer place-items-center overflow-hidden rounded-3xl border-2 border-dashed border-emerald-200 bg-emerald-50/55 p-4 text-center transition hover:border-emerald-400 hover:bg-emerald-50">
                        @if($mediaFile && str_starts_with((string) $mediaFile->getMimeType(), 'image/'))<img src="{{ $mediaFile->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" alt="معاينة الصورة المختارة"><span class="absolute inset-x-3 bottom-3 rounded-xl bg-slate-950/70 px-3 py-2 text-xs font-black text-white backdrop-blur">اضغط لاختيار ملف آخر</span>@else<span><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-white text-emerald-700 shadow-sm"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg></span><strong class="mt-3 block text-sm text-emerald-950">اختر ملفًا من جهازك</strong><small class="mt-1 block text-slate-500">سيظهر هنا قبل الرفع</small></span>@endif
                        <input type="file" class="sr-only" wire:model="mediaFile" accept="image/jpeg,image/png,image/webp,application/pdf,video/mp4"><span wire:loading.flex wire:target="mediaFile" class="absolute inset-0 items-center justify-center bg-white/85 text-xs font-black text-emerald-700 backdrop-blur">جاري تجهيز المعاينة...</span>
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label><span class="form-label">العنوان الإداري</span><input class="form-input" wire:model="mediaTitle" placeholder="مثال: نشاط الحلقة الأسبوعي"></label><label><span class="form-label">النص البديل للصورة</span><input class="form-input" wire:model="mediaAlt" placeholder="صف ما يظهر في الصورة لسهولة الوصول"></label><label class="md:col-span-2"><span class="form-label">وصف أو تعليق الصورة</span><textarea rows="2" class="form-input" wire:model="mediaCaption"></textarea></label>
                        <div class="flex flex-wrap items-end gap-4 md:col-span-2"><label class="flex h-[46px] items-center gap-2 rounded-xl bg-slate-50 px-4 text-sm font-bold text-slate-700"><input type="checkbox" wire:model="mediaGallery" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"> إظهار في معرض الموقع</label><label><span class="form-label">الترتيب</span><input type="number" min="0" class="form-input w-28" wire:model="mediaSortOrder"></label><button type="submit" class="btn-primary h-[46px] md:mr-auto" wire:loading.attr="disabled">رفع إلى المكتبة</button></div>
                    </div>
                </div><x-input-error :messages="$errors->get('mediaFile')" class="mt-3" />
            </form>
        </section>

        <section class="panel p-4 sm:p-5" data-motion="reveal">
            <div class="grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-end"><label><span class="form-label">البحث في المكتبة</span><input type="search" class="form-input" wire:model.live.debounce.350ms="mediaSearch" placeholder="اسم الملف، العنوان أو النص البديل..."></label><label><span class="form-label">نوع الوسيط</span><select class="form-input" wire:model.live="mediaKindFilter"><option value="">كل الأنواع</option><option value="image">صور</option><option value="video">فيديو</option><option value="document">مستندات</option></select></label><div class="flex h-[46px] items-center gap-2 rounded-xl bg-emerald-50 px-4 text-xs font-black text-emerald-800"><span>{{ $mediaCount }} وسيط</span><span class="text-emerald-300">•</span><span>{{ $galleryCount }} في المعرض</span></div></div><x-input-error :messages="$errors->get('mediaLibrary')" class="mt-3" />
        </section>

        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4" data-reveal-group="up" data-reveal-stagger="45">
            @forelse($media as $item)
                <article wire:key="media-{{ $item->id }}" class="group overflow-hidden rounded-3xl border border-slate-200/75 bg-white shadow-[0_16px_48px_-34px_rgba(15,23,42,.45)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200">
                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">@if($item->kind === 'image')<img src="{{ $item->url }}" alt="{{ $item->alt_text ?: $item->title }}" class="size-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">@else<div class="absolute inset-0 grid place-items-center bg-gradient-to-br from-slate-100 to-slate-200 text-center"><span><strong class="block text-4xl text-slate-500">{{ $item->kind === 'video' ? '▶' : '▤' }}</strong><small class="mt-2 block font-black text-slate-500">{{ strtoupper(pathinfo($item->original_name, PATHINFO_EXTENSION)) }}</small></span></div>@endif<span class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-black shadow-sm backdrop-blur {{ $item->is_gallery ? 'bg-emerald-600/90 text-white' : 'bg-white/85 text-slate-600' }}">{{ $item->is_gallery ? 'ظاهر في المعرض' : 'داخل المكتبة' }}</span></div>
                    <div class="p-4"><h3 class="truncate font-black text-slate-900" title="{{ $item->title ?: $item->original_name }}">{{ $item->title ?: $item->original_name }}</h3><p class="mt-1 truncate text-xs text-slate-400">{{ $item->alt_text ?: 'لم يضف نص بديل بعد' }}</p><div class="mt-3 flex items-center justify-between text-[10px] text-slate-400"><span>{{ number_format($item->size / 1024, 1) }} KB</span><span>{{ $item->featured_contents_count ? 'مستخدمة في '.$item->featured_contents_count.' محتوى' : 'غير مرتبطة بمحتوى' }}</span></div>
                        <div class="mt-4 grid grid-cols-2 gap-2"><button type="button" class="btn-secondary px-3 py-2 text-xs" wire:click="editMedia({{ $item->id }})">تعديل البيانات</button>@if($item->kind === 'image')<button type="button" class="btn-ghost px-3 py-2 text-xs font-black text-emerald-700" wire:click="toggleMediaGallery({{ $item->id }})">{{ $item->is_gallery ? 'إخفاء من المعرض' : 'إظهار بالمعرض' }}</button>@else<a href="{{ $item->url }}" target="_blank" class="btn-ghost px-3 py-2 text-xs font-black text-teal-700">فتح الملف</a>@endif<button type="button" class="btn-ghost col-span-2 px-3 py-2 text-xs font-black text-rose-600 disabled:cursor-not-allowed disabled:opacity-45" @disabled($item->featured_contents_count) @if(!$item->featured_contents_count) @click="$dispatch('app:confirm', { title: 'حذف الوسيط', message: 'سيحذف الملف نهائيًا من التخزين ولا يمكن التراجع عن هذه العملية.', confirmLabel: 'حذف الوسيط', tone: 'danger', action: () => $wire.deleteMedia({{ $item->id }}) })" @endif>{{ $item->featured_contents_count ? 'محمي لأنه مستخدم في المحتوى' : 'حذف من المكتبة' }}</button></div>
                    </div>
                </article>
            @empty<div class="empty-state sm:col-span-2 lg:col-span-3 2xl:col-span-4"><div><p class="font-black text-slate-700">لا توجد وسائط مطابقة</p><p class="mt-1 text-sm">ارفع أول صورة أو غيّر عوامل البحث.</p></div></div>@endforelse
        </section>
    @else
        <section class="panel p-4 sm:p-5" data-motion="reveal"><div class="grid gap-3 md:grid-cols-[1fr_220px] md:items-end"><label><span class="form-label">البحث في الرسائل</span><input type="search" class="form-input" wire:model.live.debounce.350ms="messageSearch" placeholder="الاسم، الموضوع، الهاتف أو البريد..."></label><label><span class="form-label">حالة المتابعة</span><select class="form-input" wire:model.live="messageStatusFilter"><option value="">كل الرسائل</option><option value="new">بحاجة لمتابعة</option><option value="handled">تمت معالجتها</option></select></label></div></section>
        <section class="space-y-4" data-reveal-group="up" data-reveal-stagger="45">
            @forelse($messages as $message)
                <article wire:key="message-{{ $message->id }}" class="panel {{ $message->status === 'new' ? 'border-emerald-200 bg-gradient-to-l from-emerald-50/60 to-white' : '' }}"><div class="flex flex-col gap-4 sm:flex-row sm:items-start"><span class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $message->status === 'new' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-lg font-black">{{ mb_substr($message->name, 0, 1) }}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-slate-900">{{ $message->subject }}</h3><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $message->status === 'new' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $message->status === 'new' ? 'تحتاج متابعة' : 'تمت المعالجة' }}</span><time class="mr-auto text-[11px] text-slate-400">{{ $message->created_at->translatedFormat('j F Y، H:i') }}</time></div><p class="mt-1 text-xs font-bold text-slate-500">{{ $message->name }} · <a dir="ltr" href="tel:{{ $message->phone }}" class="text-emerald-700">{{ $message->phone }}</a> @if($message->email)· <a href="mailto:{{ $message->email }}" class="text-emerald-700">{{ $message->email }}</a>@endif</p><p class="mt-4 whitespace-pre-line rounded-2xl bg-white/75 p-4 text-sm leading-7 text-slate-600">{{ $message->message }}</p></div>@if($message->status === 'new')<button type="button" class="btn-primary shrink-0" wire:click="handleMessage({{ $message->id }})">تمت المعالجة</button>@else<button type="button" class="btn-secondary shrink-0" wire:click="reopenMessage({{ $message->id }})">إعادة للمتابعة</button>@endif</div></article>
            @empty<div class="empty-state"><div><p class="font-black text-slate-700">لا توجد رسائل مطابقة</p><p class="mt-1 text-sm">صندوق التواصل منظم ولا توجد عناصر ضمن هذا الفلتر.</p></div></div>@endforelse
        </section>
    @endif

    @if($showContentForm)
        <div class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/55 p-0 backdrop-blur-sm sm:items-center sm:p-5" wire:click.self="$set('showContentForm', false)">
            <form wire:submit="saveContent" class="max-h-[95vh] w-full overflow-y-auto rounded-t-[2rem] bg-slate-50 shadow-2xl sm:max-w-5xl sm:rounded-[2rem]">
                <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200/80 bg-white/95 px-5 py-4 backdrop-blur sm:px-7"><div><p class="eyebrow">{{ $editingContentId ? 'تحرير المحتوى' : 'محتوى جديد' }}</p><h3 class="section-title">بيانات النشر والصورة ومحركات البحث</h3></div><button type="button" class="grid size-10 place-items-center rounded-xl bg-slate-100 text-xl text-slate-500 transition hover:bg-rose-50 hover:text-rose-600" wire:click="$set('showContentForm', false)" aria-label="إغلاق">×</button></header>
                <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[1.4fr_.8fr]">
                    <div class="space-y-5">
                        <section class="panel space-y-4 p-5"><div class="grid gap-4 sm:grid-cols-2"><label><span class="form-label">نوع المحتوى</span><select class="form-input" wire:model="contentType">@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><label><span class="form-label">حالة النشر</span><select class="form-input" wire:model="contentStatus"><option value="draft">مسودة</option><option value="published">منشور</option><option value="archived">مؤرشف</option></select></label><label class="sm:col-span-2"><span class="form-label">العنوان</span><input class="form-input" wire:model="contentTitle" placeholder="عنوان واضح ومختصر"><x-input-error :messages="$errors->get('contentTitle')" /></label><label class="sm:col-span-2"><span class="form-label">الرابط المختصر بالإنجليزية</span><span class="flex gap-2"><input dir="ltr" class="form-input text-left" placeholder="news-title" wire:model="contentSlug"><button type="button" class="btn-secondary shrink-0 px-3" wire:click="generateSlug">توليد</button></span><x-input-error :messages="$errors->get('contentSlug')" /></label><label class="sm:col-span-2"><span class="form-label">الملخص</span><textarea rows="3" class="form-input leading-7" wire:model="contentExcerpt" placeholder="ملخص يظهر في بطاقات الموقع ونتائج البحث"></textarea><x-input-error :messages="$errors->get('contentExcerpt')" /></label><label class="sm:col-span-2"><span class="form-label">تفاصيل المحتوى</span><textarea rows="11" class="form-input leading-8" wire:model="contentBody" placeholder="اكتب الخبر أو النشاط بصورة منظمة..."></textarea></label></div></section>
                        <section class="panel p-5"><h4 class="font-black text-slate-900">تهيئة محركات البحث</h4><p class="mt-1 text-xs text-slate-500">اترك الحقول فارغة لاستخدام عنوان المحتوى وملخصه تلقائيًا.</p><div class="mt-4 grid gap-4 sm:grid-cols-2"><label><span class="form-label">عنوان SEO</span><input class="form-input" wire:model="contentMetaTitle"></label><label><span class="form-label">وصف SEO</span><textarea rows="2" class="form-input" wire:model="contentMetaDescription"></textarea></label></div></section>
                    </div>
                    <aside class="space-y-5">
                        <section class="panel p-5">
                            <h4 class="font-black text-slate-900">الصورة البارزة</h4>
                            <p class="mt-1 text-xs leading-5 text-slate-500">ارفع صورة جديدة وعاينها مباشرة، أو اختر صورة محفوظة في المكتبة.</p>

                            <div class="mt-4">
                                @if($contentImageFile)
                                    <div class="group relative aspect-[16/10] overflow-hidden rounded-2xl border-2 border-emerald-400 bg-emerald-50 shadow-sm">
                                        <img src="{{ $contentImageFile->temporaryUrl() }}" class="size-full object-cover" alt="معاينة الصورة قبل النشر">
                                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-slate-950/90 to-transparent px-4 pb-3 pt-10 text-white">
                                            <span class="text-xs font-black">معاينة الصورة قبل النشر</span>
                                            <button type="button" wire:click="removeContentImage" class="rounded-lg bg-white/15 px-2.5 py-1.5 text-[10px] font-black backdrop-blur transition hover:bg-rose-500">إزالة</button>
                                        </div>
                                        <span wire:loading.flex wire:target="contentImageFile" class="absolute inset-0 items-center justify-center bg-white/85 text-xs font-black text-emerald-700 backdrop-blur">جاري تجهيز المعاينة...</span>
                                    </div>
                                    <label class="btn-secondary mt-3 w-full cursor-pointer text-xs"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>استبدال الصورة<input type="file" class="sr-only" wire:model="contentImageFile" accept="image/jpeg,image/png,image/webp"></label>
                                    <label class="mt-3 block"><span class="form-label">النص البديل للصورة</span><input class="form-input" wire:model="contentImageAlt" placeholder="صف ما يظهر في الصورة"></label>
                                @else
                                    <label class="group relative grid min-h-44 cursor-pointer place-items-center overflow-hidden rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/55 p-4 text-center transition hover:border-emerald-400 hover:bg-emerald-50">
                                        <span><span class="mx-auto grid size-11 place-items-center rounded-2xl bg-white text-emerald-700 shadow-sm"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg></span><strong class="mt-3 block text-sm text-emerald-950">رفع صورة ومعاينتها</strong><small class="mt-1 block text-[10px] leading-5 text-slate-500">JPG أو PNG أو WebP · بحد أقصى 10MB</small></span>
                                        <input type="file" class="sr-only" wire:model="contentImageFile" accept="image/jpeg,image/png,image/webp">
                                        <span wire:loading.flex wire:target="contentImageFile" class="absolute inset-0 items-center justify-center bg-white/85 text-xs font-black text-emerald-700 backdrop-blur">جاري رفع الصورة للمعاينة...</span>
                                    </label>
                                @endif
                                <x-input-error :messages="$errors->get('contentImageFile')" class="mt-2" />
                            </div>

                            <div class="my-4 flex items-center gap-3"><span class="h-px flex-1 bg-slate-200"></span><span class="text-[10px] font-black text-slate-400">أو اختر من المكتبة</span><span class="h-px flex-1 bg-slate-200"></span></div>
                            <div class="max-h-72 space-y-2 overflow-y-auto pl-1">
                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border p-3 transition {{ $contentFeaturedMediaId === '' && ! $contentImageFile ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 hover:border-emerald-200' }}"><input type="radio" value="" wire:model.live="contentFeaturedMediaId" class="text-emerald-700 focus:ring-emerald-500"><span class="grid size-12 place-items-center rounded-xl bg-slate-100 text-xs font-black text-slate-500">بدون</span><span class="text-sm font-bold text-slate-700">دون صورة بارزة</span></label>
                                @foreach($mediaChoices as $choice)
                                    <label wire:key="media-choice-{{ $choice->id }}" class="flex cursor-pointer items-center gap-3 rounded-2xl border p-2.5 transition {{ $contentFeaturedMediaId === (string) $choice->id ? 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-100' : 'border-slate-200 hover:border-emerald-200' }}"><input type="radio" value="{{ $choice->id }}" wire:model.live="contentFeaturedMediaId" class="text-emerald-700 focus:ring-emerald-500"><img src="{{ $choice->url }}" class="size-14 rounded-xl object-cover" alt=""><span class="min-w-0"><strong class="block truncate text-xs text-slate-800">{{ $choice->title ?: $choice->original_name }}</strong><small class="mt-1 block truncate text-[10px] text-slate-400">{{ $choice->alt_text ?: 'بلا نص بديل' }}</small></span></label>
                                @endforeach
                            </div>
                        </section>
                        <section class="panel space-y-4 p-5"><label><span class="form-label">موعد النشر</span><input type="datetime-local" class="form-input" wire:model="contentPublishedAt"><small class="mt-1 block text-[10px] text-slate-400">عند النشر دون موعد سيستخدم الوقت الحالي.</small></label><div class="grid grid-cols-[1fr_100px] items-end gap-3"><label class="flex h-[46px] items-center gap-2 rounded-xl bg-amber-50 px-3 text-sm font-bold text-amber-900"><input type="checkbox" wire:model="contentFeatured" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500"> إبراز المحتوى</label><label><span class="form-label">الترتيب</span><input type="number" min="0" class="form-input" wire:model="contentSortOrder"></label></div></section>
                    </aside>
                </div>
                <footer class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur sm:px-7"><button type="button" class="btn-secondary" wire:click="$set('showContentForm', false)">إلغاء</button><button type="submit" class="btn-primary" wire:loading.attr="disabled"><span wire:loading.remove wire:target="saveContent">حفظ المحتوى</span><span wire:loading wire:target="saveContent">جاري الحفظ...</span></button></footer>
            </form>
        </div>
    @endif

    @if($showMediaEditor)
        <div class="fixed inset-0 z-[75] flex items-end justify-center bg-slate-950/55 p-0 backdrop-blur-sm sm:items-center sm:p-5" wire:click.self="$set('showMediaEditor', false)">
            <form wire:submit="saveMedia" class="w-full rounded-t-[2rem] bg-white p-6 shadow-2xl sm:max-w-xl sm:rounded-[2rem] sm:p-7"><div class="flex items-center justify-between"><div><p class="eyebrow">تنظيم مكتبة الوسائط</p><h3 class="section-title">تعديل بيانات الوسيط</h3></div><button type="button" class="grid size-10 place-items-center rounded-xl bg-slate-100 text-xl text-slate-500" wire:click="$set('showMediaEditor', false)">×</button></div><div class="mt-6 grid gap-4"><label><span class="form-label">العنوان</span><input class="form-input" wire:model="editMediaTitle"></label><label><span class="form-label">النص البديل للصورة</span><input class="form-input" wire:model="editMediaAlt"><small class="mt-1 block text-[10px] text-slate-400">اكتب وصفًا موجزًا لما يظهر في الصورة.</small></label><label><span class="form-label">الوصف</span><textarea rows="3" class="form-input" wire:model="editMediaCaption"></textarea></label><div class="grid grid-cols-[1fr_110px] items-end gap-3"><label class="flex h-[46px] items-center gap-2 rounded-xl bg-emerald-50 px-4 text-sm font-bold text-emerald-900"><input type="checkbox" wire:model="editMediaGallery" class="rounded border-emerald-300 text-emerald-700 focus:ring-emerald-500"> إظهار في المعرض</label><label><span class="form-label">الترتيب</span><input type="number" min="0" class="form-input" wire:model="editMediaSortOrder"></label></div></div><div class="mt-6 flex justify-end gap-2"><button type="button" class="btn-secondary" wire:click="$set('showMediaEditor', false)">إلغاء</button><button type="submit" class="btn-primary" wire:loading.attr="disabled">حفظ التعديلات</button></div></form>
        </div>
    @endif
</div>
