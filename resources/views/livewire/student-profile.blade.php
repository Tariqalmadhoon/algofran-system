@php
    $startSurah = $surahs->firstWhere('id', (int) $baselineStartSurahId);
    $endSurah = $surahs->firstWhere('id', (int) $baselineEndSurahId);
    $baselineEndSurahs = $startSurah ? $surahs->where('id', '>=', $startSurah->id)->values() : $surahs;
    $baselineEndMinimum = $startSurah && $endSurah && $startSurah->id === $endSurah->id && (int) $baselineStartAyahNumber > 0
        ? (int) $baselineStartAyahNumber
        : 1;
    $relationships = ['father' => 'الأب', 'mother' => 'الأم', 'brother' => 'الأخ', 'sister' => 'الأخت', 'uncle' => 'العم/الخال', 'aunt' => 'العمة/الخالة', 'other' => 'أخرى'];
@endphp

<div class="space-y-6">
    <nav class="text-sm text-slate-500"><a class="font-bold text-emerald-700" href="{{ route('students.index') }}">الطلاب</a><span class="mx-2">/</span>{{ $student->full_name }}</nav>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->has('quran_range'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700" role="alert">{{ $errors->first('quran_range') }}</div>
    @endif

    <section class="panel">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-3xl bg-emerald-100 text-2xl font-black text-emerald-800">
                @if($student->photo)
                    <img class="size-full object-cover" src="{{ route('private-files.show', $student->photo) }}" alt="صورة {{ $student->full_name }}">
                @else
                    {{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->family_name, 0, 1) }}
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="eyebrow">{{ $student->student_number }}</p>
                <h1 class="page-title">{{ $student->full_name }}</h1>
                <div class="mt-3 flex flex-wrap gap-2 text-sm text-slate-600">
                    <span class="badge-active">{{ $student->status->label() }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold">{{ $student->currentHalaqa?->name ?? 'غير ملتحق بحلقة' }}</span>
                    @if($student->birth_date)<span class="rounded-full bg-slate-100 px-3 py-1">مواليد {{ $student->birth_date->format('Y-m-d') }}</span>@endif
                </div>
            </div>
            <div class="text-sm leading-7 text-slate-600">
                <p>الهاتف: <span dir="ltr">{{ $student->contact_phone ?? '—' }}</span></p>
                <p>تاريخ التسجيل: {{ $student->registration_date->format('Y-m-d') }}</p>
                @can('guardian.private-data.view')
                    <p>الهوية: <span dir="ltr">{{ $student->identity_number ?? '—' }}</span></p>
                    @if($student->identityDocument)<a class="font-bold text-emerald-700" href="{{ route('private-files.show', $student->identityDocument) }}">عرض وثيقة الطالب</a>@endif
                @endcan
            </div>
        </div>
    </section>

    @can('recitations.view')
        @php($progress = $student->latestProgress)
        <section class="panel">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="eyebrow">Progress Engine</p><h2 class="section-title">التقدم والدرجة الذكية</h2></div>@if($progress)<div class="text-left"><p class="text-4xl font-black text-emerald-800">{{ number_format($progress->score, 1) }}<span class="text-sm text-slate-400">/100</span></p><p class="text-xs text-slate-400">حتى {{ $progress->as_of_date->format('Y-m-d') }}</p></div>@endif</div>
            @if($progress)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                    @foreach([
                        ['الآيات المحفوظة', number_format($progress->memorized_ayahs)],
                        ['نسبة القرآن', number_format($progress->memorized_percentage, 2).'%'],
                        ['السور المكتملة', $progress->completed_surahs],
                        ['الأجزاء المكتملة', $progress->completed_juz],
                        ['متوسط التقييم', number_format($progress->evaluation_average, 1)],
                        ['الحضور', number_format($progress->attendance_rate, 1).'%'],
                    ] as [$label, $value])<div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-emerald-950">{{ $value }}</p><p class="mt-1 text-xs text-slate-500">{{ $label }}</p></div>@endforeach
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-2"><div class="rounded-2xl border border-slate-100 p-4"><p class="font-black text-emerald-950">آخر موضع حفظ</p><p class="mt-2 text-sm text-slate-600">@if($progress->lastMemorizedAyah){{ $progress->lastMemorizedAyah->surah->name_arabic }} — آية {{ $progress->lastMemorizedAyah->ayah_number }}@elseلا توجد نطاقات حفظ مسجلة.@endif</p><p class="mt-2 text-xs text-slate-400">آخر مراجعة: {{ $progress->last_revision_at?->format('Y-m-d') ?? 'غير مسجلة' }} · اتجاه الأداء: {{ $progress->performance_trend > 0 ? '+' : '' }}{{ number_format($progress->performance_trend, 1) }}</p></div><div class="rounded-2xl border border-slate-100 p-4"><p class="font-black text-emerald-950">تفسير الدرجة</p><ul class="mt-2 space-y-1 text-sm text-slate-600">@foreach($progress->score_breakdown['reasons'] ?? [] as $reason)<li>• {{ $reason }}</li>@endforeach</ul></div></div>
            @else
                <p class="rounded-2xl bg-slate-50 py-8 text-center text-sm text-slate-400">تظهر المؤشرات بعد تسجيل المحفوظ السابق أو أول سجل يومي.</p>
            @endif
        </section>
    @endcan

    @can('update', $student)
        <div x-data="{ tab: 'profile' }" class="panel">
            <div class="mb-6 flex flex-wrap gap-2">
                <button @click="tab = 'profile'" :class="tab === 'profile' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">بيانات الطالب</button>
                <button @click="tab = 'guardian'" :class="tab === 'guardian' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">ولي أمر</button>
                <button @click="tab = 'baseline'" :class="tab === 'baseline' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">المحفوظ السابق</button>
                <button @click="tab = 'enrollment'" :class="tab === 'enrollment' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">نقل الحلقة</button>
            </div>

            <form x-show="tab === 'profile'" wire:submit="saveProfile" class="space-y-4">
                <div><p class="eyebrow">الملف الشخصي</p><h2 class="section-title">تحديث بيانات الطالب</h2></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">الاسم الأول</span><input wire:model="profileFirstName" class="form-input"><x-input-error :messages="$errors->get('profileFirstName')" /></label>
                    <label><span class="form-label">اسم الأب</span><input wire:model="profileFatherName" class="form-input"><x-input-error :messages="$errors->get('profileFatherName')" /></label>
                    <label><span class="form-label">اسم الجد</span><input wire:model="profileGrandfatherName" class="form-input"><x-input-error :messages="$errors->get('profileGrandfatherName')" /></label>
                    <label><span class="form-label">اسم العائلة</span><input wire:model="profileFamilyName" class="form-input"><x-input-error :messages="$errors->get('profileFamilyName')" /></label>
                    <label><span class="form-label">رقم الهوية</span><input wire:model="profileIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('profileIdentityNumber')" /></label>
                    <label><span class="form-label">تاريخ الميلاد</span><input wire:model="profileBirthDate" type="date" class="form-input"><x-input-error :messages="$errors->get('profileBirthDate')" /></label>
                    <label><span class="form-label">هاتف التواصل</span><input wire:model="profileContactPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('profileContactPhone')" /></label>
                    <label><span class="form-label">الحالة</span><select wire:model="profileStatus" class="form-input">@foreach($studentStatuses as $studentStatus)<option value="{{ $studentStatus->value }}">{{ $studentStatus->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('profileStatus')" /></label>
                    <label><span class="form-label">صورة شخصية جديدة</span><input wire:model="profilePhoto" type="file" accept="image/*" class="form-input"><x-input-error :messages="$errors->get('profilePhoto')" /></label>
                    <label><span class="form-label">وثيقة هوية جديدة</span><input wire:model="profileIdentityDocument" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('profileIdentityDocument')" /></label>
                </div>
                <label><span class="form-label">ملاحظات</span><textarea wire:model="profileNotes" rows="2" class="form-input"></textarea></label>
                <button class="btn-primary flex" wire:loading.attr="disabled" type="submit">حفظ التعديلات</button>
            </form>

            <form x-show="tab === 'guardian'" x-cloak wire:submit="saveGuardian" class="space-y-4">
                <div><p class="eyebrow">الأسرة والتواصل</p><h2 class="section-title">ربط ولي أمر</h2><p class="page-subtitle">إذا تطابق رقم الهوية مع ولي أمر موجود فسيعاد استخدام سجله بدل تكراره.</p></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">الاسم الكامل</span><input wire:model="guardianName" class="form-input"><x-input-error :messages="$errors->get('guardianName')" /></label>
                    <label><span class="form-label">رقم الهوية</span><input wire:model="guardianIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianIdentityNumber')" /></label>
                    <label><span class="form-label">الهاتف</span><input wire:model="guardianPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianPhone')" /></label>
                    <label><span class="form-label">هاتف بديل</span><input wire:model="guardianAlternativePhone" class="form-input" dir="ltr"></label>
                    <label><span class="form-label">البريد الإلكتروني</span><input wire:model="guardianEmail" type="email" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('guardianEmail')" /></label>
                    <label><span class="form-label">صلة القرابة</span><select wire:model="guardianRelationship" class="form-input">@foreach($relationships as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label><span class="form-label">وثيقة الهوية الخاصة</span><input wire:model="guardianIdentityDocument" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('guardianIdentityDocument')" /></label>
                </div>
                <div class="flex flex-wrap gap-5 text-sm font-semibold text-slate-700"><label class="flex items-center gap-2"><input wire:model="guardianIsPrimary" type="checkbox"> ولي الأمر الأساسي</label><label class="flex items-center gap-2"><input wire:model="guardianCanReceiveNotifications" type="checkbox"> يستقبل الإشعارات</label></div>
                <label><span class="form-label">ملاحظات</span><textarea wire:model="guardianNotes" rows="2" class="form-input"></textarea></label>
                <button class="btn-primary flex" wire:loading.attr="disabled" type="submit">ربط ولي الأمر</button>
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
                    <article class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-emerald-950">{{ $guardian->full_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $relationships[$guardian->pivot->relationship] ?? $guardian->pivot->relationship }} · <span dir="ltr">{{ $guardian->phone }}</span></p></div>@if($guardian->pivot->is_primary)<span class="badge-active">أساسي</span>@endif</div>
                        @can('guardian.private-data.view')<div class="mt-2 text-xs text-slate-500">الهوية: <span dir="ltr">{{ $guardian->identity_number ?? '—' }}</span>@if($guardian->identityDocument) · <a class="font-bold text-emerald-700" href="{{ route('private-files.show', $guardian->identityDocument) }}">الوثيقة</a>@endif</div>@endcan
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
