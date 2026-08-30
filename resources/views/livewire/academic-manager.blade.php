<div x-data="{ tab: 'enrollments' }" @academic:open-enrollments.window="tab = 'enrollments'" class="space-y-6">
    <section class="relative overflow-hidden rounded-[2rem] bg-[linear-gradient(125deg,#052e2b_0%,#047857_58%,#b88932_145%)] p-6 text-white shadow-[0_28px_80px_-45px_rgba(6,78,59,.8)] sm:p-8" data-motion="reveal">
        <div class="absolute -left-20 -top-24 size-64 rounded-full border border-white/10"></div>
        <div class="absolute -bottom-24 right-1/3 size-56 rounded-full bg-amber-300/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-black text-emerald-50 backdrop-blur"><span class="size-2 rounded-full bg-amber-300"></span>السجل الأكاديمي</span>
                <h1 class="mt-4 text-3xl font-black sm:text-4xl">الدورات والإنجازات</h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-emerald-50/75 sm:text-base">سجّل طلاب المركز في الدورات دفعة واحدة، وثّق النتائج والإنجازات، ثم نزّل سجلًا جاهزًا ومنظمًا بصيغة Excel.</p>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $courses->count() }}</strong><span class="text-[11px] text-emerald-100/75">دورة</span></div>
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $courses->sum('enrollments_count') }}</strong><span class="text-[11px] text-emerald-100/75">مشاركة</span></div>
                <div class="min-w-24 rounded-2xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm"><strong class="block text-2xl font-black">{{ $courses->sum('completed_enrollments_count') }}</strong><span class="text-[11px] text-emerald-100/75">إنجاز</span></div>
            </div>
        </div>
    </section>

    <x-flash-messages inline consume />

    <nav class="panel flex flex-col gap-2 p-2 sm:flex-row" data-motion="reveal">
        @can('courses.manage')
            <button @click="tab = 'enrollments'" :class="tab === 'enrollments' ? 'bg-emerald-950 text-white shadow-lg' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-900'" class="flex flex-1 items-center gap-3 rounded-2xl px-4 py-3 text-right transition" type="button"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100/15"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5M7 19v2m10-2v2"/></svg></span><span><strong class="block text-sm font-black">التسجيل والنتائج</strong><small class="text-[10px] opacity-65">اختيار الطلاب وتوثيق الإنجاز</small></span></button>
            <button @click="tab = 'courses'" :class="tab === 'courses' ? 'bg-emerald-950 text-white shadow-lg' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-900'" class="flex flex-1 items-center gap-3 rounded-2xl px-4 py-3 text-right transition" type="button"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100/15"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M9 4v16M12 8h4M12 12h4"/></svg></span><span><strong class="block text-sm font-black">تعريف الدورات</strong><small class="text-[10px] opacity-65">إنشاء الدورة وإدارة سجلها</small></span></button>
        @endcan
        @can('certificates.manage')<button @click="tab = 'certificates'" :class="tab === 'certificates' ? 'bg-emerald-950 text-white shadow-lg' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-900'" class="flex flex-1 items-center gap-3 rounded-2xl px-4 py-3 text-right transition" type="button"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100/15"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h10v18l-5-3-5 3Z"/><path d="m9 9 2 2 4-4"/></svg></span><span><strong class="block text-sm font-black">الشهادات</strong><small class="text-[10px] opacity-65">إصدار وحفظ الشهادات</small></span></button>@endcan
        @can('achievements.manage')<button @click="tab = 'achievements'" :class="tab === 'achievements' ? 'bg-emerald-950 text-white shadow-lg' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-900'" class="flex flex-1 items-center gap-3 rounded-2xl px-4 py-3 text-right transition" type="button"><span class="grid size-9 place-items-center rounded-xl bg-emerald-100/15"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/></svg></span><span><strong class="block text-sm font-black">الإنجازات</strong><small class="text-[10px] opacity-65">الإنجازات اليدوية والتلقائية</small></span></button>@endcan
    </nav>

    @can('courses.manage')
        <section x-show="tab === 'enrollments'" class="space-y-6">
            <div class="grid gap-3 sm:grid-cols-3" data-reveal-group="up" data-reveal-stagger="55">
                @foreach([['1','اختر الدورة','تتحدد قائمة طلاب مركزها'],['2','حدّد الطلاب','اختر بعضهم أو الجميع'],['3','اعتمد النتائج','ثم نزّل ملف Excel']] as [$number,$title,$hint])
                    <div class="stat-card"><span class="grid size-11 place-items-center rounded-2xl bg-emerald-100 text-lg font-black text-emerald-700">{{ $number }}</span><span><strong class="block text-sm font-black text-slate-800">{{ $title }}</strong><small class="text-[11px] text-slate-400">{{ $hint }}</small></span></div>
                @endforeach
            </div>

            <section class="panel overflow-hidden p-0" data-motion="reveal">
                <div class="border-b border-slate-100 bg-gradient-to-l from-emerald-50/70 to-white p-5 sm:p-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                        <div class="flex-1"><p class="eyebrow">الخطوة الأولى</p><h2 class="section-title">اختيار الدورة وتسجيل الطلاب</h2><p class="mt-1 text-sm text-slate-500">ستظهر تلقائيًا جميع ملفات الطلاب النشطين داخل مركز الدورة.</p></div>
                        <label class="w-full xl:w-80"><span class="form-label">الدورة</span><select wire:model.live="bulkCourseId" class="form-input"><option value="">اختر الدورة</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }} · {{ $course->center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('bulkCourseId')" /></label>
                        <label class="w-full xl:w-44"><span class="form-label">تاريخ التسجيل</span><input wire:model="bulkEnrollmentDate" type="date" class="form-input"><x-input-error :messages="$errors->get('bulkEnrollmentDate')" /></label>
                    </div>
                </div>

                @if($bulkCourse)
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                            <label class="flex-1"><span class="form-label">البحث عن طالب</span><span class="relative block"><svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input wire:model.live.debounce.350ms="studentSearch" class="form-input pr-10" type="search" placeholder="الاسم، رقم الطالب أو رقم الهوية..."></span></label>
                            <div class="flex flex-wrap gap-2"><button type="button" wire:click="selectAllStudents" class="btn-secondary text-xs">تحديد كل غير المسجلين</button><button type="button" wire:click="clearSelectedStudents" class="btn-ghost text-xs font-black text-slate-600">إلغاء التحديد</button></div>
                            <div class="flex h-[46px] items-center gap-2 rounded-xl bg-emerald-50 px-4 text-xs font-black text-emerald-800"><span>{{ $studentRoster->count() }} طالب</span><span class="text-emerald-300">•</span><span>{{ count($selectedStudentIds) }} محدد</span></div>
                        </div>

                        <x-input-error :messages="$errors->get('selectedStudentIds')" class="mt-3" />
                        <div class="mt-5 max-h-[520px] overflow-auto rounded-2xl border border-slate-200">
                            <table class="data-table min-w-[820px]">
                                <thead class="sticky top-0 z-10"><tr><th class="w-12">تحديد</th><th>الطالب</th><th>رقم الهوية</th><th>الحلقة</th><th>المحفّظ</th><th>حالة الدورة</th></tr></thead>
                                <tbody>
                                    @forelse($studentRoster as $student)
                                        @php($alreadyRegistered = in_array($student->id, $bulkRegisteredStudentIds, true))
                                        <tr wire:key="course-student-{{ $student->id }}" class="{{ in_array($student->id, array_map('intval', $selectedStudentIds), true) ? 'bg-emerald-50/60' : '' }}">
                                            <td><input type="checkbox" wire:model.live="selectedStudentIds" value="{{ $student->id }}" @disabled($alreadyRegistered) class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500 disabled:opacity-35"></td>
                                            <td><span class="flex items-center gap-3"><x-student-avatar :student="$student" size="xs" /><span><strong class="block text-sm text-slate-900">{{ $student->full_name }}</strong><small class="text-[10px] text-slate-400">{{ $student->student_number }}</small></span></span></td>
                                            <td dir="ltr" class="text-right font-mono text-xs">{{ $student->identity_number ?: '—' }}</td>
                                            <td>{{ $student->currentHalaqa?->name ?: 'دون حلقة' }}</td>
                                            <td>{{ $student->currentHalaqa?->primaryTeacher?->user?->name ?: 'غير محدد' }}</td>
                                            <td>@if($alreadyRegistered)<span class="badge-active">مسجل</span>@else<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-500">متاح للتسجيل</span>@endif</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6"><div class="empty-state m-4"><div><p class="font-black text-slate-700">لا يوجد طلاب مطابقون</p><p class="mt-1 text-sm">تحقق من مركز الدورة أو عبارة البحث.</p></div></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-5 flex flex-col items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4 sm:flex-row"><p class="text-xs leading-6 text-slate-500">لن تتكرر تسجيلات الطلاب الموجودين مسبقًا، وستبقى نتائجهم محفوظة.</p><button type="button" wire:click="registerSelectedStudents" wire:loading.attr="disabled" class="btn-primary w-full sm:w-auto"><span wire:loading.remove wire:target="registerSelectedStudents">تسجيل الطلاب المحددين</span><span wire:loading wire:target="registerSelectedStudents">جاري التسجيل...</span></button></div>
                    </div>
                @else
                    <div class="empty-state m-5 sm:m-6"><div><span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6"/></svg></span><p class="mt-4 font-black text-slate-700">ابدأ باختيار الدورة</p><p class="mt-1 text-sm">بعد اختيارها ستظهر قائمة طلاب مركزها هنا مباشرة.</p></div></div>
                @endif
            </section>

            <section class="panel overflow-hidden p-0" data-motion="reveal">
                <div class="border-b border-slate-100 bg-gradient-to-l from-amber-50/60 to-white p-5 sm:p-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                        <div class="flex-1"><p class="eyebrow">الخطوة الثالثة</p><h2 class="section-title">النتائج وتوثيق الإنجاز</h2><p class="mt-1 text-sm text-slate-500">أدخل درجة كل طالب؛ سيُقترح التقييم تلقائيًا ويمكن تعديله.</p></div>
                        <label class="w-full xl:w-80"><span class="form-label">دورة النتائج</span><select wire:model.live="resultsCourseId" class="form-input"><option value="">اختر الدورة</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('resultsCourseId')" /></label>
                        <button type="button" wire:click="exportCourseResults" wire:loading.attr="disabled" class="inline-flex h-[46px] items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-800 disabled:opacity-50"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>تنزيل Excel</button>
                    </div><x-input-error :messages="$errors->get('courseExport')" class="mt-3" />
                </div>

                @if($resultCourse && $resultEnrollments->isNotEmpty())
                    <form wire:submit="saveCourseResults">
                        <div class="overflow-auto">
                            <table class="data-table min-w-[1160px]">
                                <thead><tr><th class="w-64">الطالب</th><th class="w-36">الدرجة من 100</th><th class="w-44">التقييم</th><th class="w-44">تاريخ الإنجاز</th><th>الملاحظات</th><th class="w-28">الحالة</th></tr></thead>
                                <tbody>
                                    @foreach($resultEnrollments as $enrollment)
                                        <tr wire:key="course-result-{{ $enrollment->id }}">
                                            <td><span class="flex items-center gap-3"><x-student-avatar :student="$enrollment->student" size="xs" /><span><strong class="block text-sm text-slate-900">{{ $enrollment->student->full_name }}</strong><small class="text-[10px] text-slate-400">{{ $enrollment->student->identity_number ?: $enrollment->student->student_number }}</small></span></span></td>
                                            <td><input wire:model.live.debounce.400ms="courseResultRows.{{ $enrollment->id }}.result" type="number" min="0" max="100" step="0.01" class="form-input" placeholder="0 - 100"><x-input-error :messages="$errors->get('courseResultRows.'.$enrollment->id.'.result')" /></td>
                                            <td><select wire:model="courseResultRows.{{ $enrollment->id }}.grade" class="form-input"><option value="">اختر التقييم</option>@foreach($evaluationRatings as $rating)<option value="{{ $rating->label() }}">{{ $rating->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('courseResultRows.'.$enrollment->id.'.grade')" /></td>
                                            <td><input wire:model="courseResultRows.{{ $enrollment->id }}.completed_at" type="date" class="form-input"><x-input-error :messages="$errors->get('courseResultRows.'.$enrollment->id.'.completed_at')" /></td>
                                            <td><input wire:model="courseResultRows.{{ $enrollment->id }}.notes" class="form-input" placeholder="ملاحظات اختيارية"></td>
                                            <td>@if($enrollment->status->value === 'completed')<span class="badge-active">مكتمل</span>@else<span class="badge-warning">بانتظار النتيجة</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 p-5 sm:flex-row"><p class="text-xs leading-6 text-slate-500">عند الاعتماد ستُضاف الدورة تلقائيًا إلى إنجازات كل طالب وخطه الزمني.</p><button class="btn-primary w-full sm:w-auto" type="submit" wire:loading.attr="disabled"><span wire:loading.remove wire:target="saveCourseResults">اعتماد النتائج والإنجازات</span><span wire:loading wire:target="saveCourseResults">جاري الاعتماد...</span></button></div>
                    </form>
                @elseif($resultCourse)
                    <div class="empty-state m-5 sm:m-6"><div><p class="font-black text-slate-700">لم يسجل طلاب في هذه الدورة بعد</p><p class="mt-1 text-sm">استخدم قائمة الطلاب أعلاه ثم عد لاعتماد النتائج.</p></div></div>
                @else
                    <div class="empty-state m-5 sm:m-6"><div><p class="font-black text-slate-700">اختر دورة لعرض سجل نتائجها</p><p class="mt-1 text-sm">ستظهر حقول الدرجة والتقييم والتاريخ لكل طالب.</p></div></div>
                @endif
            </section>
        </section>

        <section x-show="tab === 'courses'" x-cloak class="space-y-6">
            <form wire:submit="saveCourse" class="panel space-y-5" data-motion="reveal">
                <div><p class="eyebrow">دورة جديدة</p><h2 class="section-title">تعريف الدورة</h2><p class="mt-1 text-sm text-slate-500">أدخل البيانات الأساسية، ثم انتقل إلى التسجيل لإضافة الطلاب.</p></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">المركز</span><select wire:model="courseCenterId" class="form-input"><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('courseCenterId')" /></label>
                    <label><span class="form-label">مدرب الدورة</span><select wire:model="courseInstructorId" class="form-input"><option value="">غير محدد</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select></label>
                    <label class="md:col-span-2"><span class="form-label">اسم الدورة</span><input wire:model="courseName" class="form-input" placeholder="مثال: دورة أحكام التجويد"><x-input-error :messages="$errors->get('courseName')" /></label>
                    <label><span class="form-label">تاريخ البداية</span><input wire:model="courseStartsAt" type="date" class="form-input"><x-input-error :messages="$errors->get('courseStartsAt')" /></label>
                    <label><span class="form-label">تاريخ النهاية</span><input wire:model="courseEndsAt" type="date" class="form-input"><x-input-error :messages="$errors->get('courseEndsAt')" /></label>
                    <label><span class="form-label">عدد الساعات</span><input wire:model="courseHours" type="number" min="0" step="0.5" class="form-input"></label>
                    <label><span class="form-label">حالة الدورة</span><select wire:model="courseStatus" class="form-input">@foreach($courseStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
                    <label class="md:col-span-2 xl:col-span-4"><span class="form-label">وصف الدورة</span><textarea wire:model="courseDescription" rows="3" class="form-input" placeholder="وصف مختصر لأهداف الدورة"></textarea></label>
                </div>
                <button class="btn-primary" type="submit">إنشاء الدورة والانتقال للتسجيل</button>
            </form>

            <section data-motion="reveal">
                <div class="mb-4"><p class="eyebrow">سجل الدورات</p><h2 class="section-title">الدورات المتاحة</h2></div>
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3" data-reveal-group="up" data-reveal-stagger="50">
                    @forelse($courses as $course)
                        <article class="panel panel-interactive flex h-full flex-col p-5"><div class="flex items-start justify-between gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6"/></svg></span><span class="{{ $course->status->value === 'active' ? 'badge-active' : ($course->status->value === 'planned' ? 'badge-warning' : 'badge-inactive') }}">{{ $course->status->label() }}</span></div><h3 class="mt-4 text-lg font-black text-emerald-950">{{ $course->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $course->center->name }} · {{ $course->instructor?->user?->name ?: 'دون مدرب محدد' }}</p><div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl bg-slate-50 p-2"><strong class="block text-base text-slate-800">{{ $course->enrollments_count }}</strong><small class="text-[9px] text-slate-400">مسجل</small></div><div class="rounded-xl bg-emerald-50 p-2"><strong class="block text-base text-emerald-800">{{ $course->completed_enrollments_count }}</strong><small class="text-[9px] text-emerald-600">مكتمل</small></div><div class="rounded-xl bg-amber-50 p-2"><strong class="block text-base text-amber-800">{{ number_format($course->hours, 1) }}</strong><small class="text-[9px] text-amber-600">ساعة</small></div></div><p class="mt-3 text-[10px] text-slate-400">{{ $course->starts_at->format('Y-m-d') }} ← {{ $course->ends_at?->format('Y-m-d') ?: 'مفتوحة' }}</p><button type="button" @click="tab = 'enrollments'; $wire.set('bulkCourseId', '{{ $course->id }}')" class="btn-secondary mt-auto w-full pt-3 text-xs">إدارة طلاب الدورة</button></article>
                    @empty<div class="empty-state lg:col-span-2 xl:col-span-3"><p class="font-black">لم تنشأ دورات بعد</p></div>@endforelse
                </div>
            </section>
        </section>
    @endcan

    @can('certificates.manage')
        <section x-show="tab === 'certificates'" x-cloak class="space-y-6">
            <form wire:submit="saveCertificate" class="panel space-y-5"><div><p class="eyebrow">توثيق الإنجاز</p><h2 class="section-title">إصدار شهادة</h2></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="form-label">الطالب</span><select wire:model="certificateStudentId" class="form-input"><option value="">اختر الطالب</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select><x-input-error :messages="$errors->get('certificateStudentId')" /></label>
                <label><span class="form-label">الدورة (اختياري)</span><select wire:model="certificateCourseId" class="form-input"><option value="">دون دورة</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach</select></label>
                <label><span class="form-label">اسم الشهادة</span><input wire:model="certificateName" class="form-input"><x-input-error :messages="$errors->get('certificateName')" /></label>
                <label><span class="form-label">الجهة المصدرة</span><input wire:model="certificateIssuer" class="form-input"><x-input-error :messages="$errors->get('certificateIssuer')" /></label>
                <label><span class="form-label">رقم الشهادة</span><input wire:model="certificateNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('certificateNumber')" /></label>
                <label><span class="form-label">تاريخ الإصدار</span><input wire:model="certificateIssuedAt" type="date" class="form-input"></label>
                <label><span class="form-label">تاريخ الانتهاء</span><input wire:model="certificateExpiresAt" type="date" class="form-input"></label>
                <label><span class="form-label">التقييم</span><input wire:model="certificateGrade" class="form-input"></label>
                <label class="md:col-span-2"><span class="form-label">ملف الشهادة الخاص</span><input wire:model="certificateFile" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('certificateFile')" /></label>
                <label class="md:col-span-2"><span class="form-label">ملاحظات</span><textarea wire:model="certificateNotes" rows="2" class="form-input"></textarea></label>
            </div><button class="btn-primary" type="submit">إصدار الشهادة</button></form>
            <section class="panel"><div class="mb-5"><h2 class="section-title">الشهادات الصادرة</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الشهادة</th><th>الجهة</th><th>التاريخ</th><th>الملف</th></tr></thead><tbody>@forelse($certificates as $certificate)<tr><td><span class="flex items-center gap-2"><x-student-avatar :student="$certificate->student" size="xs" />{{ $certificate->student->full_name }}</span></td><td class="font-bold">{{ $certificate->name }}</td><td>{{ $certificate->issuer }}</td><td>{{ $certificate->issued_at->format('Y-m-d') }}</td><td>@if($certificate->privateFile)<a class="font-bold text-emerald-700" href="{{ route('private-files.show', $certificate->privateFile) }}">تنزيل</a>@else—@endif</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-400">لا توجد شهادات بعد.</td></tr>@endforelse</tbody></table></div></section>
        </section>
    @endcan

    @can('achievements.manage')
        <section x-show="tab === 'achievements'" x-cloak class="space-y-6">
            <form wire:submit="saveAchievement" class="panel space-y-5"><div><p class="eyebrow">تكريم وتقدم</p><h2 class="section-title">تسجيل إنجاز يدوي</h2><p class="mt-1 text-sm text-slate-500">إنجازات إتمام الدورات تُضاف تلقائيًا عند اعتماد نتائجها، ويمكن هنا إضافة المسابقات والتكريمات الأخرى.</p></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="form-label">الطالب</span><select wire:model="achievementStudentId" class="form-input"><option value="">اختر الطالب</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select><x-input-error :messages="$errors->get('achievementStudentId')" /></label>
                <label><span class="form-label">النوع</span><select wire:model="achievementType" class="form-input">@foreach($achievementTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></label>
                <label><span class="form-label">العنوان</span><input wire:model="achievementTitle" class="form-input"><x-input-error :messages="$errors->get('achievementTitle')" /></label>
                <label><span class="form-label">التاريخ</span><input wire:model="achievementDate" type="date" class="form-input"></label>
                <label><span class="form-label">الجهة</span><input wire:model="achievementIssuer" class="form-input"></label>
                <label class="md:col-span-2 xl:col-span-3"><span class="form-label">الوصف</span><textarea wire:model="achievementDescription" rows="2" class="form-input"></textarea></label>
            </div><button class="btn-primary" type="submit">حفظ الإنجاز</button></form>
            <section class="panel"><div class="mb-5"><h2 class="section-title">سجل الإنجازات</h2></div><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">@forelse($achievements as $achievement)<article class="rounded-2xl border border-slate-100 p-4 transition hover:border-emerald-200"><div class="flex justify-between gap-2"><div class="flex items-center gap-3"><x-student-avatar :student="$achievement->student" size="sm" /><div><p class="font-black text-emerald-950">{{ $achievement->title }}</p><p class="mt-1 text-sm text-slate-500">{{ $achievement->student->full_name }}</p></div></div><span class="badge-active">{{ $achievement->type->label() }}</span></div><p class="mt-3 text-xs text-slate-400">{{ $achievement->achieved_at->format('Y-m-d') }} · {{ $achievement->issuer ?? '—' }}</p></article>@empty<p class="col-span-full py-8 text-center text-slate-400">لا توجد إنجازات بعد.</p>@endforelse</div></section>
        </section>
    @endcan
</div>
