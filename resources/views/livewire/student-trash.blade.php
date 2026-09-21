<div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">إدارة آمنة للملفات المحذوفة</p>
            <h1 class="page-title">سلة مهملات الطلاب</h1>
            <p class="page-subtitle">الملفات هنا لا تظهر في البحث أو القوائم اليومية. يمكنك استعادتها، أو حذفها نهائيًا بعد التأكد.</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn-secondary">العودة إلى الطلاب</a>
    </header>

    <x-flash-messages inline consume />

    <section class="panel space-y-5">
        <label class="block max-w-xl"><span class="form-label">بحث داخل سلة المهملات</span><input wire:model.live.debounce.350ms="search" class="form-input" placeholder="الاسم أو رقم الطالب أو رقم الهوية"></label>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>رقم الطالب</th><th>الاسم</th><th>تاريخ النقل للسلة</th><th>الحالة عند الاستعادة</th><th></th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        <tr wire:key="trashed-student-{{ $student->id }}">
                            <td dir="ltr">{{ $student->student_number }}</td>
                            <td><div class="flex items-center gap-3 font-bold text-emerald-950"><x-student-avatar :student="$student" size="sm" /><span>{{ $student->full_name }}</span></div></td>
                            <td>{{ $student->deleted_at?->translatedFormat('j F Y - g:i A') }}</td>
                            <td>{{ $student->pre_archive_status ? \App\Enums\StudentStatus::tryFrom($student->pre_archive_status)?->label() : 'نشط' }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    @can('restore', $student)
                                        <button type="button" class="text-sm font-black text-emerald-700 transition hover:text-emerald-900" @click="$dispatch('app:confirm', { title: 'استعادة ملف الطالب', message: 'سيعود الطالب إلى قائمة الطلاب دون إلحاق تلقائي بحلقة. يمكنك إلحاقه من ملفه بعد الاستعادة.', confirmLabel: 'استعادة الملف', tone: 'warning', action: () => $wire.restoreStudent({{ $student->id }}) })">استعادة</button>
                                    @endcan
                                    @can('forceDelete', $student)
                                        <button type="button" class="text-xs font-black text-rose-600 transition hover:text-rose-800" @click="$dispatch('app:confirm', { title: 'حذف نهائي غير قابل للتراجع', message: 'سيُحذف ملف الطالب وسجلاته المرتبطة نهائيًا. لا يمكن استعادة هذه العملية.', confirmLabel: 'حذف نهائي', tone: 'danger', action: () => $wire.permanentlyDeleteStudent({{ $student->id }}) })">حذف نهائي</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-slate-400">سلة المهملات فارغة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </section>
</div>
