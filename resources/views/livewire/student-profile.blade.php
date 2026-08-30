@php
    $startSurah = $surahs->firstWhere('id', (int) $baselineStartSurahId);
    $endSurah = $surahs->firstWhere('id', (int) $baselineEndSurahId);
    $baselineEndSurahs = $startSurah ? $surahs->where('id', '>=', $startSurah->id)->values() : $surahs;
    $baselineEndMinimum = $startSurah && $endSurah && $startSurah->id === $endSurah->id && (int) $baselineStartAyahNumber > 0
        ? (int) $baselineStartAyahNumber
        : 1;
    $relationships = ['father' => 'الأب', 'mother' => 'الأم', 'brother' => 'الأخ', 'sister' => 'الأخت', 'uncle' => 'العم/الخال', 'aunt' => 'العمة/الخالة', 'other' => 'أخرى'];
    $progress = $student->latestProgress;
    $studentPhotoUrl = $student->photo ? route('private-files.preview', $student->photo) : null;
    $canManagePrivateStudentData = auth()->user()->can('guardian.private-data.view') || auth()->user()->can('update', $student);
@endphp

<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-slate-500"><a class="font-bold text-emerald-700" href="{{ route('students.index') }}">الطلاب</a><svg class="size-3" viewBox="0 0 12 12" fill="none" stroke="currentColor"><path d="m8 2-4 4 4 4"/></svg><span class="truncate">{{ $student->full_name }}</span></nav>

    <x-flash-messages inline consume />
    @if ($errors->has('quran_range'))
        <x-feedback-alert type="error" title="نطاق قرآني غير صالح" :message="$errors->first('quran_range')" :duration="0" />
    @endif

    <section class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-700 p-6 text-white shadow-[0_28px_75px_-40px_rgba(6,78,59,.9)] sm:p-8">
        <svg class="absolute inset-0 -z-10 size-full text-white/[.04]" aria-hidden="true"><defs><pattern id="student-islamic-pattern" width="74" height="74" patternUnits="userSpaceOnUse"><path d="M37 4 47 27l23 10-23 10-10 23-10-23L4 37l23-10L37 4Z" fill="none" stroke="currentColor"/><circle cx="37" cy="37" r="12" fill="none" stroke="currentColor"/></pattern></defs><rect width="100%" height="100%" fill="url(#student-islamic-pattern)"/></svg>
        <div class="absolute -left-20 -top-24 -z-10 size-72 rounded-full bg-emerald-300/10 blur-3xl"></div>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
            <div class="relative mx-auto size-32 shrink-0 lg:mx-0">
                <x-student-avatar :student="$student" size="hero" class="border-4 border-white/15 shadow-2xl" />
                <span class="absolute -bottom-2 -left-2 grid size-10 place-items-center rounded-2xl border-2 border-emerald-900 bg-amber-300 text-emerald-950 shadow-lg"><x-islamic-icon name="quran" class="size-5" /></span>
            </div>

            <div class="min-w-0 flex-1 text-center lg:text-right">
                <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-start"><span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black tracking-wider" dir="ltr">{{ $student->student_number }}</span><span class="flex items-center gap-1.5 rounded-full bg-emerald-300/15 px-3 py-1 text-xs font-bold text-emerald-100"><span class="size-1.5 rounded-full bg-emerald-300"></span>{{ $student->status->label() }}</span></div>
                <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ $student->full_name }}</h1>
                <p class="mt-2 flex items-center justify-center gap-2 text-sm text-emerald-100/75 lg:justify-start"><x-islamic-icon name="mosque" class="size-4" />{{ $student->currentHalaqa?->name ?? 'غير ملتحق بحلقة حاليًا' }}</p>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-3 lg:w-[25rem]">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur"><p class="text-emerald-200">تاريخ التسجيل</p><p class="mt-1 font-black" dir="ltr">{{ $student->registration_date->format('Y-m-d') }}</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur"><p class="text-emerald-200">تاريخ الميلاد</p><p class="mt-1 font-black" dir="ltr">{{ $student->birth_date?->format('Y-m-d') ?? '—' }}</p></div>
                <div class="col-span-2 rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:col-span-1"><p class="text-emerald-200">هاتف التواصل</p><p class="mt-1 font-black" dir="ltr">{{ $student->contact_phone ?? '—' }}</p></div>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-5">
            <div class="flex flex-wrap gap-2 text-xs text-emerald-100/70">
                @if($canManagePrivateStudentData)<span>الهوية: <b dir="ltr">{{ $student->identity_number ?? '—' }}</b></span>@endif
                @if($student->identityDocument)
                    @if($canManagePrivateStudentData)
                        <a class="font-black text-emerald-200 hover:text-white" href="{{ route('private-files.show', $student->identityDocument) }}">عرض وثيقة الطالب</a>
                    @endif
                @endif
            </div>
            @can('update', $student)<a href="#student-profile-editor" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-black text-emerald-950 shadow-lg hover:-translate-y-0.5"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 16-1 5 5-1L19 9l-4-4L4 16Z"/><path d="m13 7 4 4"/></svg>تحديث ملف الطالب</a>@endcan
        </div>
    </section>

    @can('recitations.view')
        <section class="panel relative overflow-hidden">
            <div class="absolute -left-8 -top-10 text-emerald-50"><x-islamic-icon name="crescent" class="size-40" /></div>
            <div class="relative mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div class="flex items-center gap-3"><span class="grid size-12 place-items-center rounded-2xl bg-emerald-900 text-white"><x-islamic-icon name="quran" class="size-6" /></span><div><p class="eyebrow">رحلة الحفظ والإتقان</p><h2 class="section-title">التقدم والدرجة الذكية</h2></div></div>@if($progress)<div class="text-left"><p class="text-4xl font-black text-emerald-800">{{ number_format($progress->score, 1) }}<span class="text-sm text-slate-400">/100</span></p><p class="text-xs text-slate-400">حتى {{ $progress->as_of_date->format('Y-m-d') }}</p></div>@endif</div>
            @if($memorizationJourney)
                <div class="memorization-journey-card relative mb-6 overflow-hidden rounded-[1.75rem] border border-amber-200/70 bg-gradient-to-l from-emerald-950 via-emerald-900 to-teal-700 p-5 text-white shadow-[0_22px_55px_-32px_rgba(6,78,59,.9)] sm:p-6" data-motion="reveal" data-reveal="scale">
                    <div class="memorization-orbit absolute -left-9 -top-9 size-32 rounded-full border border-amber-300/20"></div>
                    <div class="absolute -bottom-16 right-1/3 size-40 rounded-full bg-emerald-300/10 blur-3xl"></div>
                    <div class="relative grid gap-5 lg:grid-cols-[auto_1fr] lg:items-center">
                        <div class="flex items-center gap-4">
                            <span class="grid size-16 shrink-0 place-items-center rounded-2xl border border-amber-200/35 bg-amber-300 text-2xl font-black text-emerald-950 shadow-lg shadow-emerald-950/25"><x-islamic-icon name="quran" class="size-8" /></span>
                            <div><p class="text-xs font-bold text-emerald-100/75">المحفوظ حسب مسار الناس ← الفاتحة</p><p class="mt-1 text-4xl font-black tracking-tight text-white"><span class="memorization-number">{{ $memorizationJourney['completed_juz'] }}</span><span class="mr-2 text-sm font-bold text-emerald-100/70">من 30 جزءًا</span></p></div>
                        </div>
                        <div class="memorization-encouragement lg:border-r lg:border-white/10 lg:pr-6"><p class="text-lg font-black text-amber-200">{{ $memorizationJourney['encouragement_title'] }}</p><p class="mt-1 text-sm leading-7 text-emerald-50/80">{{ $memorizationJourney['encouragement_message'] }}</p></div>
                    </div>
                    <div class="relative mt-5">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold text-emerald-100/75"><span>تقدم الأجزاء المكتملة</span><span dir="ltr">{{ number_format($memorizationJourney['completed_percentage'], 1) }}%</span></div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-black/20 ring-1 ring-white/10"><div class="memorization-progress-fill h-full rounded-full bg-gradient-to-l from-amber-300 to-emerald-300" style="width: {{ $memorizationJourney['completed_percentage'] }}%"></div></div>
                        <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-bold">
                            @if($memorizationJourney['has_progress'])
                                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">المحطة الحالية: سورة {{ $memorizationJourney['frontier_surah_name'] }} · آية {{ $memorizationJourney['frontier_ayah_number'] }}</span>
                                @if($memorizationJourney['next_juz'])<span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1.5 text-amber-100">الهدف التالي: إكمال الجزء {{ $memorizationJourney['next_juz'] }}</span>@endif
                            @else
                                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">يبدأ العداد تلقائيًا مع أول بند «حفظ جديد»</span>
                            @endif
                            <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">المتبقي: {{ $memorizationJourney['remaining_juz'] }} جزءًا</span>
                        </div>
                    </div>
                </div>
            @endif
            @if($progress)
                <div class="relative grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    @foreach([
                        ['الآيات المحفوظة', number_format($progress->memorized_ayahs), 'quran'],
                        ['نسبة القرآن', number_format($progress->memorized_percentage, 2).'%', 'crescent'],
                        ['السور المكتملة', $progress->completed_surahs, 'star'],
                        ['الأجزاء المكتملة', max($progress->completed_juz, $memorizationJourney['completed_juz'] ?? 0), 'quran'],
                        ['متوسط التقييم', number_format($progress->evaluation_average, 1), 'certificate'],
                        ['الحضور', number_format($progress->attendance_rate, 1).'%', 'mosque'],
                    ] as [$label, $value, $icon])<div class="group rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-4 transition duration-300 hover:-translate-y-1 hover:border-emerald-200"><span class="mb-3 grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-100"><x-islamic-icon :name="$icon" class="size-4" /></span><p class="text-2xl font-black text-emerald-950">{{ $value }}</p><p class="mt-1 text-xs font-bold text-slate-500">{{ $label }}</p></div>@endforeach
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-2"><div class="rounded-2xl border border-slate-100 p-4"><p class="font-black text-emerald-950">المحطة الحالية في مسار الحفظ</p><p class="mt-2 text-sm text-slate-600">@if($memorizationJourney['has_progress'])سورة {{ $memorizationJourney['frontier_surah_name'] }} — آية {{ $memorizationJourney['frontier_ayah_number'] }} — الجزء {{ $memorizationJourney['current_juz'] }}@elseif($progress->lastMemorizedAyah){{ $progress->lastMemorizedAyah->surah->name_arabic }} — آية {{ $progress->lastMemorizedAyah->ayah_number }}@elseلا توجد نطاقات حفظ مسجلة.@endif</p><p class="mt-2 text-xs text-slate-400">آخر مراجعة: {{ $progress->last_revision_at?->format('Y-m-d') ?? 'غير مسجلة' }} · اتجاه الأداء: {{ $progress->performance_trend > 0 ? '+' : '' }}{{ number_format($progress->performance_trend, 1) }}</p></div><div class="rounded-2xl border border-slate-100 p-4"><p class="font-black text-emerald-950">تفسير الدرجة</p><ul class="mt-2 space-y-1 text-sm text-slate-600">@foreach($progress->score_breakdown['reasons'] ?? [] as $reason)<li>• {{ $reason }}</li>@endforeach</ul></div></div>
            @else
                <p class="rounded-2xl bg-slate-50 py-8 text-center text-sm text-slate-400">تظهر المؤشرات بعد تسجيل المحفوظ السابق أو أول سجل يومي.</p>
            @endif
        </section>
    @endcan

    @can('update', $student)
        <div id="student-profile-editor" x-data="{ tab: 'profile' }" x-on:guardian-editor-opened.window="tab = 'guardian'; $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))" class="panel scroll-mt-24 overflow-hidden">
            <div class="-mx-6 -mt-6 mb-7 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button @click="tab = 'profile'" :class="tab === 'profile' ? 'bg-emerald-900 text-white shadow-lg shadow-emerald-900/15' : 'bg-white text-slate-600 hover:bg-emerald-50'" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition duration-300" type="button"><x-nav-icon name="profile" class="size-4" />بيانات الطالب</button>
                    <button @click="tab = 'guardian'" :class="tab === 'guardian' ? 'bg-emerald-900 text-white shadow-lg shadow-emerald-900/15' : 'bg-white text-slate-600 hover:bg-emerald-50'" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition duration-300" type="button"><x-islamic-icon name="crescent" class="size-4" />ولي أمر</button>
                    <button @click="tab = 'baseline'" :class="tab === 'baseline' ? 'bg-emerald-900 text-white shadow-lg shadow-emerald-900/15' : 'bg-white text-slate-600 hover:bg-emerald-50'" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition duration-300" type="button"><x-islamic-icon name="quran" class="size-4" />المحفوظ السابق</button>
                    <button @click="tab = 'enrollment'" :class="tab === 'enrollment' ? 'bg-emerald-900 text-white shadow-lg shadow-emerald-900/15' : 'bg-white text-slate-600 hover:bg-emerald-50'" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition duration-300" type="button"><x-islamic-icon name="mosque" class="size-4" />نقل الحلقة</button>
                </div>
            </div>

            <form x-show="tab === 'profile'" x-transition.opacity.duration.200ms wire:submit="saveProfile" class="space-y-5">
                <div><p class="eyebrow">الملف الشخصي</p><h2 class="section-title">تحديث بيانات الطالب</h2><p class="page-subtitle">يمكنك تحديث الصورة والبيانات الأساسية من مكان واحد.</p></div>

                <div class="rounded-3xl border border-dashed border-emerald-200 bg-gradient-to-l from-emerald-50/70 to-white p-4">
                    <div class="flex flex-col items-center gap-4 sm:flex-row">
                        <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-3xl bg-emerald-900 text-2xl font-black text-white shadow-lg">
                            @if($profilePhoto)
                                <img src="{{ $profilePhoto->temporaryUrl() }}" alt="معاينة صورة الطالب" class="size-full object-cover">
                            @elseif(!$removeProfilePhoto && $studentPhotoUrl)
                                <img src="{{ $studentPhotoUrl }}" alt="صورة {{ $student->full_name }}" class="size-full object-cover">
                            @else
                                {{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->family_name, 0, 1) }}
                            @endif
                        </div>
                        <div class="flex-1 text-center sm:text-right">
                            <p class="font-black text-slate-800">صورة الطالب الشخصية</p>
                            <p class="mt-1 text-xs leading-6 text-slate-500">صورة مربعة من نوع JPG أو PNG أو WEBP، وبحجم أقصى 3 ميجابايت.</p>
                            <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                                <label class="btn-secondary cursor-pointer"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>{{ $studentPhotoUrl || $profilePhoto ? 'تغيير الصورة' : 'اختيار صورة' }}<input wire:model="profilePhoto" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp"></label>
                                @if(($studentPhotoUrl && !$removeProfilePhoto) || $profilePhoto)<button wire:click="removeProfilePhotoSelection" type="button" class="rounded-xl px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50">إزالة الصورة</button>@endif
                                @if($removeProfilePhoto && $studentPhotoUrl)<button wire:click="$set('removeProfilePhoto', false)" type="button" class="rounded-xl px-3 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-50">تراجع</button>@endif
                            </div>
                            <p wire:loading wire:target="profilePhoto" class="mt-2 text-xs font-bold text-emerald-700">جارٍ تجهيز المعاينة…</p>
                            <x-input-error :messages="$errors->get('profilePhoto')" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">الاسم الأول</span><input wire:model="profileFirstName" class="form-input"><x-input-error :messages="$errors->get('profileFirstName')" /></label>
                    <label><span class="form-label">اسم الأب</span><input wire:model="profileFatherName" class="form-input"><x-input-error :messages="$errors->get('profileFatherName')" /></label>
                    <label><span class="form-label">اسم الجد</span><input wire:model="profileGrandfatherName" class="form-input"><x-input-error :messages="$errors->get('profileGrandfatherName')" /></label>
                    <label><span class="form-label">اسم العائلة</span><input wire:model="profileFamilyName" class="form-input"><x-input-error :messages="$errors->get('profileFamilyName')" /></label>
                    <label><span class="form-label">رقم الهوية</span><input wire:model="profileIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('profileIdentityNumber')" /></label>
                    <label><span class="form-label">تاريخ الميلاد</span><input wire:model="profileBirthDate" type="date" class="form-input"><x-input-error :messages="$errors->get('profileBirthDate')" /></label>
                    <label><span class="form-label">هاتف التواصل</span><input wire:model="profileContactPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('profileContactPhone')" /></label>
                    <label><span class="form-label">نوع الكفالة</span><input wire:model="profileSponsorshipType" class="form-input" placeholder="مثال: كفالة تعليمية"><x-input-error :messages="$errors->get('profileSponsorshipType')" /></label>
                    <label><span class="form-label">جهة الكفالة</span><input wire:model="profileSponsorshipOrganization" class="form-input" placeholder="اسم الجهة إن وجدت"><x-input-error :messages="$errors->get('profileSponsorshipOrganization')" /></label>
                    <label><span class="form-label">الحالة</span><select wire:model="profileStatus" class="form-input">@foreach($studentStatuses as $studentStatus)<option value="{{ $studentStatus->value }}">{{ $studentStatus->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('profileStatus')" /></label>
                    <label><span class="form-label">وثيقة هوية جديدة</span><input wire:model="profileIdentityDocument" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('profileIdentityDocument')" /></label>
                </div>
                <label><span class="form-label">ملاحظات</span><textarea wire:model="profileNotes" rows="2" class="form-input"></textarea></label>
                <button class="btn-primary flex" wire:loading.attr="disabled" type="submit">حفظ التعديلات</button>
            </form>

            <form x-show="tab === 'guardian'" x-cloak wire:submit="saveGuardian" class="space-y-4">
                <div><p class="eyebrow">الأسرة والتواصل</p><h2 class="section-title">{{ $editingGuardianId ? 'تعديل بيانات ولي الأمر' : 'بيانات ولي الأمر' }}</h2><p class="page-subtitle">لا يحتاج ولي الأمر إلى حساب مستقل. أدخل بيانات التواصل الأساسية، ويمكنك إضافة بقية التفاصيل عند توفرها.</p></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">الاسم الكامل</span><input wire:model="guardianName" class="form-input"><x-input-error :messages="$errors->get('guardianName')" /></label>
                    <label><span class="form-label">صلة القرابة</span><select wire:model="guardianRelationship" class="form-input">@foreach($relationships as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label><span class="form-label">الهاتف</span><input wire:model="guardianPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianPhone')" /></label>
                    <label><span class="form-label">هاتف بديل</span><input wire:model="guardianAlternativePhone" class="form-input" dir="ltr"></label>
                </div>
                <details class="group rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-black text-emerald-900"><span>بيانات إضافية اختيارية</span><svg class="size-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
                    <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2 xl:grid-cols-3">
                        <label><span class="form-label">رقم الهوية</span><input wire:model="guardianIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianIdentityNumber')" /></label>
                        <label><span class="form-label">البريد الإلكتروني</span><input wire:model="guardianEmail" type="email" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianEmail')" /></label>
                        <label><span class="form-label">وثيقة الهوية</span><input wire:model="guardianIdentityDocument" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('guardianIdentityDocument')" /></label>
                        <label class="md:col-span-2 xl:col-span-3"><span class="form-label">ملاحظات</span><textarea wire:model="guardianNotes" rows="2" class="form-input"></textarea></label>
                        <div class="flex flex-wrap gap-5 text-sm font-semibold text-slate-700 md:col-span-2 xl:col-span-3"><label class="flex items-center gap-2"><input wire:model="guardianIsPrimary" type="checkbox"> ولي الأمر الأساسي</label><label class="flex items-center gap-2"><input wire:model="guardianCanReceiveNotifications" type="checkbox"> يستقبل الإشعارات</label></div>
                    </div>
                </details>
                <div class="flex flex-wrap gap-2">
                    <button class="btn-primary flex" wire:loading.attr="disabled" type="submit">{{ $editingGuardianId ? 'حفظ التعديلات' : 'حفظ بيانات ولي الأمر' }}</button>
                    @if($editingGuardianId)<button wire:click="cancelGuardianEditing" class="btn-secondary" type="button">إلغاء التعديل</button>@endif
                </div>
            </form>

            <form x-show="tab === 'baseline'" x-cloak wire:submit="saveBaseline" class="space-y-4">
                <div><p class="eyebrow">Quran Reference</p><h2 class="section-title">تسجيل المحفوظ قبل الالتحاق</h2></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <label><span class="form-label">سورة البداية</span><select wire:model.live="baselineStartSurahId" class="form-input"><option value="">اختر</option>@foreach($surahs as $surah)<option value="{{ $surah->id }}">{{ $surah->id }}. {{ $surah->name_arabic }}</option>@endforeach</select><x-input-error :messages="$errors->get('baselineStartSurahId')" /></label>
                    <label><span class="form-label">آية البداية</span><select wire:key="baseline-start-ayah-{{ $baselineStartSurahId ?: 'none' }}" wire:model.live="baselineStartAyahNumber" class="form-input" @disabled(!$startSurah)><option value="">{{ $startSurah ? 'اختر رقم الآية' : 'اختر السورة أولًا' }}</option>@if($startSurah)@for($ayah = 1; $ayah <= $startSurah->verses_count; $ayah++)<option value="{{ $ayah }}">{{ $ayah }}</option>@endfor@endif</select><x-input-error :messages="$errors->get('baselineStartAyahNumber')" /></label>
                    <label><span class="form-label">سورة النهاية</span><select wire:model.live="baselineEndSurahId" class="form-input" @disabled(!$startSurah)><option value="">{{ $startSurah ? 'اختر' : 'اختر سورة البداية أولًا' }}</option>@foreach($baselineEndSurahs as $surah)<option value="{{ $surah->id }}">{{ $surah->id }}. {{ $surah->name_arabic }}</option>@endforeach</select><x-input-error :messages="$errors->get('baselineEndSurahId')" /></label>
                    <label><span class="form-label">آية النهاية</span><select wire:key="baseline-end-ayah-{{ $baselineEndSurahId ?: 'none' }}-{{ $baselineEndMinimum }}" wire:model.live="baselineEndAyahNumber" class="form-input" @disabled(!$endSurah)><option value="">{{ $endSurah ? 'اختر رقم الآية' : 'اختر السورة أولًا' }}</option>@if($endSurah)@for($ayah = $baselineEndMinimum; $ayah <= $endSurah->verses_count; $ayah++)<option value="{{ $ayah }}">{{ $ayah }}</option>@endfor@endif</select><x-input-error :messages="$errors->get('baselineEndAyahNumber')" /></label>
                    <label><span class="form-label">تاريخ التسجيل</span><input wire:model="baselineRecordedAt" type="date" class="form-input"><x-input-error :messages="$errors->get('baselineRecordedAt')" /></label>
                </div>
                <label><span class="form-label">ملاحظات</span><textarea wire:model="baselineNotes" rows="2" class="form-input"></textarea></label>
                <button class="btn-primary flex" type="submit">حفظ نقطة البداية</button>
            </form>

            <form x-show="tab === 'enrollment'" x-cloak wire:submit="saveEnrollment" class="space-y-4">
                <div><p class="eyebrow">سجل الالتحاق</p><h2 class="section-title">إلحاق أو نقل الطالب</h2><p class="page-subtitle">يغلق النظام الالتحاق الحالي في اليوم السابق ويحفظ المسارين في التاريخ.</p></div>
                <div class="grid gap-4 md:grid-cols-3">
                    <label><span class="form-label">الحلقة الجديدة</span><select wire:model="enrollmentHalaqaId" class="form-input"><option value="">اختر الحلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('enrollmentHalaqaId')" /></label>
                    <label><span class="form-label">تاريخ البدء</span><input wire:model="enrollmentStartsAt" type="date" class="form-input"><x-input-error :messages="$errors->get('enrollmentStartsAt')" /></label>
                    <label><span class="form-label">سبب النقل/الإلحاق</span><input wire:model="enrollmentReason" class="form-input"><x-input-error :messages="$errors->get('enrollmentReason')" /></label>
                </div>
                <button class="btn-primary flex" type="submit">حفظ الالتحاق</button>
            </form>
        </div>
    @endcan

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="mb-5"><p class="eyebrow">جهات التواصل</p><h2 class="section-title">أولياء الأمور</h2></div>
            <div class="space-y-3">
                @forelse($student->guardians as $guardian)
                    <article class="rounded-2xl border border-slate-100 p-4" wire:key="guardian-{{ $guardian->id }}">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-emerald-950">{{ $guardian->full_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $relationships[$guardian->pivot->relationship] ?? $guardian->pivot->relationship }} · <span dir="ltr">{{ $guardian->phone }}</span></p></div>@if($guardian->pivot->is_primary)<span class="badge-active">أساسي</span>@endif</div>
                        @if($canManagePrivateStudentData)<div class="mt-2 text-xs text-slate-500">الهوية: <span dir="ltr">{{ $guardian->identity_number ?? '—' }}</span>@if($guardian->identityDocument) · <a class="font-bold text-emerald-700" href="{{ route('private-files.show', $guardian->identityDocument) }}">الوثيقة</a>@endif</div>@endif
                        @can('update', $student)<button wire:click="editGuardian({{ $guardian->id }})" type="button" class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-emerald-700 transition hover:text-emerald-900"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 16-1 5 5-1L19 9l-4-4L4 16Z"/><path d="m13 7 4 4"/></svg>تعديل البيانات</button>@endcan
                    </article>
                @empty <p class="py-8 text-center text-sm text-slate-400">لم يربط ولي أمر بعد.</p> @endforelse
            </div>
        </section>

        <section class="panel">
            <div class="mb-5"><p class="eyebrow">الحلقات</p><h2 class="section-title">تاريخ الالتحاق</h2></div>
            <div class="space-y-3">
                @forelse($student->enrollments->sortByDesc('starts_at') as $enrollment)
                    <article class="flex items-center justify-between rounded-2xl border border-slate-100 p-4"><div><p class="font-bold">{{ $enrollment->halaqa->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $enrollment->reason ?? 'التحاق' }}</p></div><div class="text-left text-xs text-slate-500" dir="ltr">{{ $enrollment->starts_at->format('Y-m-d') }}<br>{{ $enrollment->ends_at?->format('Y-m-d') ?? 'مستمر' }}</div></article>
                @empty <p class="py-8 text-center text-sm text-slate-400">لا يوجد تاريخ التحاق.</p> @endforelse
            </div>
        </section>
    </div>

    <section class="grid gap-6 xl:grid-cols-3">
        <article class="panel"><div class="mb-4"><p class="eyebrow">الدورات</p><h2 class="section-title">المشاركات</h2></div><div class="space-y-3">@forelse($student->courseEnrollments->sortByDesc('enrolled_at') as $enrollment)<div class="rounded-2xl border border-slate-100 p-4"><div class="flex justify-between"><p class="font-bold">{{ $enrollment->course->name }}</p><span class="badge-inactive">{{ $enrollment->status->label() }}</span></div><p class="mt-2 text-xs text-slate-500">النتيجة: {{ $enrollment->result ?? '—' }} · التقدير: {{ $enrollment->grade ?? '—' }}</p></div>@empty<p class="py-6 text-center text-sm text-slate-400">لا توجد دورات.</p>@endforelse</div></article>
        <article class="panel"><div class="mb-4"><p class="eyebrow">الشهادات</p><h2 class="section-title">السجل الموثق</h2></div><div class="space-y-3">@forelse($student->certificates->sortByDesc('issued_at') as $certificate)<div class="rounded-2xl border border-slate-100 p-4"><p class="font-bold">{{ $certificate->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $certificate->issuer }} · {{ $certificate->issued_at->format('Y-m-d') }}</p>@if($certificate->privateFile)<a class="mt-2 inline-block text-xs font-bold text-emerald-700" href="{{ route('private-files.show', $certificate->privateFile) }}">تنزيل الشهادة</a>@endif</div>@empty<p class="py-6 text-center text-sm text-slate-400">لا توجد شهادات.</p>@endforelse</div></article>
        <article class="panel"><div class="mb-4"><p class="eyebrow">الإنجازات</p><h2 class="section-title">محطات الطالب</h2></div><div class="space-y-3">@forelse($student->achievements->sortByDesc('achieved_at') as $achievement)<div class="rounded-2xl border border-slate-100 p-4"><div class="flex justify-between gap-2"><p class="font-bold">{{ $achievement->title }}</p><span class="badge-active">{{ $achievement->type->label() }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $achievement->achieved_at->format('Y-m-d') }}</p></div>@empty<p class="py-6 text-center text-sm text-slate-400">لا توجد إنجازات.</p>@endforelse</div></article>
    </section>

    @can('recitations.view')
        <section class="panel">
            <div class="mb-5"><p class="eyebrow">الحفظ والمراجعة</p><h2 class="section-title">السجلات اليومية</h2></div>
            <div class="space-y-4">
                @forelse($dailyRecords as $record)
                    <article class="rounded-2xl border border-slate-100 p-4" wire:key="daily-record-{{ $record->id }}">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div><p class="font-bold text-emerald-950">{{ $record->record_date->format('Y-m-d') }} · {{ $record->attendance->status->label() }}</p><p class="mt-1 text-xs text-slate-500">{{ $record->halaqa->name }} — {{ $record->teacher->user->name }}@if($record->general_evaluation) — {{ $record->general_evaluation->label() }}@endif</p></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $record->recitationItems->count() }} بنود</span>
                        </div>
                        @if($record->recitationItems->isNotEmpty())
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                @foreach($record->recitationItems as $item)
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm"><span class="font-bold">{{ $item->type->label() }}:</span> {{ $item->startAyah->surah->name_arabic }} {{ $item->startAyah->ayah_number }} ← {{ $item->endAyah->surah->name_arabic }} {{ $item->endAyah->ayah_number }} <span class="text-slate-500">({{ $item->evaluation->label() }}، أخطاء الحفظ {{ $item->memorization_errors }}، التجويد {{ $item->tajweed_errors }})</span></div>
                                @endforeach
                            </div>
                        @endif
                        @if($record->notes)<p class="mt-3 text-sm text-slate-600">{{ $record->notes }}</p>@endif
                    </article>
                @empty <p class="py-8 text-center text-sm text-slate-400">لا توجد سجلات يومية بعد.</p> @endforelse
            </div>
            <div class="mt-5">{{ $dailyRecords->links() }}</div>
        </section>
    @endcan

    <section class="panel">
        <div class="mb-5"><p class="eyebrow">السجل الزمني</p><h2 class="section-title">تاريخ الطالب الكامل</h2></div>
        <ol class="relative mr-3 space-y-5 border-r-2 border-emerald-100 pr-6">
            @forelse($timeline as $event)
                <li class="relative" wire:key="event-{{ $event->id }}"><span class="absolute -right-[31px] top-1 size-3 rounded-full border-2 border-white bg-emerald-600 ring-2 ring-emerald-100"></span><div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-bold text-emerald-950">{{ $event->title }}</p>@if($event->description)<p class="mt-1 text-sm text-slate-600">{{ $event->description }}</p>@endif</div><time class="shrink-0 text-xs text-slate-400" dir="ltr">{{ $event->occurred_at->format('Y-m-d H:i') }}</time></div></li>
            @empty <li class="text-sm text-slate-400">لا توجد أحداث بعد.</li> @endforelse
        </ol>
        <div class="mt-5">{{ $timeline->links() }}</div>
    </section>
</div>
