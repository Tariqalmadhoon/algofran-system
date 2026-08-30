<div class="space-y-8">
    @php
        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    @endphp

    <x-flash-messages inline consume />

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $centers->count() }}</p><p class="text-sm text-slate-500">مركز</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $halaqas->count() }}</p><p class="text-sm text-slate-500">حلقة</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $teachers->count() }}</p><p class="text-sm text-slate-500">محفظ</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $studentsCount }}</p><p class="text-sm text-slate-500">طالب</p></div></article>
    </section>

    @if (auth()->user()->can('organization.manage') || auth()->user()->can('users.manage') || auth()->user()->can('halaqas.manage'))
        <section class="panel">
            <div class="border-b border-slate-100 pb-6">
                <div><p class="eyebrow">مساحة الإضافة والإسناد</p><h2 class="section-title">اختر العملية المطلوبة</h2><p class="mt-2 text-sm text-slate-500">العمليات مرتبة حسب المؤسسة، الفريق، ثم تشغيل الحلقات.</p></div>
                <div class="mt-5 grid gap-3 lg:grid-cols-3">
                    @can('organization.manage')
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3"><p class="mb-2 text-[10px] font-black tracking-wider text-slate-400">1 · المؤسسة</p><div class="flex flex-wrap gap-2">@if(auth()->user()->hasRole('super-admin'))<button wire:click="$set('activeForm', 'center')" class="{{ $activeForm === 'center' ? 'btn-primary' : 'btn-secondary' }} !px-4 !py-2" type="button">مركز جديد</button>@endif @can('halaqas.manage')<button wire:click="$set('activeForm', 'halaqa')" class="{{ $activeForm === 'halaqa' ? 'btn-primary' : 'btn-secondary' }} !px-4 !py-2" type="button">حلقة جديدة</button>@endcan</div></div>
                    @endcan
                    @if(auth()->user()->hasRole('super-admin'))
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3"><p class="mb-2 text-[10px] font-black tracking-wider text-slate-400">2 · الفريق والصلاحيات</p><div class="flex flex-wrap gap-2"><a href="{{ route('access.index') }}" class="btn-secondary !px-4 !py-2">إدارة الحسابات</a><button wire:click="$set('activeForm', 'manager-teacher')" class="{{ $activeForm === 'manager-teacher' ? 'btn-primary' : 'btn-secondary' }} !px-4 !py-2" type="button">مدير + محفّظ</button></div></div>
                    @endif
                    @can('halaqas.manage')
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3"><p class="mb-2 text-[10px] font-black tracking-wider text-slate-400">3 · تشغيل الحلقة</p><div class="flex flex-wrap gap-2"><button wire:click="$set('activeForm', 'schedule')" class="{{ $activeForm === 'schedule' ? 'btn-primary' : 'btn-secondary' }} !px-4 !py-2" type="button">إضافة موعد</button><button wire:click="$set('activeForm', 'assignment')" class="{{ $activeForm === 'assignment' ? 'btn-primary' : 'btn-secondary' }} !px-4 !py-2" type="button">إسناد محفّظ</button></div></div>
                    @endcan
                </div>
            </div>

            @if ($activeForm === 'center' && auth()->user()->hasRole('super-admin'))
                <form wire:submit="saveCenter" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">اسم المركز</label><input wire:model="centerName" class="form-input" required><x-input-error :messages="$errors->get('centerName')" /></div>
                    <div class="rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/70 px-4 py-3">
                        <span class="form-label">رمز المركز</span>
                        <div class="flex items-center justify-between gap-3"><span class="text-sm font-bold text-emerald-900">يُسند تلقائيًا عند الحفظ</span><code class="rounded-lg bg-white px-2.5 py-1 text-xs font-black text-emerald-700" dir="ltr">CTR-###</code></div>
                    </div>
                    <div><label class="form-label">الهاتف</label><input wire:model="centerPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('centerPhone')" /></div>
                    <div><label class="form-label">البريد الإلكتروني</label><input wire:model="centerEmail" class="form-input" type="email" dir="ltr"><x-input-error :messages="$errors->get('centerEmail')" /></div>
                    <div class="md:col-span-2"><label class="form-label">العنوان</label><input wire:model="centerAddress" class="form-input"><x-input-error :messages="$errors->get('centerAddress')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ المركز</button></div>
                </form>
            @elseif ($activeForm === 'manager-teacher' && auth()->user()->hasRole('super-admin'))
                @php
                    $selectedManager = $eligibleTeacherManagers->firstWhere('id', (int) $existingTeacherUserId);
                    $managerHalaqas = $selectedManager
                        ? $halaqas->where('center_id', $selectedManager->staffProfile->center_id)->where('active', true)
                        : collect();
                @endphp
                <form wire:submit="saveManagerAsTeacher" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2"><label class="form-label">المدير المراد تفعيله كمحفّظ</label><select wire:model.live="existingTeacherUserId" class="form-input" required><option value="">اختر المدير</option>@foreach($eligibleTeacherManagers as $manager)<option value="{{ $manager->id }}">{{ $manager->name }} — {{ $manager->staffProfile->center->name }}{{ $manager->teacherProfile?->active ? ' · مفعّل حاليًا' : '' }}</option>@endforeach</select><x-input-error :messages="$errors->get('existingTeacherUserId')" /></div>
                    <div><label class="form-label">تخصص التحفيظ</label><input wire:model="specialization" class="form-input" placeholder="حفظ وتجويد"><x-input-error :messages="$errors->get('specialization')" /></div>
                    <div><label class="form-label">تاريخ بدء التحفيظ</label><input wire:model="hiredAt" class="form-input" type="date"><x-input-error :messages="$errors->get('hiredAt')" /></div>
                    <div class="md:col-span-2"><label class="form-label">الحلقة الرسمية</label><select wire:model="managerTeacherHalaqaId" class="form-input" required @disabled(!$selectedManager)><option value="">{{ $selectedManager ? 'اختر حلقة المدير' : 'اختر المدير أولًا' }}</option>@foreach($managerHalaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }} · {{ $halaqa->current_students_count }} طالب</option>@endforeach</select><x-input-error :messages="$errors->get('managerTeacherHalaqaId')" />@if($selectedManager && $managerHalaqas->isEmpty())<p class="mt-2 text-xs font-bold text-amber-700">لا توجد حلقة فعّالة في مركز هذا المدير؛ أنشئ الحلقة أولًا.</p>@endif</div>
                    <div><label class="form-label">صفة الإسناد</label><select wire:model="managerTeacherRole" class="form-input"><option value="primary">محفّظ أساسي</option><option value="assistant">محفّظ مساعد</option></select><x-input-error :messages="$errors->get('managerTeacherRole')" /></div>
                    <div><label class="form-label">بدء الإسناد</label><input wire:model="managerTeacherStartsAt" max="{{ today()->toDateString() }}" class="form-input" type="date" required><x-input-error :messages="$errors->get('managerTeacherStartsAt')" /></div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 md:col-span-2 xl:col-span-3"><p class="text-sm font-black text-emerald-900">تفعيل وإسناد في خطوة واحدة</p><p class="mt-1 text-xs leading-6 text-emerald-700">سيحتفظ المستخدم بصلاحيات الإدارة، ويُضاف له دور المحفّظ وملفه التعليمي ثم يُسند للحلقة فورًا. بعد الحفظ ستظهر له مساحة اليومي وطلاب الحلقة دون خطوة أخرى.</p></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" wire:target="saveManagerAsTeacher" class="btn-primary w-full" type="submit"><span wire:loading.remove wire:target="saveManagerAsTeacher">تفعيل وإسناد الحلقة الآن</span><span wire:loading wire:target="saveManagerAsTeacher">جارٍ التفعيل والإسناد…</span></button></div>
                </form>
            @elseif ($activeForm === 'halaqa' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveHalaqa" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">المركز</label><select wire:model="halaqaCenterId" class="form-input" required><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaCenterId')" /></div>
                    <div><label class="form-label">اسم الحلقة</label><input wire:model="halaqaName" class="form-input" required><x-input-error :messages="$errors->get('halaqaName')" /></div>
                    <div class="rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/70 px-4 py-3">
                        <span class="form-label">رمز الحلقة</span>
                        <div class="flex items-center justify-between gap-3"><span class="text-sm font-bold text-emerald-900">متسلسل داخل المركز</span><code class="rounded-lg bg-white px-2.5 py-1 text-xs font-black text-emerald-700" dir="ltr">HLQ-###</code></div>
                    </div>
                    <div><label class="form-label">السعة</label><input wire:model="halaqaCapacity" class="form-input" type="number" min="1" max="500"><x-input-error :messages="$errors->get('halaqaCapacity')" /></div>
                    <div><label class="form-label">تاريخ البداية</label><input wire:model="halaqaStartDate" class="form-input" type="date"><x-input-error :messages="$errors->get('halaqaStartDate')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ الحلقة</button></div>
                </form>
            @elseif ($activeForm === 'schedule' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveSchedule" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">الحلقة</label><select wire:model="scheduleHalaqaId" class="form-input" required><option value="">اختر الحلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('scheduleHalaqaId')" /></div>
                    <div><label class="form-label">اليوم</label><select wire:model="weekday" class="form-input">@foreach($days as $key => $day)<option value="{{ $key }}">{{ $day }}</option>@endforeach</select></div>
                    <div><label class="form-label">من</label><input wire:model="scheduleStartsAt" class="form-input" type="time" required><x-input-error :messages="$errors->get('scheduleStartsAt')" /></div>
                    <div><label class="form-label">إلى</label><input wire:model="scheduleEndsAt" class="form-input" type="time" required><x-input-error :messages="$errors->get('scheduleEndsAt')" /></div>
                    <div><label class="form-label">الغرفة</label><input wire:model="scheduleRoom" class="form-input"><x-input-error :messages="$errors->get('scheduleRoom')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">إضافة الموعد</button></div>
                </form>
            @elseif ($activeForm === 'assignment' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveAssignment" class="mt-6 grid gap-4 rounded-3xl border border-emerald-100 bg-gradient-to-l from-emerald-50/70 to-white p-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2 xl:col-span-4"><p class="text-sm font-black text-emerald-950">إسناد وظيفي مباشر</p><p class="mt-1 text-xs leading-6 text-slate-500">عند الإسناد يُفعّل دور المحفّظ تلقائيًا، ويظهر له طلاب الحلقة والتسجيل اليومي. الإسناد المكرر لن ينشئ سجلًا جديدًا.</p></div>
                    <div><label class="form-label">الحلقة</label><select wire:model="assignmentHalaqaId" class="form-input" required><option value="">اختر الحلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('assignmentHalaqaId')" /></div>
                    <div><label class="form-label">المحفظ</label><select wire:model="assignmentTeacherId" class="form-input" required><option value="">اختر المحفظ</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('assignmentTeacherId')" /></div>
                    <div><label class="form-label">نوع الإسناد</label><select wire:model="assignmentRole" class="form-input"><option value="primary">أساسي</option><option value="assistant">مساعد</option></select></div>
                    <div><label class="form-label">من تاريخ</label><input wire:model="assignmentStartsAt" class="form-input" type="date" required><x-input-error :messages="$errors->get('assignmentStartsAt')" /></div>
                    <div class="xl:col-span-4"><button wire:loading.attr="disabled" wire:target="saveAssignment" class="btn-primary" type="submit"><span wire:loading.remove wire:target="saveAssignment">تأكيد إسناد المحفّظ</span><span wire:loading wire:target="saveAssignment">جارٍ التحقق والإسناد…</span></button></div>
                </form>
            @endif
        </section>
    @endif

    <section class="relative isolate overflow-hidden rounded-[2rem] border border-emerald-950/10 bg-white p-5 shadow-[0_20px_70px_-48px_rgba(6,78,59,.45)] sm:p-6">
        <div aria-hidden="true" class="absolute -left-24 -top-32 -z-10 size-72 rounded-full bg-emerald-100/60 blur-3xl"></div>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="eyebrow">الخريطة التنظيمية</p><h2 class="section-title">الهيكل الشجري للمراكز والحلقات</h2><p class="mt-2 text-sm text-slate-500">افتح كل مركز لرؤية حلقاته، المحفّظين، الطلاب والمواعيد بصورة مترابطة.</p></div>
            <div class="flex items-center gap-2 text-[11px] font-bold text-slate-400"><span class="size-2 rounded-full bg-emerald-500"></span> مركز <span class="mr-2 size-2 rounded-full bg-amber-400"></span> حلقة</div>
        </div>

        <div class="space-y-5">
            @forelse($centers as $center)
                @php $centerHalaqas = $halaqas->where('center_id', $center->id); @endphp
                <article x-data="{ open: true }" class="overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-slate-50/50" wire:key="tree-center-{{ $center->id }}">
                    <div class="flex items-center gap-3 bg-white p-4 sm:p-5">
                        <button type="button" @click="open = !open" class="grid size-11 shrink-0 place-items-center rounded-2xl bg-emerald-950 text-emerald-200 shadow-lg shadow-emerald-950/15" :aria-expanded="open"><x-islamic-icon name="mosque" class="size-5" /></button>
                        <button type="button" @click="open = !open" class="min-w-0 flex-1 text-right"><span class="flex flex-wrap items-center gap-2"><strong class="text-base font-black text-emerald-950">{{ $center->name }}</strong><code class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-500" dir="ltr">{{ $center->code }}</code><span class="{{ $center->active ? 'badge-active' : 'badge-inactive' }}">{{ $center->active ? 'نشط' : 'متوقف' }}</span></span><span class="mt-1 block text-xs text-slate-400">{{ $centerHalaqas->count() }} حلقة · {{ $centerHalaqas->sum('current_students_count') }} طالب</span></button>
                        @can('organization.manage')<button wire:click="toggleActive('center', {{ $center->id }})" class="hidden rounded-xl px-3 py-2 text-xs font-black text-slate-500 hover:bg-slate-100 sm:inline-flex" type="button">{{ $center->active ? 'إيقاف المركز' : 'تفعيل المركز' }}</button>@endcan
                        <svg class="size-5 text-slate-400 transition-transform duration-300" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </div>

                    <div x-show="open" x-collapse class="border-t border-slate-200/70 px-4 py-5 sm:px-6">
                        <div class="relative space-y-4 pr-8 before:absolute before:inset-y-3 before:right-3 before:w-px before:bg-gradient-to-b before:from-emerald-400 before:via-emerald-200 before:to-transparent">
                            @forelse($centerHalaqas as $halaqa)
                                @php $assistantTeachers = $halaqa->teacherAssignments->where('role', 'assistant'); @endphp
                                <div class="relative before:absolute before:right-[-1.25rem] before:top-8 before:h-px before:w-5 before:bg-emerald-300" wire:key="tree-halaqa-{{ $halaqa->id }}">
                                    <span class="absolute right-[-1.55rem] top-[1.55rem] z-10 size-2.5 rounded-full border-2 border-white bg-amber-400 ring-2 ring-amber-100"></span>
                                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 transition duration-300 hover:border-emerald-200 hover:shadow-[0_18px_45px_-35px_rgba(6,78,59,.45)]">
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2"><span class="grid size-9 place-items-center rounded-xl bg-amber-50 text-amber-700"><x-islamic-icon name="quran" class="size-4" /></span><h3 class="font-black text-slate-900">{{ $halaqa->name }}</h3><code class="rounded-lg bg-slate-50 px-2 py-1 text-[10px] font-black text-slate-400" dir="ltr">{{ $halaqa->code }}</code><span class="{{ $halaqa->active ? 'badge-active' : 'badge-inactive' }}">{{ $halaqa->active ? 'نشطة' : 'متوقفة' }}</span></div>
                                                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                    <div class="rounded-xl bg-emerald-50/70 px-3 py-2.5"><span class="block text-[10px] font-bold text-emerald-700/60">المحفّظ الأساسي</span><strong class="mt-1 block truncate text-xs text-emerald-950">{{ $halaqa->primaryTeacher?->user?->name ?? 'لم يُسند بعد' }}</strong></div>
                                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5"><span class="block text-[10px] font-bold text-slate-400">الطلاب</span><strong class="mt-1 block text-xs text-slate-700">{{ $halaqa->current_students_count }} / {{ $halaqa->capacity }}</strong></div>
                                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5"><span class="block text-[10px] font-bold text-slate-400">المحفّظون المساعدون</span><strong class="mt-1 block truncate text-xs text-slate-700">{{ $assistantTeachers->pluck('teacher.user.name')->filter()->join('، ') ?: 'لا يوجد' }}</strong></div>
                                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5"><span class="block text-[10px] font-bold text-slate-400">تاريخ البداية</span><strong class="mt-1 block text-xs text-slate-700">{{ $halaqa->start_date?->format('Y-m-d') ?? 'غير محدد' }}</strong></div>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-2">@forelse($halaqa->schedules as $schedule)<span class="inline-flex items-center gap-1.5 rounded-xl border border-sky-100 bg-sky-50 px-2.5 py-1.5 text-[10px] font-black text-sky-700"><span class="size-1.5 rounded-full bg-sky-400"></span>{{ $days[$schedule->weekday] }} · {{ substr($schedule->starts_at, 0, 5) }}—{{ substr($schedule->ends_at, 0, 5) }}</span>@empty<span class="text-[11px] font-bold text-slate-400">لم تُضف مواعيد للحلقة بعد</span>@endforelse</div>
                                            </div>
                                            @can('organization.manage')<button wire:click="toggleActive('halaqa', {{ $halaqa->id }})" class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:border-emerald-200 hover:bg-emerald-50" type="button">{{ $halaqa->active ? 'إيقاف الحلقة' : 'تفعيل الحلقة' }}</button>@endcan
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center text-sm font-bold text-slate-400">لا توجد حلقات داخل هذا المركز بعد.</div>
                            @endforelse
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state"><p class="font-black">لا توجد مراكز في الهيكل حتى الآن.</p></div>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="mb-5"><p class="eyebrow">فريق العمل</p><h2 class="section-title">المحفظون</h2></div>
            <div class="space-y-3">
                @forelse($teachers as $teacher)
                    <article class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 p-4"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="font-bold">{{ $teacher->user->name }}</p>@if($teacher->user->hasAnyRole(['center-manager', 'super-admin']))<span class="rounded-full bg-violet-50 px-2 py-1 text-[9px] font-black text-violet-700">مدير + محفّظ</span>@endif</div><p class="mt-1 text-xs text-slate-500">{{ $teacher->center->name }} · {{ $teacher->active_assignments_count }} إسناد فعّال</p></div><span class="shrink-0 text-xs font-semibold text-slate-500" dir="ltr">{{ $teacher->employee_number }}</span></article>
                @empty <p class="py-6 text-center text-sm text-slate-400">لا يوجد محفظون بعد.</p> @endforelse
            </div>
        </section>
        <section class="panel">
            <div class="mb-5"><p class="eyebrow">فريق العمل</p><h2 class="section-title">الموظفون</h2></div>
            <div class="space-y-3">
                @forelse($staff as $member)
                    <article class="flex items-center justify-between rounded-2xl border border-slate-100 p-4"><div><p class="font-bold">{{ $member->user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $member->job_title }} — {{ $member->center->name }}</p></div><span class="text-xs font-semibold text-slate-500" dir="ltr">{{ $member->employee_number }}</span></article>
                @empty <p class="py-6 text-center text-sm text-slate-400">لا يوجد موظفون بعد.</p> @endforelse
            </div>
        </section>
    </div>
</div>
