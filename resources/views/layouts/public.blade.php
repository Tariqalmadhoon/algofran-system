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
    @vite(['resources/css/app.css', 'resources/js/public-site.js'])
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
<body class="public-site min-h-screen bg-[#fbfdfb] text-slate-900 antialiased">
    @php
        $publicNavigation = [
            ['route' => 'public.home', 'active' => 'public.home', 'label' => 'الرئيسية'],
            ['route' => 'public.about', 'active' => 'public.about', 'label' => 'عن المركز'],
            ['route' => 'public.programs', 'active' => 'public.programs', 'label' => 'البرامج'],
            ['route' => 'activities.index', 'active' => 'activities.*', 'label' => 'الأنشطة'],
            ['route' => 'news.index', 'active' => 'news.*', 'label' => 'الأخبار'],
            ['route' => 'public.achievements', 'active' => 'public.achievements', 'label' => 'الإنجازات'],
            ['route' => 'public.gallery', 'active' => 'public.gallery', 'label' => 'المعرض'],
            ['route' => 'public.contact', 'active' => 'public.contact*', 'label' => 'تواصل معنا'],
        ];
    @endphp

    <a href="#main-content" class="fixed right-4 top-3 z-[70] -translate-y-20 rounded-xl bg-emerald-950 px-4 py-2 text-sm font-black text-white shadow-xl transition focus:translate-y-0">انتقل إلى المحتوى</a>

    <div data-public-navigation>
        <header data-public-header class="public-site-header sticky top-0 z-50 border-b border-emerald-950/8 bg-[#fbfdfb]/88 backdrop-blur-xl">
            <div class="public-header-inner mx-auto flex h-20 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                    <x-brand-logo size="xs" />
                    <span><span class="block max-w-48 text-xs font-black leading-5 text-emerald-950 sm:text-sm">{{ config('app.name') }}</span><span class="block text-[10px] font-bold tracking-wide text-emerald-700">مع القرآن نحيا ونرتقي</span></span>
                </a>
                <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="التنقل العام">
                    @foreach($publicNavigation as $item)
                        @php($isActive = request()->routeIs($item['active']))
                        <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif class="public-nav-link rounded-xl px-3 py-2 text-sm font-bold {{ $isActive ? 'bg-emerald-100 text-emerald-900' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-800' }}">{{ $item['label'] }}</a>
                    @endforeach
                </nav>
                <div class="mr-auto flex items-center gap-2 lg:mr-0">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary hidden sm:inline-flex">{{ auth()->check() ? 'لوحة التحكم' : 'دخول النظام' }}</a>
                    <button data-public-menu-trigger type="button" class="btn-ghost lg:hidden" aria-controls="public-mobile-menu" aria-expanded="false" aria-label="فتح القائمة"><span class="public-menu-icon"><x-nav-icon name="menu" /></span></button>
                </div>
            </div>
            <nav id="public-mobile-menu" data-public-mobile-menu aria-label="التنقل العام للهاتف" aria-hidden="true" inert class="public-mobile-menu px-4 py-4 lg:hidden">
                <div class="mx-auto grid max-w-7xl gap-1 sm:grid-cols-2">
                    @foreach($publicNavigation as $item)
                        @php($isActive = request()->routeIs($item['active']))
                        <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif class="mobile-nav-link {{ $isActive ? 'bg-emerald-50 text-emerald-900' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mobile-nav-link text-emerald-800">{{ auth()->check() ? 'لوحة التحكم' : 'دخول النظام' }}</a>
                </div>
            </nav>
            <span aria-hidden="true" class="public-scroll-progress"></span>
            <span aria-hidden="true" class="public-route-progress"></span>
        </header>

        <x-flash-messages />
        <main id="main-content" class="public-main" data-page-reveal>{{ $slot }}</main>

        <footer data-reveal class="relative mt-24 overflow-hidden bg-emerald-950 text-emerald-50">
            <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 30%,#fff 0 1px,transparent 2px);background-size:30px 30px"></div>
            <div data-reveal-group="up" data-reveal-stagger="70" class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3 lg:px-8">
                <div><div class="flex items-center gap-3"><x-brand-logo size="sm" /><span class="max-w-52 font-black leading-7">{{ config('app.name') }}</span></div><p class="mt-4 max-w-sm text-sm leading-7 text-emerald-100/65">بيئة تعليمية وتربوية متكاملة للعناية بكتاب الله، حفظًا وتلاوةً وتدبرًا.</p></div>
                <div><h3 class="font-black">روابط سريعة</h3><div class="mt-4 grid grid-cols-2 gap-2 text-sm text-emerald-100/70"><a class="hover:text-white" href="{{ route('public.about') }}">عن المركز</a><a class="hover:text-white" href="{{ route('public.programs') }}">البرامج</a><a class="hover:text-white" href="{{ route('news.index') }}">الأخبار</a><a class="hover:text-white" href="{{ route('public.contact') }}">تواصل معنا</a></div></div>
                <div><h3 class="font-black">كن قريبًا</h3><p class="mt-4 text-sm leading-7 text-emerald-100/65">تابع جديد البرامج والفعاليات، وللاستفسار أو التسجيل تواصل معنا مباشرة عبر واتساب.</p><a href="{{ route('public.contact') }}" class="mt-4 inline-flex rounded-xl bg-white px-4 py-2 text-sm font-black text-emerald-900">تواصل عبر واتساب</a></div>
            </div>
            <div class="relative border-t border-white/10 py-5 text-center text-xs text-emerald-100/50">© {{ now()->year }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
        </footer>
    </div>
    <x-whatsapp-float />
</body>
</html>
