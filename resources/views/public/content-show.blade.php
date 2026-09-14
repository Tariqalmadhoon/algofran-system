<x-public-shell :title="$content->meta_title ?: $content->title" :description="$content->meta_description ?: $content->excerpt">
    @php
        $related = $related ?? collect();
        $routeName = $routeName ?? null;
    @endphp

    <article class="bg-stone-50">
        <header class="relative isolate overflow-hidden bg-emerald-950 pb-28 pt-16 text-white sm:pb-36 sm:pt-20">
            <div aria-hidden="true" class="absolute inset-0 opacity-30 [background-image:radial-gradient(circle_at_18%_20%,rgba(251,191,36,.32)_0,transparent_24%),radial-gradient(circle_at_83%_75%,rgba(20,184,166,.55)_0,transparent_30%)]"></div>
            <svg aria-hidden="true" class="absolute inset-x-0 bottom-0 h-24 w-full text-stone-50" viewBox="0 0 1440 110" preserveAspectRatio="none"><path fill="currentColor" d="M0 68c245 50 473 25 698-5 276-37 508-53 742 10v37H0Z"/></svg>
            <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6">
                <nav class="flex items-center justify-center gap-2 text-xs font-black text-emerald-100/70" data-reveal="up"><a href="{{ route('public.home') }}" class="transition hover:text-white">الرئيسية</a><span>/</span><a href="{{ route($backRoute) }}" class="transition hover:text-white">{{ $section }}</a></nav>
                <div class="mt-6 flex items-center justify-center gap-3" data-reveal="up"><span class="h-px w-10 bg-amber-300/70"></span><time class="text-xs font-black text-amber-200">{{ $content->published_at?->translatedFormat('j F Y') }}</time><span class="h-px w-10 bg-amber-300/70"></span></div>
                <h1 class="mx-auto mt-6 max-w-4xl text-3xl font-black leading-[1.5] sm:text-5xl lg:text-6xl" data-reveal="up">{{ $content->title }}</h1>
                @if($content->excerpt)<p class="mx-auto mt-6 max-w-2xl text-base leading-8 text-emerald-50/70 sm:text-lg" data-reveal="up">{{ $content->excerpt }}</p>@endif
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-4 pb-20 sm:px-6 sm:pb-28">
            @if($content->featuredMedia)
                <figure class="relative z-10 -mt-20 overflow-hidden rounded-[2rem] border-4 border-white bg-white shadow-[0_35px_100px_-45px_rgba(6,78,59,.5)] sm:-mt-28 sm:rounded-[2.75rem]" data-reveal="scale">
                    <img class="aspect-[16/8.5] w-full object-cover" src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt_text ?: $content->title }}" decoding="async" fetchpriority="high">
                    @if($content->featuredMedia->caption)<figcaption class="border-t border-slate-100 px-5 py-3 text-center text-xs text-slate-400">{{ $content->featuredMedia->caption }}</figcaption>@endif
                </figure>
            @else
                <div class="relative z-10 -mt-16 h-2 rounded-full bg-gradient-to-l from-amber-400 via-emerald-500 to-teal-500 shadow-lg" data-reveal="scale"></div>
            @endif

            <div class="mx-auto mt-10 max-w-3xl sm:mt-14">
                <div class="rounded-[2rem] border border-slate-200/70 bg-white p-6 shadow-[0_22px_70px_-50px_rgba(15,23,42,.4)] sm:p-10" data-reveal="up">
                    <div class="cms-public-prose whitespace-pre-line text-base leading-9 text-slate-600 sm:text-lg sm:leading-10">{{ $content->body }}</div>
                </div>
                <div class="mt-8 flex flex-col items-center justify-between gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 sm:flex-row" data-reveal="up">
                    <p class="text-center text-xs font-bold text-emerald-900 sm:text-right">مركز الغفران لتحفيظ القرآن الكريم · نصنع أثرًا يبقى</p>
                    <a href="{{ route($backRoute) }}" class="btn-secondary shrink-0">العودة إلى {{ $section }} <span aria-hidden="true">←</span></a>
                </div>
            </div>
        </div>
    </article>

    @if($related->isNotEmpty() && $routeName)
        <section class="border-t border-emerald-950/5 bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-end justify-between gap-4" data-reveal="up"><div><p class="text-xs font-black text-amber-700">واصل الاستكشاف</p><h2 class="mt-2 text-2xl font-black text-emerald-950 sm:text-3xl">قد يهمك أيضًا</h2></div><a href="{{ route($backRoute) }}" class="hidden text-sm font-black text-emerald-700 transition hover:-translate-x-1 sm:block">عرض الكل ←</a></div>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-reveal-group="up" data-reveal-stagger="70">@foreach($related as $item)<x-public-content-card :item="$item" :route-name="$routeName" :kind="$content->type" />@endforeach</div>
            </div>
        </section>
    @endif
</x-public-shell>
