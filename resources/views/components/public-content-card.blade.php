@props(['item', 'routeName', 'kind' => null])

<article {{ $attributes->class('public-content-card group h-full overflow-hidden rounded-[2rem] border border-emerald-950/8 bg-white shadow-[0_20px_70px_-45px_rgba(6,78,59,.45)] transition duration-500 hover:border-emerald-200 hover:shadow-[0_30px_85px_-42px_rgba(6,78,59,.55)]') }}>
    <a href="{{ route($routeName, $item) }}" class="flex h-full flex-col">
        <div class="relative aspect-[16/10] overflow-hidden bg-[linear-gradient(135deg,#dff8ea,#b8ead2)]">
            @if($item->featuredMedia)
                <img src="{{ $item->featuredMedia->url }}" alt="{{ $item->featuredMedia->alt_text ?: $item->title }}" class="size-full object-cover transition duration-700 group-hover:scale-105" loading="lazy" decoding="async">
            @else
                <div class="absolute inset-0 opacity-35 [background-image:radial-gradient(circle_at_20%_30%,#059669_0_2px,transparent_3px)] [background-size:28px_28px]"></div>
                <span class="absolute inset-0 grid place-items-center"><x-brand-logo size="lg" class="opacity-65" /></span>
            @endif
            <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-emerald-950/35 to-transparent opacity-0 transition duration-500 group-hover:opacity-100"></div>
            <span class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-black text-emerald-800 shadow-sm backdrop-blur">{{ $kind === 'activity' || $item->type === 'activity' ? 'نشاط' : ($item->type === 'news' ? 'خبر' : 'من المركز') }}</span>
            @if($item->featured)<span class="absolute left-4 top-4 rounded-full bg-amber-300 px-3 py-1 text-[11px] font-black text-amber-950 shadow-sm">مميز</span>@endif
        </div>
        <div class="flex flex-1 flex-col p-5 sm:p-6">
            <p class="flex items-center gap-2 text-[11px] font-bold text-emerald-700"><span class="size-1.5 rounded-full bg-amber-400"></span>{{ $item->published_at?->translatedFormat('j F Y') }}</p>
            <h3 class="mt-3 text-lg font-black leading-8 text-emerald-950 transition group-hover:text-emerald-700">{{ $item->title }}</h3>
            <p class="mt-2 line-clamp-2 text-sm leading-7 text-slate-500">{{ $item->excerpt ?: str(strip_tags($item->body))->limit(120) }}</p>
            <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-black text-emerald-700">اقرأ المزيد <span class="transition duration-300 group-hover:-translate-x-1">←</span></span>
        </div>
    </a>
</article>
