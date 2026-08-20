<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-emerald-950 text-slate-900 antialiased">
    <main class="relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
        <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(circle_at_20%_20%,#d6b56d_0,transparent_28%),radial-gradient(circle_at_80%_80%,#0f766e_0,transparent_32%)]"></div>
        <section class="relative w-full max-w-md rounded-3xl border border-white/20 bg-white p-7 shadow-2xl sm:p-9">
            <div class="mb-7 text-center">
                <div class="mx-auto mb-4 grid size-16 place-items-center rounded-2xl bg-emerald-800 text-3xl font-bold text-white shadow-lg">ق</div>
                <h1 class="text-2xl font-extrabold text-emerald-950">مركز القرآن الكريم</h1>
                <p class="mt-1 text-sm text-slate-500">نظام الإدارة المؤسسية</p>
            </div>
            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </section>
    </main>
</body>
</html>
