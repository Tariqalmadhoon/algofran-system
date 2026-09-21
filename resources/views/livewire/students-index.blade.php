<div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">إدارة الطلاب</p>
            <h1 class="page-title">ملفات الطلاب</h1>
            <p class="page-subtitle">{{ $teacherMode ? 'أضف طلابك مباشرة إلى الحلقات المسندة إليك وتابع ملفاتهم.' : 'بحث سريع، بيانات موثقة، وحالة التحاق حالية مع تاريخ كامل للنقل بين الحلقات.' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('viewTrash', App\Models\Student::class)
                <a href="{{ route('students.trash') }}" class="btn-secondary flex items-center gap-2">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m-9 0 1 13h10l1-13" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    سلة المهملات
                </a>
            @endcan
            @can('create', App\Models\Student::class)
                <button class="btn-primary flex" type="button" x-data @click="$dispatch('toggle-student-form')">{{ $teacherMode ? 'إضافة طالب إلى حلقتي' : 'إضافة طالب' }}</button>
            @endcan
        </div>
    </header>

    <x-flash-messages inline consume />

    @can('create', App\Models\Student::class)
        <section x-data="{ open: false }" @toggle-student-form.window="open = !open" x-show="open" x-cloak class="panel">
            <div class="mb-5">
                <p class="eyebrow">ملف جديد</p>
                <h2 class="section-title">{{ $teacherMode ? 'إضافة طالب إلى حلقة مسندة إليك' : 'بيانات الطالب الأساسية' }}</h2>
                @if($teacherMode)<p class="mt-2 text-sm leading-6 text-slate-500">ستظهر هنا حلقاتك النشطة فقط، ويُربط الطالب بالحَلقة المختارة فور الحفظ.</p>@endif
            </div>
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">رقم الطالب</span><input wire:model="studentNumber" class="form-input" dir="ltr" placeholder="STU-2026-001"><x-input-error :messages="$errors->get('studentNumber')" /></label>
                    <label><span class="form-label">الاسم الأول</span><input wire:model="firstName" class="form-input"><x-input-error :messages="$errors->get('firstName')" /></label>
                    <label><span class="form-label">اسم الأب</span><input wire:model="fatherName" class="form-input"><x-input-error :messages="$errors->get('fatherName')" /></label>
                    <label><span class="form-label">اسم الجد</span><input wire:model="grandfatherName" class="form-input"><x-input-error :messages="$errors->get('grandfatherName')" /></label>
                    <label><span class="form-label">اسم العائلة</span><input wire:model="familyName" class="form-input"><x-input-error :messages="$errors->get('familyName')" /></label>
                    <label><span class="form-label">رقم الهوية</span><input wire:model="identityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('identityNumber')" /></label>
                    <label><span class="form-label">تاريخ الميلاد</span><input wire:model="birthDate" type="date" class="form-input"><x-input-error :messages="$errors->get('birthDate')" /></label>
                    <label><span class="form-label">هاتف التواصل</span><input wire:model="contactPhone" type="tel" inputmode="tel" class="form-input" dir="ltr" placeholder="059 000 0000"><small class="mt-1 block text-xs font-medium text-emerald-700">سيظهر زر واتساب للمراسلة بعد حفظ الملف.</small><x-input-error :messages="$errors->get('contactPhone')" /></label>
                    <label><span class="form-label">تاريخ التسجيل</span><input wire:model="registrationDate" type="date" class="form-input"><x-input-error :messages="$errors->get('registrationDate')" /></label>
                    <label><span class="form-label">الحالة</span><select wire:model="status" class="form-input">@foreach($statuses as $studentStatus)<option value="{{ $studentStatus->value }}">{{ $studentStatus->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('status')" /></label>
                    <label><span class="form-label">{{ $teacherMode ? 'الحلقة المسندة إليك' : 'الحلقة الأولى' }}</span><select wire:model="halaqaId" class="form-input"><option value="">{{ $teacherMode ? 'اختر الحلقة' : 'دون حلقة حاليًا' }}</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaId')" /></label>
                    <label class="md:col-span-2 xl:col-span-1"><span class="form-label">الصورة الشخصية</span><span class="flex items-center gap-3 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 p-3">@if($photo)<img src="{{ $photo->temporaryUrl() }}" alt="معاينة صورة الطالب" class="size-14 rounded-2xl object-cover">@else<span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-xl font-black text-emerald-700">ص</span>@endif<span class="min-w-0 flex-1"><input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-500 file:ml-3 file:rounded-xl file:border-0 file:bg-white file:px-3 file:py-2 file:font-bold file:text-emerald-700"><small wire:loading wire:target="photo" class="mt-1 block font-bold text-emerald-700">جارٍ تجهيز الصورة…</small></span></span><x-input-error :messages="$errors->get('photo')" /></label>
                    <label><span class="form-label">وثيقة الهوية الخاصة</span><input wire:model="identityDocument" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('identityDocument')" /></label>
                </div>
                <label><span class="form-label">ملاحظات</span><textarea wire:model="notes" rows="3" class="form-input"></textarea><x-input-error :messages="$errors->get('notes')" /></label>
                <button class="btn-primary flex" wire:loading.attr="disabled" type="submit"><span wire:loading.remove wire:target="save">حفظ ملف الطالب</span><span wire:loading wire:target="save">جارٍ الحفظ…</span></button>
            </form>
        </section>
    @endcan

    <section class="panel space-y-5">
        <div class="grid gap-3 md:grid-cols-3">
            <label><span class="form-label">بحث</span><input wire:model.live.debounce.350ms="search" class="form-input" placeholder="الاسم، الرقم، الهوية أو الهاتف"></label>
            <label><span class="form-label">الحالة</span><select wire:model.live="statusFilter" class="form-input"><option value="">كل الحالات</option>@foreach($statuses as $studentStatus)<option value="{{ $studentStatus->value }}">{{ $studentStatus->label() }}</option>@endforeach</select></label>
            <label><span class="form-label">الحلقة</span><select wire:model.live="halaqaFilter" class="form-input"><option value="">كل الحلقات</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select></label>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>رقم الطالب</th><th>الاسم</th><th>الحلقة الحالية</th><th>التواصل</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        <tr wire:key="student-{{ $student->id }}">
                            <td dir="ltr">{{ $student->student_number }}</td>
                            <td><a href="{{ route('students.show', $student) }}" class="flex items-center gap-3 font-bold text-emerald-950 hover:text-emerald-700"><x-student-avatar :student="$student" size="sm" /><span>{{ $student->full_name }}</span></a></td>
                            <td>{{ $student->currentHalaqa?->name ?? 'غير ملتحق' }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2" dir="ltr">
                                    <span>{{ $student->contact_phone ?? '—' }}</span>
                                    @can('update', $student)
                                        <x-whatsapp-contact :phone="$student->contact_phone" label="واتساب" class="px-2 py-1.5" />
                                    @endcan
                                </div>
                            </td>
                            <td><span class="{{ $student->status->value === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ $student->status->label() }}</span></td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    <a class="text-sm font-bold text-emerald-700 hover:text-emerald-900" href="{{ route('students.show', $student) }}">فتح الملف</a>
                                    @can('archive', $student)
                                        @if($student->status->value !== 'archived')
                                            <button type="button" class="text-xs font-black text-rose-600 transition hover:text-rose-800" @click="$dispatch('app:confirm', { title: 'نقل الطالب إلى سلة المهملات', message: 'سيُنهى إلحاق الطالب الحالي ويختفي من قائمة الطلاب. تستطيع الإدارة استعادته لاحقًا من سلة المهملات، وسجلات الحفظ والحضور ستبقى محفوظة.', confirmLabel: 'نقل إلى السلة', tone: 'danger', action: () => $wire.archiveStudent({{ $student->id }}) })">حذف</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-slate-400">لا توجد نتائج مطابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </section>
</div>
