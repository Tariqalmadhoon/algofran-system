<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset(config('app.logo')) }}">
    <meta name="description" content="{{ $description ?: 'مركز قرآني يعنى بالحفظ والتلاوة والتجويد وبناء جيل مرتبط بكتاب الله.' }}">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:locale" content="ar_AR"><meta property="og:type" content="website"><meta property="og:title" content="{{ $title ?: config('app.name') }}"><meta property="og:description" content="{{ $description ?: 'تعليم القرآن الكريم بمنهجية ورعاية متكاملة.' }}"><meta property="og:url" content="{{ url()->current() }}"><meta property="og:image" content="{{ asset(config('app.logo')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $organizationSchema = [
            '@'.'context' => 'https://schema.org',
            '@'.'type' => 'EducationalOrganization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset(config('app.logo')),
            'description' => 'مركز لتحفيظ القرآن الكريم وتعليم التلاوة والتجويد',
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="min-h-screen bg-[#fbfdfb] text-slate-900 antialiased">
    <div x-data="{ menu: false }" @keydown.escape.window="menu = false">
        <header class="public-site-header sticky top-0 z-50 border-b border-emerald-950/8 bg-[#fbfdfb]/88 backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                    <x-brand-logo size="xs" />
                    <span><span class="block max-w-48 text-xs font-black leading-5 text-emerald-950 sm:text-sm">{{ config('app.name') }}</span><span class="block text-[10px] font-bold tracking-wide text-emerald-700">مع القرآن نحيا ونرتقي</span></span>
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

        <x-flash-messages />
        <main data-page-reveal>{{ $slot }}</main>

        <footer data-reveal class="relative mt-24 overflow-hidden bg-emerald-950 text-emerald-50">
            <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 30%,#fff 0 1px,transparent 2px);background-size:30px 30px"></div>
            <div data-reveal-group="up" data-reveal-stagger="70" class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3 lg:px-8">
                <div><div class="flex items-center gap-3"><x-brand-logo size="sm" /><span class="max-w-52 font-black leading-7">{{ config('app.name') }}</span></div><p class="mt-4 max-w-sm text-sm leading-7 text-emerald-100/65">بيئة تعليمية وتربوية متكاملة للعناية بكتاب الله، حفظًا وتلاوةً وتدبرًا.</p></div>
                <div><h3 class="font-black">روابط سريعة</h3><div class="mt-4 grid grid-cols-2 gap-2 text-sm text-emerald-100/70"><a href="{{ route('public.about') }}">عن المركز</a><a href="{{ route('public.programs') }}">البرامج</a><a href="{{ route('news.index') }}">الأخبار</a><a href="{{ route('public.contact') }}">تواصل معنا</a></div></div>
                <div><h3 class="font-black">كن قريبًا</h3><p class="mt-4 text-sm leading-7 text-emerald-100/65">تابع جديد البرامج والفعاليات، أو أرسل لنا استفسارك وسنعود إليك قريبًا.</p><a href="{{ route('public.contact') }}" class="mt-4 inline-flex rounded-xl bg-white px-4 py-2 text-sm font-black text-emerald-900">أرسل رسالة</a></div>
            </div>
            <div class="relative border-t border-white/10 py-5 text-center text-xs text-emerald-100/50">© {{ now()->year }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
        </footer>
    </div>
</body>
</html>
