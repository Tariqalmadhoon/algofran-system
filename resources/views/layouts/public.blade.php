<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>
    <meta name="description" content="{{ $description ?: 'مركز قرآني يعنى بالحفظ والتلاوة والتجويد وبناء جيل مرتبط بكتاب الله.' }}">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:locale" content="ar_AR"><meta property="og:type" content="website"><meta property="og:title" content="{{ $title ?: config('app.name') }}"><meta property="og:description" content="{{ $description ?: 'تعليم القرآن الكريم بمنهجية ورعاية متكاملة.' }}"><meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'EducationalOrganization', 'name' => config('app.name'), 'url' => url('/'), 'description' => 'مركز لتعليم القرآن الكريم والحفظ والتجويد'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="min-h-screen bg-[#fbfdfb] text-slate-900 antialiased">
    <div x-data="{ menu: false }" @keydown.escape.window="menu = false">
        <header class="sticky top-0 z-50 border-b border-emerald-950/8 bg-[#fbfdfb]/88 backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-2xl bg-emerald-800 text-xl font-black text-white shadow-lg shadow-emerald-900/20">ق</span>
                    <span><span class="block text-sm font-black text-emerald-950">مركز القرآن الكريم</span><span class="block text-[10px] font-bold tracking-wide text-emerald-700">مع القرآن نحيا ونرتقي</span></span>
                </a>
                <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="التنقل العام">
                    @foreach([
                        ['public.home','الرئيسية'], ['public.about','عن المركز'], ['public.programs','البرامج'], ['activities.index','الأنشطة'],
                        ['news.index','الأخبار'], ['public.achievements','الإنجازات'], ['public.gallery','المعرض'], ['public.contact','تواصل معنا'],
                    ] as [$routeName, $label])
                        <a href="{{ route($routeName) }}" class="rounded-xl px-3 py-2 text-sm font-bold {{ request()->routeIs($routeName) ? 'bg-emerald-100 text-emerald-900' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-800' }}">{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="mr-auto flex items-center gap-2 lg:mr-0">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary hidden sm:inline-flex">{{ auth()->check() ? 'لوحة التحكم' : 'دخول النظام' }}</a>
                    <button type="button" @click="menu = !menu" class="btn-ghost lg:hidden" aria-label="فتح القائمة"><x-nav-icon name="menu" /></button>
                </div>
            </div>
            <nav x-cloak x-show="menu" x-transition class="border-t border-emerald-950/8 bg-white px-4 py-4 shadow-xl lg:hidden">
                <div class="mx-auto grid max-w-7xl gap-1 sm:grid-cols-2">
                    @foreach([
                        ['public.home','الرئيسية'], ['public.about','عن المركز'], ['public.programs','البرامج'], ['activities.index','الأنشطة'],
                        ['news.index','الأخبار'], ['public.achievements','الإنجازات'], ['public.gallery','المعرض'], ['public.contact','تواصل معنا'],
                    ] as [$routeName, $label])<a @click="menu = false" href="{{ route($routeName) }}" class="mobile-nav-link">{{ $label }}</a>@endforeach
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mobile-nav-link text-emerald-800">{{ auth()->check() ? 'لوحة التحكم' : 'دخول النظام' }}</a>
                </div>
            </nav>
        </header>

        @if(session('success'))<div x-data="{show:true}" x-init="setTimeout(()=>show=false,5000)" x-show="show" x-transition class="fixed left-4 top-24 z-[60] max-w-sm rounded-2xl border border-emerald-200 bg-white px-5 py-4 text-sm font-bold text-emerald-800 shadow-2xl">{{ session('success') }}</div>@endif
        <main>{{ $slot }}</main>

        <footer class="relative mt-24 overflow-hidden bg-emerald-950 text-emerald-50">
            <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 30%,#fff 0 1px,transparent 2px);background-size:30px 30px"></div>
            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3 lg:px-8">
                <div><div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-white text-xl font-black text-emerald-900">ق</span><span class="font-black">مركز القرآن الكريم</span></div><p class="mt-4 max-w-sm text-sm leading-7 text-emerald-100/65">بيئة تعليمية وتربوية متكاملة للعناية بكتاب الله، حفظًا وتلاوةً وتدبرًا.</p></div>
                <div><h3 class="font-black">روابط سريعة</h3><div class="mt-4 grid grid-cols-2 gap-2 text-sm text-emerald-100/70"><a href="{{ route('public.about') }}">عن المركز</a><a href="{{ route('public.programs') }}">البرامج</a><a href="{{ route('news.index') }}">الأخبار</a><a href="{{ route('public.contact') }}">تواصل معنا</a></div></div>
                <div><h3 class="font-black">كن قريبًا</h3><p class="mt-4 text-sm leading-7 text-emerald-100/65">تابع جديد البرامج والفعاليات، أو أرسل لنا استفسارك وسنعود إليك قريبًا.</p><a href="{{ route('public.contact') }}" class="mt-4 inline-flex rounded-xl bg-white px-4 py-2 text-sm font-black text-emerald-900">أرسل رسالة</a></div>
            </div>
            <div class="relative border-t border-white/10 py-5 text-center text-xs text-emerald-100/50">© {{ now()->year }} مركز القرآن الكريم — جميع الحقوق محفوظة</div>
        </footer>
    </div>
</body>
</html>
