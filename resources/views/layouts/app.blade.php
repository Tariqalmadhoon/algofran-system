<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset(config('app.logo')) }}">
    <script>
        try {
            document.documentElement.dataset.sidebarCollapsed = JSON.parse(localStorage.getItem('alquran-sidebar-collapsed') ?? 'false') ? 'true' : 'false';
        } catch (error) {
            document.documentElement.dataset.sidebarCollapsed = 'false';
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $pageTitle = match (true) {
        request()->routeIs('dashboard') => 'لوحة المعلومات',
        request()->routeIs('organization.*') => 'الهيكل التنظيمي',
        request()->routeIs('access.*') => 'الحسابات والصلاحيات',
        request()->routeIs('students.*') => 'إدارة الطلاب',
        request()->routeIs('teacher.daily') => 'التسجيل اليومي',
        request()->routeIs('academic.*') => 'الإدارة الأكاديمية',
        request()->routeIs('alerts.*') => 'مركز التنبيهات',
        request()->routeIs('calendar.*') => 'التقويم',
        request()->routeIs('notifications.*') => 'الإشعارات',
        request()->routeIs('reports.*') => 'مركز التقارير',
        request()->routeIs('cms.*') => 'إدارة الموقع العام',
        request()->routeIs('mobile.distribution') => 'توزيع تطبيق المحفّظ',
        request()->routeIs('profile.*') => 'الملف الشخصي',
        default => config('app.name'),
    };
    $unreadNotifications = auth()->user()->unreadNotifications()->count();
    $teachingProfile = auth()->user()->can('recitations.create')
        ? auth()->user()->teacherProfile()->where('active', true)->with('center:id,name')->first()
        : null;
    $assignedTeachingHalaqas = $teachingProfile
        ? $teachingProfile->assignments()
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->whereHas('halaqa', fn ($halaqa) => $halaqa->where('active', true))
            ->with('halaqa:id,name')
            ->get()
            ->pluck('halaqa')
            ->filter()
            ->unique('id')
        : collect();
@endphp
<body class="min-h-screen overflow-x-hidden bg-[#f5f7f6] text-slate-900 antialiased">
    <div class="navigation-progress" aria-hidden="true"></div>
    <div
        x-data="{
            sidebarOpen: false,
            isDesktop: window.innerWidth >= 1024,
            sidebarCollapsed: document.documentElement.dataset.sidebarCollapsed === 'true',
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
        x-init="$watch('sidebarCollapsed', value => { document.documentElement.dataset.sidebarCollapsed = value ? 'true' : 'false'; try { localStorage.setItem('alquran-sidebar-collapsed', JSON.stringify(value)); } catch (_) {} })"
        @keydown.escape.window="sidebarOpen = false; userMenu = false; notificationMenu = false"
        @resize.window.debounce.100ms="isDesktop = window.innerWidth >= 1024; if (isDesktop) sidebarOpen = false"
        @alquran:notification.window="receiveNotification($event)"
        class="min-h-screen"
    >
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <aside
            x-cloak
            id="app-sidebar"
            :inert="!isDesktop && !sidebarOpen"
            :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            class="app-sidebar-shell fixed inset-y-0 right-0 z-50 flex flex-col overflow-hidden bg-[linear-gradient(165deg,#073b31_0%,#0b513e_52%,#08372f_100%)] text-white shadow-2xl shadow-emerald-950/25 transition-[transform,width] duration-300 ease-out"
        >
            <div class="pointer-events-none absolute -left-20 top-20 size-56 rounded-full bg-emerald-300/7 blur-3xl"></div>
            <div class="flex h-20 shrink-0 items-center gap-3 border-b border-white/10 px-5" :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <x-brand-logo size="xs" />
                    <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity.duration.200ms class="min-w-0 lg:block">
                        <span class="block max-w-48 text-sm font-extrabold leading-5">{{ config('app.name') }}</span>
                        <span class="block truncate pt-0.5 text-[10px] text-emerald-100/65">نظام الإدارة والمتابعة المؤسسية</span>
                    </span>
                </a>
                <button type="button" class="mr-auto rounded-xl p-2 text-emerald-100/70 hover:bg-white/10 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="إغلاق القائمة">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            @if($teachingProfile)
                <div class="mx-3 mt-3 rounded-2xl border border-white/10 bg-white/[.07] p-3" :class="sidebarCollapsed ? 'lg:p-2' : ''" title="{{ $teachingProfile->center?->name }} — {{ $assignedTeachingHalaqas->pluck('name')->join('، ') }}">
                    <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-300/15 text-emerald-100"><x-islamic-icon name="mosque" class="size-5" /></span>
                        <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity class="min-w-0">
                            <span class="block text-[10px] font-black tracking-wider text-emerald-200/70">أنت تعمل داخل</span>
                            <span class="mt-0.5 block truncate text-xs font-black text-white">{{ $teachingProfile->center?->name ?? config('app.name') }}</span>
                        </span>
                    </div>
                    <div x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity class="mt-3 space-y-1.5 border-t border-white/10 pt-3">
                        @forelse($assignedTeachingHalaqas as $workHalaqa)
                            <a href="{{ route('teacher.daily') }}" class="flex items-center gap-2 rounded-xl bg-emerald-950/25 px-2.5 py-2 text-[11px] font-bold text-emerald-50 transition hover:bg-white/10"><span class="size-1.5 shrink-0 rounded-full bg-emerald-300"></span><span class="truncate">{{ $workHalaqa->name }}</span></a>
                        @empty
                            <p class="text-[10px] leading-5 text-amber-200/80">بانتظار إسناد حلقة رسمية</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <nav class="relative flex-1 overflow-y-auto overflow-x-hidden px-3 pb-5" aria-label="التنقل الرئيسي">
                <p class="sidebar-section" x-show="!sidebarCollapsed || !isDesktop">نظرة عامة</p>
                <a class="sidebar-link group {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}" href="{{ route('dashboard') }}" title="لوحة المعلومات">
                    <span class="sidebar-icon"><x-nav-icon name="home" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>لوحة المعلومات</span>
                </a>

                @if($teachingProfile)
                    <p class="sidebar-section" x-show="!sidebarCollapsed || !isDesktop">مساحتي كمحفّظ</p>
                    <div class="mb-2 space-y-1 rounded-2xl border border-emerald-300/15 bg-emerald-950/20 p-1.5">
                        <a class="sidebar-link group {{ request()->routeIs('teacher.daily') ? 'sidebar-link-active' : '' }}" href="{{ route('teacher.daily') }}" title="التسجيل اليومي">
                            <span class="sidebar-icon"><x-nav-icon name="daily" /></span>
                            <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity class="min-w-0 flex-1 truncate">التسجيل اليومي</span>
                            <span x-show="!sidebarCollapsed || !isDesktop" class="rounded-full bg-emerald-300/15 px-2 py-0.5 text-[9px] font-black text-emerald-100">اليوم</span>
                        </a>
                        <a class="sidebar-link group {{ request()->routeIs('teacher.mobile.app*') ? 'sidebar-link-active' : '' }}" href="{{ route('teacher.mobile.app') }}" title="تطبيق المحفّظ">
                            <span class="sidebar-icon"><x-nav-icon name="mobile" /></span>
                            <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity class="min-w-0 flex-1 truncate">تطبيق المحفّظ</span>
                        </a>
                        @can('alerts.view')
                            <a class="sidebar-link group {{ request()->routeIs('alerts.*') && request('scope') === 'teaching' ? 'sidebar-link-active' : '' }}" href="{{ route('alerts.index', ['scope' => 'teaching']) }}" title="تنبيهات طلاب حلقاتي">
                                <span class="sidebar-icon"><x-nav-icon name="alerts" /></span>
                                <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>تنبيهات طلاب حلقاتي</span>
                            </a>
                        @endcan
                    </div>
                @endif

                <p class="sidebar-section" x-show="!sidebarCollapsed || !isDesktop">الإدارة والمتابعة</p>
                @can('organization.view')
                    <a class="sidebar-link group {{ request()->routeIs('organization.*') ? 'sidebar-link-active' : '' }}" href="{{ route('organization.index') }}" title="الهيكل التنظيمي">
                        <span class="sidebar-icon"><x-nav-icon name="organization" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>الهيكل التنظيمي</span>
                    </a>
                @endcan
                @if(auth()->user()->hasRole('super-admin'))
                    <a class="sidebar-link group {{ request()->routeIs('access.*') ? 'sidebar-link-active' : '' }}" href="{{ route('access.index') }}" title="الحسابات والصلاحيات">
                        <span class="sidebar-icon"><x-nav-icon name="access" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>الحسابات والصلاحيات</span>
                    </a>
                    <a class="sidebar-link group {{ request()->routeIs('mobile.distribution') ? 'sidebar-link-active' : '' }}" href="{{ route('mobile.distribution') }}" title="توزيع تطبيق المحفّظ">
                        <span class="sidebar-icon"><x-nav-icon name="mobile" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>توزيع التطبيق</span>
                    </a>
                @endif
                @can('students.view')
                    <a class="sidebar-link group {{ request()->routeIs('students.*') ? 'sidebar-link-active' : '' }}" href="{{ route('students.index') }}" title="الطلاب">
                        <span class="sidebar-icon"><x-nav-icon name="students" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>الطلاب</span>
                    </a>
                @endcan
                @can('courses.manage')
                    <a class="sidebar-link group {{ request()->routeIs('academic.*') ? 'sidebar-link-active' : '' }}" href="{{ route('academic.index') }}" title="الإدارة الأكاديمية">
                        <span class="sidebar-icon"><x-nav-icon name="academic" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>الإدارة الأكاديمية</span>
                    </a>
                @endcan
                @can('alerts.view')
                    @if(! $teachingProfile || auth()->user()->can('alerts.manage'))
                        <a class="sidebar-link group {{ request()->routeIs('alerts.*') && request('scope') !== 'teaching' ? 'sidebar-link-active' : '' }}" href="{{ route('alerts.index') }}" title="مركز التنبيهات">
                            <span class="sidebar-icon"><x-nav-icon name="alerts" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>مركز التنبيهات</span>
                        </a>
                    @endif
                @endcan

                <p class="sidebar-section" x-show="!sidebarCollapsed || !isDesktop">التخطيط والتقارير</p>
                @can('calendar.view')
                    <a class="sidebar-link group {{ request()->routeIs('calendar.*') ? 'sidebar-link-active' : '' }}" href="{{ route('calendar.index') }}" title="التقويم">
                        <span class="sidebar-icon"><x-nav-icon name="calendar" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>التقويم</span>
                    </a>
                @endcan
                @can('reports.view')
                    <a class="sidebar-link group {{ request()->routeIs('reports.*') ? 'sidebar-link-active' : '' }}" href="{{ route('reports.index') }}" title="مركز التقارير">
                        <span class="sidebar-icon"><x-nav-icon name="reports" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>مركز التقارير</span>
                    </a>
                @endcan
                @can('website.manage')
                    <a class="sidebar-link group {{ request()->routeIs('cms.*') ? 'sidebar-link-active' : '' }}" href="{{ route('cms.index') }}" title="إدارة الموقع">
                        <span class="sidebar-icon"><x-nav-icon name="organization" /></span><span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity>إدارة الموقع</span>
                    </a>
                @endcan
            </nav>

            <div class="relative border-t border-white/10 p-3">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-white/10" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
                    <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-emerald-200/15 text-sm font-black text-emerald-50">
                        @if(auth()->user()->avatar)
                            <img src="{{ route('private-files.preview', auth()->user()->avatar) }}" alt="" class="size-full object-cover">
                        @else
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        @endif
                    </span>
                    <span x-show="!sidebarCollapsed || !isDesktop" x-transition.opacity class="min-w-0">
                        <span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-[11px] text-emerald-100/60">{{ $teachingProfile && auth()->user()->hasRole('super-admin') ? 'مدير النظام · محفّظ' : ($teachingProfile && auth()->user()->hasRole('center-manager') ? 'مدير مركز · محفّظ' : (auth()->user()->getRoleNames()->first() ?? 'مستخدم')) }}</span>
                    </span>
                </a>
            </div>
        </aside>

        <div class="app-content-shell min-h-screen transition-[padding] duration-300 ease-out">
            <header class="app-topbar-shell sticky top-0 z-30 h-20 border-b border-slate-200/75 bg-white/88 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <div class="mx-auto flex h-full max-w-[1600px] items-center gap-3">
                    <button type="button" class="btn-ghost" @click="toggleSidebar()" aria-controls="app-sidebar" :aria-expanded="isDesktop ? !sidebarCollapsed : sidebarOpen" :aria-label="isDesktop ? (sidebarCollapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية') : (sidebarOpen ? 'إغلاق القائمة' : 'فتح القائمة')">
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
                                <span class="grid size-9 place-items-center overflow-hidden rounded-xl bg-emerald-100 text-sm font-black text-emerald-800">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ route('private-files.preview', auth()->user()->avatar) }}" alt="" class="size-full object-cover">
                                    @else
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    @endif
                                </span>
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
                <x-flash-messages />
                <div x-cloak x-show="realtimeNotification" x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-x-6 opacity-0" x-transition:leave="transition duration-250 ease-in" x-transition:leave-end="translate-x-5 opacity-0" class="feedback-alert fixed left-4 top-24 z-[84] w-[min(25rem,calc(100vw-2rem))] overflow-hidden rounded-3xl border border-sky-200/80 bg-white shadow-[0_24px_70px_-35px_rgba(15,23,42,.35)]" role="status">
                    <span class="absolute inset-y-0 right-0 w-1.5 bg-sky-500"></span>
                    <div class="flex items-start gap-3.5 p-4 pr-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-sky-50 text-sky-700"><x-nav-icon name="bell" /></span>
                        <div class="min-w-0 flex-1 pt-0.5"><p class="text-sm font-black text-slate-900" x-text="realtimeNotification?.title ?? 'إشعار جديد'"></p><p class="mt-1 text-xs font-medium leading-6 text-slate-600" x-text="realtimeNotification?.message"></p><a x-show="realtimeNotification?.url" :href="realtimeNotification?.url" class="mt-2 inline-flex text-xs font-black text-sky-700">فتح التفاصيل ←</a></div>
                        <button type="button" @click="realtimeNotification = null" class="grid size-8 shrink-0 place-items-center rounded-xl text-slate-400 hover:bg-slate-100" aria-label="إغلاق التنبيه">×</button>
                    </div>
                </div>
                <div data-page-reveal>{{ $slot }}</div>
            </main>
        </div>
        <x-confirm-dialog />
    </div>
    @stack('scripts-before-livewire')
    @livewireScripts
    @stack('scripts')
</body>
</html>
