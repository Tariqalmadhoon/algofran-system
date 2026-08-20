@props(['item', 'routeName'])
<article class="group overflow-hidden rounded-[2rem] border border-emerald-950/8 bg-white shadow-[0_20px_70px_-45px_rgba(6,78,59,.45)] transition duration-500 hover:-translate-y-2 hover:shadow-[0_28px_80px_-40px_rgba(6,78,59,.55)]">
    <a href="{{ route($routeName, $item) }}" class="block">
        <div class="relative aspect-[16/10] overflow-hidden bg-[linear-gradient(135deg,#dff8ea,#b8ead2)]">
            @if($item->featuredMedia)
                <img src="{{ $item->featuredMedia->url }}" alt="{{ $item->featuredMedia->alt_text ?: $item->title }}" class="size-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">
            @else
                <div class="absolute inset-0 opacity-35" style="background-image:radial-gradient(circle at 20% 30%,#059669 0 2px,transparent 3px);background-size:28px 28px"></div>
                <span class="absolute inset-0 grid place-items-center text-5xl font-black text-emerald-800/20">ق</span>
            @endif
            @if($item->featured)<span class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-black text-emerald-800 shadow-sm backdrop-blur">مميز</span>@endif
        </div>
        <div class="p-5 sm:p-6">
            <p class="text-[11px] font-bold text-emerald-700">{{ $item->published_at?->translatedFormat('j F Y') }}</p>
            <h3 class="mt-2 text-lg font-black leading-8 text-emerald-950 transition group-hover:text-emerald-700">{{ $item->title }}</h3>
            <p class="mt-2 line-clamp-2 text-sm leading-7 text-slate-500">{{ $item->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($item->body), 120) }}</p>
            <span class="mt-4 inline-flex items-center gap-2 text-sm font-black text-emerald-700">اقرأ المزيد <span class="transition group-hover:-translate-x-1">←</span></span>
        </div>
    </a>
</article>
