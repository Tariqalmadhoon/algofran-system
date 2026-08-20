<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $pageTitle = match (true) {
        request()->routeIs('dashboard') => 'لوحة المعلومات',
        request()->routeIs('organization.*') => 'الهيكل التنظيمي',
        request()->routeIs('students.*') => 'إدارة الطلاب',
        request()->routeIs('teacher.daily') => 'التسجيل اليومي',
        request()->routeIs('academic.*') => 'الإدارة الأكاديمية',
        request()->routeIs('alerts.*') => 'مركز التنبيهات',
        request()->routeIs('calendar.*') => 'التقويم',
        request()->routeIs('notifications.*') => 'الإشعارات',
        request()->routeIs('reports.*') => 'مركز التقارير',
        request()->routeIs('cms.*') => 'إدارة الموقع العام',
        request()->routeIs('profile.*') => 'الملف الشخصي',
        default => config('app.name'),
    };
    $unreadNotifications = auth()->user()->unreadNotifications()->count();
@endphp
<body class="min-h-screen overflow-x-hidden bg-[#f5f7f6] text-slate-900 antialiased">
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: JSON.parse(localStorage.getItem('alquran-sidebar-collapsed') ?? 'false'),
            userMenu: false,
            notificationMenu: false,
            unreadNotifications: {{ $unreadNotifications }},
            realtimeNotification: null,
            toggleSidebar() {
                if (window.innerWidth < 1024) this.sidebarOpen = !this.sidebarOpen;
                else this.sidebarCollapsed = !this.sidebarCollapsed;
            },
            receiveNotification(event) {
                this.unreadNotifications++;
                this.realtimeNotification = event.detail;
                setTimeout(() => this.realtimeNotification = null, 6500);
            }
        }"
        x-init="$watch('sidebarCollapsed', value => localStorage.setItem('alquran-sidebar-collapsed', JSON.stringify(value)))"
        @keydown.escape.window="sidebarOpen = false; userMenu = false; notificationMenu = false"
        @alquran:notification.window="receiveNotification($event)"
        class="min-h-screen"
    >
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <aside
            :class="[sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0', sidebarCollapsed ? 'lg:w-24' : 'lg:w-72']"
            class="fixed inset-y-0 right-0 z-50 flex w-72 flex-col overflow-hidden bg-[linear-gradient(165deg,#073b31_0%,#0b513e_52%,#08372f_100%)] text-white shadow-2xl shadow-emerald-950/25 transition-all duration-300 ease-out"
        >
            <div class="pointer-events-none absolute -left-20 top-20 size-56 rounded-full bg-emerald-300/7 blur-3xl"></div>
            <div class="flex h-20 shrink-0 items-center gap-3 border-b border-white/10 px-5" :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white text-xl font-black text-emerald-900 shadow-lg shadow-black/10">ق</span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="min-w-0 lg:block">
                        <span class="block truncate text-sm font-extrabold">مركز القرآن الكريم</span>
                        <span class="block truncate pt-0.5 text-[11px] text-emerald-100/65">نظام الإدارة المؤسسية</span>
                    </span>
                </a>
                <button type="button" class="mr-auto rounded-xl p-2 text-emerald-100/70 hover:bg-white/10 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="إغلاق القائمة">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <nav class="relative flex-1 overflow-y-auto overflow-x-hidden px-3 pb-5" aria-label="التنقل الرئيسي">
                <p class="sidebar-section" x-show="!sidebarCollapsed">نظرة عامة</p>
                <a class="sidebar-link group {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}" href="{{ route('dashboard') }}" title="لوحة المعلومات">
                    <span class="sidebar-icon"><x-nav-icon name="home" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>لوحة المعلومات</span>
                </a>

                <p class="sidebar-section" x-show="!sidebarCollapsed">الإدارة والمتابعة</p>
                @can('organization.view')
                    <a class="sidebar-link group {{ request()->routeIs('organization.*') ? 'sidebar-link-active' : '' }}" href="{{ route('organization.index') }}" title="الهيكل التنظيمي">
                        <span class="sidebar-icon"><x-nav-icon name="organization" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>الهيكل التنظيمي</span>
                    </a>
                @endcan
                @can('students.view')
                    <a class="sidebar-link group {{ request()->routeIs('students.*') ? 'sidebar-link-active' : '' }}" href="{{ route('students.index') }}" title="الطلاب">
                        <span class="sidebar-icon"><x-nav-icon name="students" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>الطلاب</span>
                    </a>
                @endcan
                @if(auth()->user()->can('recitations.create') && auth()->user()->teacherProfile?->active)
                    <a class="sidebar-link group {{ request()->routeIs('teacher.daily') ? 'sidebar-link-active' : '' }}" href="{{ route('teacher.daily') }}" title="التسجيل اليومي">
                        <span class="sidebar-icon"><x-nav-icon name="daily" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>التسجيل اليومي</span>
                    </a>
                @endif
                @can('courses.manage')
                    <a class="sidebar-link group {{ request()->routeIs('academic.*') ? 'sidebar-link-active' : '' }}" href="{{ route('academic.index') }}" title="الإدارة الأكاديمية">
                        <span class="sidebar-icon"><x-nav-icon name="academic" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>الإدارة الأكاديمية</span>
                    </a>
                @endcan
                @can('alerts.view')
                    <a class="sidebar-link group {{ request()->routeIs('alerts.*') ? 'sidebar-link-active' : '' }}" href="{{ route('alerts.index') }}" title="التنبيهات">
                        <span class="sidebar-icon"><x-nav-icon name="alerts" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>التنبيهات</span>
                    </a>
                @endcan

                <p class="sidebar-section" x-show="!sidebarCollapsed">التخطيط والتقارير</p>
                @can('calendar.view')
                    <a class="sidebar-link group {{ request()->routeIs('calendar.*') ? 'sidebar-link-active' : '' }}" href="{{ route('calendar.index') }}" title="التقويم">
                        <span class="sidebar-icon"><x-nav-icon name="calendar" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>التقويم</span>
                    </a>
                @endcan
                @can('reports.view')
                    <a class="sidebar-link group {{ request()->routeIs('reports.*') ? 'sidebar-link-active' : '' }}" href="{{ route('reports.index') }}" title="مركز التقارير">
                        <span class="sidebar-icon"><x-nav-icon name="reports" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>مركز التقارير</span>
                    </a>
                @endcan
                @can('website.manage')
                    <a class="sidebar-link group {{ request()->routeIs('cms.*') ? 'sidebar-link-active' : '' }}" href="{{ route('cms.index') }}" title="إدارة الموقع">
                        <span class="sidebar-icon"><x-nav-icon name="organization" /></span><span x-show="!sidebarCollapsed" x-transition.opacity>إدارة الموقع</span>
                    </a>
                @endcan
            </nav>

            <div class="relative border-t border-white/10 p-3">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-white/10" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-200/15 text-sm font-black text-emerald-50">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0">
                        <span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-[11px] text-emerald-100/60">{{ auth()->user()->getRoleNames()->first() ?? 'مستخدم' }}</span>
                    </span>
                </a>
            </div>
        </aside>

        <div :class="sidebarCollapsed ? 'lg:pr-24' : 'lg:pr-72'" class="min-h-screen transition-[padding] duration-300 ease-out">
            <header class="sticky top-0 z-30 h-20 border-b border-slate-200/75 bg-white/88 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <div class="mx-auto flex h-full max-w-[1600px] items-center gap-3">
                    <button type="button" class="btn-ghost" @click="toggleSidebar()" :aria-label="sidebarCollapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية'">
                        <x-nav-icon name="menu" class="size-5 lg:hidden" />
                        <x-nav-icon name="collapse" class="hidden size-5 transition-transform duration-300 lg:block" ::class="sidebarCollapsed ? 'rotate-180' : ''" />
                    </button>
                    <div class="min-w-0">
                        <p class="hidden text-[11px] font-bold text-slate-400 sm:block">الرئيسية / {{ $pageTitle }}</p>
                        <h1 class="truncate text-base font-extrabold text-slate-900 sm:text-lg">{{ $pageTitle }}</h1>
                    </div>

                    <div class="mr-auto flex items-center gap-1 sm:gap-2">
                        @if(Route::has('notifications.index'))
                            <div class="relative" @click.outside="notificationMenu = false">
                                <button type="button" class="btn-ghost relative" @click="notificationMenu = !notificationMenu; userMenu = false" aria-label="الإشعارات">
                                    <x-nav-icon name="bell" />
                                    <span x-cloak x-show="unreadNotifications > 0" x-text="Math.min(unreadNotifications, 99)" class="absolute right-1 top-1 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-black leading-4 text-white ring-2 ring-white"></span>
                                </button>
                                <div x-cloak x-show="notificationMenu" x-transition.origin.top.left class="absolute left-0 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
                                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><span class="font-extrabold text-slate-900">الإشعارات</span><span class="text-xs text-slate-400"><span x-text="unreadNotifications"></span> غير مقروء</span></div>
                                    <div class="max-h-72 overflow-y-auto">
                                        @forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)
                                            <a href="{{ route('notifications.index') }}" class="block border-b border-slate-100 px-4 py-3 hover:bg-emerald-50/60"><p class="text-sm font-bold text-slate-800">{{ data_get($notification->data, 'title', 'إشعار جديد') }}</p><p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ data_get($notification->data, 'message') }}</p></a>
                                        @empty
                                            <p class="px-4 py-8 text-center text-sm text-slate-400">لا توجد إشعارات بعد</p>
                                        @endforelse
                                    </div>
                                    <a href="{{ route('notifications.index') }}" class="block px-4 py-3 text-center text-sm font-bold text-emerald-700 hover:bg-emerald-50">عرض جميع الإشعارات</a>
                                </div>
                            </div>
                        @endif

                        <div class="relative" @click.outside="userMenu = false">
                            <button type="button" class="flex items-center gap-2 rounded-2xl p-1.5 hover:bg-slate-100" @click="userMenu = !userMenu; notificationMenu = false">
                                <span class="grid size-9 place-items-center rounded-xl bg-emerald-100 text-sm font-black text-emerald-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                <span class="hidden max-w-36 truncate text-sm font-bold text-slate-700 md:block">{{ auth()->user()->name }}</span>
                                <svg class="hidden size-4 text-slate-400 md:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-cloak x-show="userMenu" x-transition.origin.top.left class="absolute left-0 mt-2 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><x-nav-icon name="profile" /> الملف الشخصي</a>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm font-bold text-rose-600 hover:bg-rose-50" type="submit"><x-nav-icon name="logout" /> تسجيل الخروج</button></form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1600px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
                <div x-cloak x-show="realtimeNotification" x-transition class="fixed left-4 top-24 z-[70] w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-emerald-200 bg-white p-4 shadow-2xl shadow-emerald-950/15" role="status">
                    <p class="text-sm font-black text-slate-900" x-text="realtimeNotification?.title ?? 'إشعار جديد'"></p>
                    <p class="mt-1 text-xs leading-5 text-slate-500" x-text="realtimeNotification?.message"></p>
                    <a x-show="realtimeNotification?.url" :href="realtimeNotification?.url" class="mt-2 inline-block text-xs font-black text-emerald-700">فتح التفاصيل</a>
                </div>
                @if (session('success'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition class="mb-6 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm" role="status"><span>{{ session('success') }}</span><button type="button" @click="show = false" class="p-1 text-emerald-600">×</button></div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800" role="alert">{{ session('error') }}</div>
                @endif
                <div class="animate-[fade-in_.35s_ease-out]">{{ $slot }}</div>
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
