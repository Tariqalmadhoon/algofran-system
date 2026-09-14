<x-public-shell :title="$heading" :description="$description">
    @php
        $spotlight = $items->first();
        $rest = $items->skip(1);
        $isNews = $contentKind === 'news';
    @endphp

    <section class="relative isolate overflow-hidden bg-emerald-950 pb-24 pt-20 text-white sm:pb-28 sm:pt-24">
        <div aria-hidden="true" class="absolute inset-0 opacity-25 [background-image:radial-gradient(circle_at_20%_20%,rgba(251,191,36,.42)_0,transparent_24%),radial-gradient(circle_at_82%_70%,rgba(20,184,166,.55)_0,transparent_30%)]"></div>
        <svg aria-hidden="true" class="absolute inset-x-0 bottom-0 h-24 w-full text-stone-50" viewBox="0 0 1440 110" preserveAspectRatio="none"><path fill="currentColor" d="M0 76c222 36 436 27 641-4 261-39 511-74 799-8v46H0Z"/></svg>
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-[1fr_340px]">
                <div data-reveal="up">
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black text-amber-200 backdrop-blur"><span class="size-2 rounded-full bg-amber-300"></span>{{ $eyebrow }}</p>
                    <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">{{ $heading }}</h1>
                    <p class="mt-5 max-w-2xl text-base leading-8 text-emerald-50/70 sm:text-lg">{{ $description }}</p>
                </div>
                <div class="hidden lg:block" data-reveal="scale">
                    <div class="relative mx-auto grid aspect-square w-60 place-items-center rounded-full border border-white/10 bg-white/5 backdrop-blur-sm">
                        <div class="absolute inset-5 rounded-full border border-dashed border-amber-200/25 motion-safe:animate-[spin_28s_linear_infinite]"></div>
                        <div class="grid size-28 place-items-center rounded-full bg-white/10 text-amber-200 shadow-[0_0_70px_rgba(251,191,36,.12)]">
                            @if($isNews)<svg class="size-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M5 4h14v16H5z"/><path d="M8 8h5M8 12h8M8 16h8M16 7v3"/></svg>@else<svg class="size-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M4 7h16v13H4z"/><path d="M8 4v6m8-6v6M4 11h16"/><path d="m9 16 2 2 4-4"/></svg>@endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-stone-50 pb-24 pt-5 sm:pb-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if($spotlight)
                <article class="public-feature-card group relative z-10 -mt-16 overflow-hidden rounded-[2rem] border border-emerald-950/5 bg-white shadow-[0_35px_90px_-48px_rgba(6,78,59,.55)]" data-reveal="up">
                    <a href="{{ route($routeName, $spotlight) }}" class="grid lg:grid-cols-[1.15fr_.85fr]">
                        <div class="relative min-h-72 overflow-hidden bg-gradient-to-br from-emerald-100 to-teal-50 lg:min-h-[430px]">
                            @if($spotlight->featuredMedia)
                                <img src="{{ $spotlight->featuredMedia->url }}" alt="{{ $spotlight->featuredMedia->alt_text ?: $spotlight->title }}" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105" decoding="async" fetchpriority="high">
                            @else
                                <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(circle,#059669_1.5px,transparent_1.5px)] [background-size:27px_27px]"></div><div class="absolute inset-0 grid place-items-center"><x-brand-logo size="xl" class="opacity-70" /></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/45 via-transparent to-transparent lg:hidden"></div>
                            <span class="absolute right-5 top-5 rounded-full bg-white/90 px-4 py-2 text-xs font-black text-emerald-900 shadow-lg backdrop-blur">أحدث {{ $isNews ? 'خبر' : 'نشاط' }}</span>
                        </div>
                        <div class="flex flex-col justify-center p-6 sm:p-9 lg:p-11">
                            <div class="flex items-center gap-3 text-xs font-black text-emerald-700"><span class="h-px w-9 bg-amber-400"></span><time>{{ $spotlight->published_at?->translatedFormat('j F Y') }}</time>@if($spotlight->featured)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">مميز</span>@endif</div>
                            <h2 class="mt-5 text-2xl font-black leading-[1.55] text-emerald-950 transition group-hover:text-emerald-700 sm:text-3xl">{{ $spotlight->title }}</h2>
                            <p class="mt-4 line-clamp-3 text-sm leading-8 text-slate-500 sm:text-base">{{ $spotlight->excerpt ?: str(strip_tags($spotlight->body))->limit(180) }}</p>
                            <span class="mt-7 inline-flex w-fit items-center gap-3 rounded-full bg-emerald-950 px-5 py-3 text-sm font-black text-white transition duration-300 group-hover:-translate-x-1 group-hover:bg-emerald-800">اقرأ التفاصيل <span aria-hidden="true">←</span></span>
                        </div>
                    </a>
                </article>

                @if($rest->isNotEmpty())
                    <div class="mb-7 mt-14 flex items-end justify-between gap-4" data-reveal="up"><div><p class="text-xs font-black text-amber-700">المزيد من المركز</p><h2 class="mt-1 text-2xl font-black text-emerald-950">{{ $isNews ? 'أخبار أخرى' : 'فعاليات ومحطات أخرى' }}</h2></div><span class="hidden text-xs font-bold text-slate-400 sm:block">{{ $items->total() }} مادة منشورة</span></div>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-reveal-group="up" data-reveal-stagger="70">
                        @foreach($rest as $item)<x-public-content-card :item="$item" :route-name="$routeName" :kind="$contentKind" />@endforeach
                    </div>
                @endif
            @else
                <div class="mx-auto max-w-2xl rounded-[2.5rem] border border-dashed border-emerald-200 bg-white px-6 py-16 text-center shadow-[0_20px_70px_-50px_rgba(6,78,59,.4)]" data-reveal="scale">
                    <span class="mx-auto grid size-20 place-items-center rounded-full bg-emerald-50 text-emerald-700"><svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                    <h2 class="mt-6 text-xl font-black text-emerald-950">نجهّز محتوى جديدًا</h2><p class="mt-2 text-sm leading-7 text-slate-500">ستظهر هنا آخر أخبار مركز الغفران وأنشطته فور نشرها من لوحة الإدارة.</p>
                </div>
            @endif

            @if($items->hasPages())<div class="mt-12" data-reveal="up">{{ $items->links() }}</div>@endif
        </div>
    </section>
</x-public-shell>
