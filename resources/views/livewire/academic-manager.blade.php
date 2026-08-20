<div x-data="{ tab: 'courses' }" class="space-y-6">
    <header><p class="eyebrow">Academic Modules</p><h1 class="page-title">الدورات والشهادات والإنجازات</h1><p class="page-subtitle">سجلات أكاديمية مرتبطة مباشرة بملف الطالب وخطه الزمني.</p></header>
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif

    <nav class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2">
        @can('courses.manage')<button @click="tab = 'courses'" :class="tab === 'courses' ? 'bg-emerald-800 text-white' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">الدورات</button><button @click="tab = 'enrollments'" :class="tab === 'enrollments' ? 'bg-emerald-800 text-white' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">التسجيل والنتائج</button>@endcan
        @can('certificates.manage')<button @click="tab = 'certificates'" :class="tab === 'certificates' ? 'bg-emerald-800 text-white' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">الشهادات</button>@endcan
        @can('achievements.manage')<button @click="tab = 'achievements'" :class="tab === 'achievements' ? 'bg-emerald-800 text-white' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-bold" type="button">الإنجازات</button>@endcan
    </nav>

    @can('courses.manage')
        <section x-show="tab === 'courses'" class="space-y-6">
            <form wire:submit="saveCourse" class="panel space-y-4">
                <div><p class="eyebrow">دورة جديدة</p><h2 class="section-title">تعريف الدورة</h2></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">المركز</span><select wire:model="courseCenterId" class="form-input"><option value="">اختر</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('courseCenterId')" /></label>
                    <label><span class="form-label">الفرع</span><select wire:model="courseBranchId" class="form-input"><option value="">كل المركز</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('courseBranchId')" /></label>
                    <label><span class="form-label">المدرب</span><select wire:model="courseInstructorId" class="form-input"><option value="">غير محدد</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select></label>
                    <label><span class="form-label">اسم الدورة</span><input wire:model="courseName" class="form-input"><x-input-error :messages="$errors->get('courseName')" /></label>
                    <label><span class="form-label">تاريخ البداية</span><input wire:model="courseStartsAt" type="date" class="form-input"><x-input-error :messages="$errors->get('courseStartsAt')" /></label>
                    <label><span class="form-label">تاريخ النهاية</span><input wire:model="courseEndsAt" type="date" class="form-input"><x-input-error :messages="$errors->get('courseEndsAt')" /></label>
                    <label><span class="form-label">الساعات</span><input wire:model="courseHours" type="number" min="0" step="0.5" class="form-input"></label>
                    <label><span class="form-label">الحالة</span><select wire:model="courseStatus" class="form-input">@foreach($courseStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
                </div>
                <label><span class="form-label">الوصف</span><textarea wire:model="courseDescription" rows="2" class="form-input"></textarea></label>
                <button class="btn-primary flex" type="submit">إنشاء الدورة</button>
            </form>
            <section class="panel"><div class="mb-5"><p class="eyebrow">السجل</p><h2 class="section-title">الدورات</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الدورة</th><th>المركز</th><th>المدرب</th><th>الفترة</th><th>الطلاب</th><th>الحالة</th></tr></thead><tbody>@forelse($courses as $course)<tr><td class="font-bold">{{ $course->name }}</td><td>{{ $course->center->name }}</td><td>{{ $course->instructor?->user?->name ?? '—' }}</td><td dir="ltr">{{ $course->starts_at->format('Y-m-d') }} — {{ $course->ends_at?->format('Y-m-d') ?? 'مفتوحة' }}</td><td>{{ $course->enrollments_count }}</td><td>{{ $course->status->label() }}</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-slate-400">لا توجد دورات بعد.</td></tr>@endforelse</tbody></table></div></section>
        </section>

        <section x-show="tab === 'enrollments'" x-cloak class="space-y-6">
            <form wire:submit="saveEnrollment" class="panel space-y-4"><div><p class="eyebrow">التحاق ونتيجة</p><h2 class="section-title">تسجيل الطالب في دورة</h2></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="form-label">الدورة</span><select wire:model="enrollmentCourseId" class="form-input"><option value="">اختر</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('enrollmentCourseId')" /></label>
                <label><span class="form-label">الطالب</span><select wire:model="enrollmentStudentId" class="form-input"><option value="">اختر</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }} · {{ $student->student_number }}</option>@endforeach</select><x-input-error :messages="$errors->get('enrollmentStudentId')" /></label>
                <label><span class="form-label">تاريخ التسجيل</span><input wire:model="enrollmentDate" type="date" class="form-input"></label>
                <label><span class="form-label">الحالة</span><select wire:model="enrollmentStatus" class="form-input">@foreach($enrollmentStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
                <label><span class="form-label">النتيجة /100</span><input wire:model="enrollmentResult" type="number" min="0" max="100" step="0.01" class="form-input"><x-input-error :messages="$errors->get('enrollmentResult')" /></label>
                <label><span class="form-label">التقدير</span><input wire:model="enrollmentGrade" class="form-input"></label>
                <label><span class="form-label">تاريخ الإكمال</span><input wire:model="enrollmentCompletedAt" type="date" class="form-input"></label>
                <label><span class="form-label">ملاحظات</span><input wire:model="enrollmentNotes" class="form-input"></label>
            </div><button class="btn-primary flex" type="submit">حفظ التسجيل والنتيجة</button></form>
            <section class="panel"><div class="mb-5"><h2 class="section-title">سجل المشاركات</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الدورة</th><th>الحالة</th><th>النتيجة</th><th>التقدير</th></tr></thead><tbody>@forelse($enrollments as $enrollment)<tr><td>{{ $enrollment->student->full_name }}</td><td>{{ $enrollment->course->name }}</td><td>{{ $enrollment->status->label() }}</td><td>{{ $enrollment->result ?? '—' }}</td><td>{{ $enrollment->grade ?? '—' }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-400">لا توجد مشاركات بعد.</td></tr>@endforelse</tbody></table></div></section>
        </section>
    @endcan

    @can('certificates.manage')
        <section x-show="tab === 'certificates'" x-cloak class="space-y-6">
            <form wire:submit="saveCertificate" class="panel space-y-4"><div><p class="eyebrow">توثيق الإنجاز</p><h2 class="section-title">إصدار شهادة</h2></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="form-label">الطالب</span><select wire:model="certificateStudentId" class="form-input"><option value="">اختر</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select><x-input-error :messages="$errors->get('certificateStudentId')" /></label>
                <label><span class="form-label">الدورة (اختياري)</span><select wire:model="certificateCourseId" class="form-input"><option value="">دون دورة</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach</select></label>
                <label><span class="form-label">اسم الشهادة</span><input wire:model="certificateName" class="form-input"><x-input-error :messages="$errors->get('certificateName')" /></label>
                <label><span class="form-label">الجهة المصدرة</span><input wire:model="certificateIssuer" class="form-input"><x-input-error :messages="$errors->get('certificateIssuer')" /></label>
                <label><span class="form-label">رقم الشهادة</span><input wire:model="certificateNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('certificateNumber')" /></label>
                <label><span class="form-label">تاريخ الإصدار</span><input wire:model="certificateIssuedAt" type="date" class="form-input"></label>
                <label><span class="form-label">تاريخ الانتهاء</span><input wire:model="certificateExpiresAt" type="date" class="form-input"></label>
                <label><span class="form-label">الدرجة</span><input wire:model="certificateGrade" class="form-input"></label>
                <label><span class="form-label">ملف الشهادة الخاص</span><input wire:model="certificateFile" type="file" accept=".pdf,image/*" class="form-input"><x-input-error :messages="$errors->get('certificateFile')" /></label>
            </div><label><span class="form-label">ملاحظات</span><textarea wire:model="certificateNotes" rows="2" class="form-input"></textarea></label><button class="btn-primary flex" type="submit">إصدار الشهادة</button></form>
            <section class="panel"><div class="mb-5"><h2 class="section-title">الشهادات الصادرة</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الشهادة</th><th>الجهة</th><th>التاريخ</th><th>الملف</th></tr></thead><tbody>@forelse($certificates as $certificate)<tr><td>{{ $certificate->student->full_name }}</td><td class="font-bold">{{ $certificate->name }}</td><td>{{ $certificate->issuer }}</td><td>{{ $certificate->issued_at->format('Y-m-d') }}</td><td>@if($certificate->privateFile)<a class="font-bold text-emerald-700" href="{{ route('private-files.show', $certificate->privateFile) }}">تنزيل</a>@else—@endif</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-400">لا توجد شهادات بعد.</td></tr>@endforelse</tbody></table></div></section>
        </section>
    @endcan

    @can('achievements.manage')
        <section x-show="tab === 'achievements'" x-cloak class="space-y-6">
            <form wire:submit="saveAchievement" class="panel space-y-4"><div><p class="eyebrow">تكريم وتقدم</p><h2 class="section-title">تسجيل إنجاز</h2><p class="page-subtitle">إنجازات إكمال السور والأجزاء تنشأ تلقائيًا، ويمكن إضافة المسابقات والتكريمات يدويًا.</p></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="form-label">الطالب</span><select wire:model="achievementStudentId" class="form-input"><option value="">اختر</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }}</option>@endforeach</select><x-input-error :messages="$errors->get('achievementStudentId')" /></label>
                <label><span class="form-label">النوع</span><select wire:model="achievementType" class="form-input">@foreach($achievementTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></label>
                <label><span class="form-label">العنوان</span><input wire:model="achievementTitle" class="form-input"><x-input-error :messages="$errors->get('achievementTitle')" /></label>
                <label><span class="form-label">التاريخ</span><input wire:model="achievementDate" type="date" class="form-input"></label>
                <label><span class="form-label">الجهة</span><input wire:model="achievementIssuer" class="form-input"></label>
            </div><label><span class="form-label">الوصف</span><textarea wire:model="achievementDescription" rows="2" class="form-input"></textarea></label><button class="btn-primary flex" type="submit">حفظ الإنجاز</button></form>
            <section class="panel"><div class="mb-5"><h2 class="section-title">سجل الإنجازات</h2></div><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">@forelse($achievements as $achievement)<article class="rounded-2xl border border-slate-100 p-4"><div class="flex justify-between gap-2"><div><p class="font-black text-emerald-950">{{ $achievement->title }}</p><p class="mt-1 text-sm text-slate-500">{{ $achievement->student->full_name }}</p></div><span class="badge-active">{{ $achievement->type->label() }}</span></div><p class="mt-3 text-xs text-slate-400">{{ $achievement->achieved_at->format('Y-m-d') }} · {{ $achievement->issuer ?? '—' }}</p></article>@empty<p class="col-span-full py-8 text-center text-slate-400">لا توجد إنجازات بعد.</p>@endforelse</div></section>
        </section>
    @endcan
</div>
