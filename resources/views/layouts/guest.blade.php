<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset(config('app.logo')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-emerald-950 text-slate-900 antialiased">
    <main data-page-reveal class="relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
        <div data-motion-ignore aria-hidden="true" class="absolute inset-0 opacity-40 [background-image:radial-gradient(circle_at_20%_20%,#d6b56d_0,transparent_28%),radial-gradient(circle_at_80%_80%,#0f766e_0,transparent_32%)]"></div>
        <section data-reveal="scale" class="relative w-full max-w-md rounded-3xl border border-white/20 bg-white p-7 shadow-2xl sm:p-9">
            <div class="mb-7 text-center">
                <x-brand-logo size="lg" class="mx-auto mb-4" />
                <h1 class="text-2xl font-extrabold leading-9 text-emerald-950">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-slate-500">نظام الإدارة والمتابعة المؤسسية</p>
            </div>
            <x-flash-messages inline />
            {{ $slot }}
        </section>
    </main>
</body>
</html>
