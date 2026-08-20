<div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">إدارة الطلاب</p>
            <h1 class="page-title">ملفات الطلاب</h1>
            <p class="page-subtitle">بحث سريع، بيانات موثقة، وحالة التحاق حالية مع تاريخ كامل للنقل بين الحلقات.</p>
        </div>
        @can('create', App\Models\Student::class)
            <button class="btn-primary flex" type="button" x-data @click="$dispatch('toggle-student-form')">إضافة طالب</button>
        @endcan
    </header>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" role="status">{{ session('success') }}</div>
    @endif

    @can('create', App\Models\Student::class)
        <section x-data="{ open: false }" @toggle-student-form.window="open = !open" x-show="open" x-cloak class="panel">
            <div class="mb-5">
                <p class="eyebrow">ملف جديد</p>
                <h2 class="section-title">بيانات الطالب الأساسية</h2>
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
                    <label><span class="form-label">هاتف التواصل</span><input wire:model="contactPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('contactPhone')" /></label>
                    <label><span class="form-label">تاريخ التسجيل</span><input wire:model="registrationDate" type="date" class="form-input"><x-input-error :messages="$errors->get('registrationDate')" /></label>
                    <label><span class="form-label">الحالة</span><select wire:model="status" class="form-input">@foreach($statuses as $studentStatus)<option value="{{ $studentStatus->value }}">{{ $studentStatus->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('status')" /></label>
                    <label><span class="form-label">الحلقة الأولى</span><select wire:model="halaqaId" class="form-input"><option value="">دون حلقة حاليًا</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaId')" /></label>
                    <label><span class="form-label">الصورة الشخصية</span><input wire:model="photo" type="file" accept="image/*" class="form-input"><x-input-error :messages="$errors->get('photo')" /></label>
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
                            <td class="font-bold text-emerald-950">{{ $student->full_name }}</td>
                            <td>{{ $student->currentHalaqa?->name ?? 'غير ملتحق' }}</td>
                            <td dir="ltr">{{ $student->contact_phone ?? '—' }}</td>
                            <td><span class="{{ $student->status->value === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ $student->status->label() }}</span></td>
                            <td><a class="text-sm font-bold text-emerald-700 hover:text-emerald-900" href="{{ route('students.show', $student) }}">فتح الملف</a></td>
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
