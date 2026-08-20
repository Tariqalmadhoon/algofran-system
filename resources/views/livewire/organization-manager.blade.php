<div class="space-y-8">
    @php
        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $roles = [
            'center-manager' => 'مدير مركز', 'academic-supervisor' => 'مشرف أكاديمي',
            'registrar' => 'مسجل', 'website-editor' => 'محرر الموقع', 'report-viewer' => 'عارض تقارير',
        ];
    @endphp

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $centers->count() }}</p><p class="text-sm text-slate-500">مركز</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $branches->count() }}</p><p class="text-sm text-slate-500">فرع</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $halaqas->count() }}</p><p class="text-sm text-slate-500">حلقة</p></div></article>
        <article class="stat-card"><div><p class="text-3xl font-black text-emerald-950">{{ $teachers->count() }}</p><p class="text-sm text-slate-500">محفظ</p></div></article>
    </section>

    @if (auth()->user()->can('organization.manage') || auth()->user()->can('users.manage') || auth()->user()->can('halaqas.manage'))
        <section class="panel">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="eyebrow">إضافة جديدة</p><h2 class="section-title">توسيع الهيكل التنظيمي</h2></div>
                <div class="flex flex-wrap gap-2">
                    @can('organization.manage')
                        <button wire:click="$set('activeForm', 'center')" class="{{ $activeForm === 'center' ? 'btn-primary' : 'btn-secondary' }}" type="button">مركز</button>
                        <button wire:click="$set('activeForm', 'branch')" class="{{ $activeForm === 'branch' ? 'btn-primary' : 'btn-secondary' }}" type="button">فرع</button>
                    @endcan
                    @can('users.manage')
                        <button wire:click="$set('activeForm', 'staff')" class="{{ $activeForm === 'staff' ? 'btn-primary' : 'btn-secondary' }}" type="button">موظف</button>
                        <button wire:click="$set('activeForm', 'teacher')" class="{{ $activeForm === 'teacher' ? 'btn-primary' : 'btn-secondary' }}" type="button">محفظ</button>
                    @endcan
                    @can('halaqas.manage')
                        <button wire:click="$set('activeForm', 'halaqa')" class="{{ $activeForm === 'halaqa' ? 'btn-primary' : 'btn-secondary' }}" type="button">حلقة</button>
                        <button wire:click="$set('activeForm', 'schedule')" class="{{ $activeForm === 'schedule' ? 'btn-primary' : 'btn-secondary' }}" type="button">موعد</button>
                        <button wire:click="$set('activeForm', 'assignment')" class="{{ $activeForm === 'assignment' ? 'btn-primary' : 'btn-secondary' }}" type="button">إسناد</button>
                    @endcan
                </div>
            </div>

            @if ($activeForm === 'center' && auth()->user()->can('organization.manage'))
                <form wire:submit="saveCenter" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">اسم المركز</label><input wire:model="centerName" class="form-input" required><x-input-error :messages="$errors->get('centerName')" /></div>
                    <div><label class="form-label">الرمز</label><input wire:model="centerCode" class="form-input" dir="ltr" required><x-input-error :messages="$errors->get('centerCode')" /></div>
                    <div><label class="form-label">الهاتف</label><input wire:model="centerPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('centerPhone')" /></div>
                    <div><label class="form-label">البريد الإلكتروني</label><input wire:model="centerEmail" class="form-input" type="email" dir="ltr"><x-input-error :messages="$errors->get('centerEmail')" /></div>
                    <div class="md:col-span-2"><label class="form-label">العنوان</label><input wire:model="centerAddress" class="form-input"><x-input-error :messages="$errors->get('centerAddress')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ المركز</button></div>
                </form>
            @elseif ($activeForm === 'branch' && auth()->user()->can('organization.manage'))
                <form wire:submit="saveBranch" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">المركز</label><select wire:model="branchCenterId" class="form-input" required><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('branchCenterId')" /></div>
                    <div><label class="form-label">اسم الفرع</label><input wire:model="branchName" class="form-input" required><x-input-error :messages="$errors->get('branchName')" /></div>
                    <div><label class="form-label">الرمز</label><input wire:model="branchCode" class="form-input" dir="ltr" required><x-input-error :messages="$errors->get('branchCode')" /></div>
                    <div><label class="form-label">الهاتف</label><input wire:model="branchPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('branchPhone')" /></div>
                    <div><label class="form-label">العنوان</label><input wire:model="branchAddress" class="form-input"><x-input-error :messages="$errors->get('branchAddress')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ الفرع</button></div>
                </form>
            @elseif (in_array($activeForm, ['staff', 'teacher'], true) && auth()->user()->can('users.manage'))
                <form wire:submit="{{ $activeForm === 'teacher' ? 'saveTeacher' : 'saveStaff' }}" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">الاسم الكامل</label><input wire:model="personName" class="form-input" required><x-input-error :messages="$errors->get('personName')" /></div>
                    <div><label class="form-label">البريد الإلكتروني</label><input wire:model="personEmail" class="form-input" type="email" dir="ltr" required><x-input-error :messages="$errors->get('personEmail')" /></div>
                    <div><label class="form-label">رقم التواصل</label><input wire:model="personPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('personPhone')" /></div>
                    <div><label class="form-label">المركز</label><select wire:model="personCenterId" class="form-input" required><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('personCenterId')" /></div>
                    <div><label class="form-label">الفرع</label><select wire:model="personBranchId" class="form-input"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->center->name }} — {{ $branch->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('personBranchId')" /></div>
                    <div><label class="form-label">الرقم الوظيفي</label><input wire:model="employeeNumber" class="form-input" dir="ltr" required><x-input-error :messages="$errors->get('employeeNumber')" /></div>
                    @if ($activeForm === 'teacher')
                        <div><label class="form-label">التخصص</label><input wire:model="specialization" class="form-input"><x-input-error :messages="$errors->get('specialization')" /></div>
                    @else
                        <div><label class="form-label">المسمى الوظيفي</label><input wire:model="jobTitle" class="form-input" required><x-input-error :messages="$errors->get('jobTitle')" /></div>
                        <div><label class="form-label">الدور</label><select wire:model="staffRole" class="form-input">@foreach($roles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><x-input-error :messages="$errors->get('staffRole')" /></div>
                    @endif
                    <div><label class="form-label">تاريخ التعيين</label><input wire:model="hiredAt" class="form-input" type="date"><x-input-error :messages="$errors->get('hiredAt')" /></div>
                    <div><label class="form-label">كلمة المرور المؤقتة</label><input wire:model="personPassword" class="form-input" type="password" required><x-input-error :messages="$errors->get('personPassword')" /></div>
                    <div><label class="form-label">تأكيد كلمة المرور</label><input wire:model="personPassword_confirmation" class="form-input" type="password" required></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ {{ $activeForm === 'teacher' ? 'المحفظ' : 'الموظف' }}</button></div>
                </form>
            @elseif ($activeForm === 'halaqa' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveHalaqa" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">المركز</label><select wire:model="halaqaCenterId" class="form-input" required><option value="">اختر المركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaCenterId')" /></div>
                    <div><label class="form-label">الفرع</label><select wire:model="halaqaBranchId" class="form-input" required><option value="">اختر الفرع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('halaqaBranchId')" /></div>
                    <div><label class="form-label">اسم الحلقة</label><input wire:model="halaqaName" class="form-input" required><x-input-error :messages="$errors->get('halaqaName')" /></div>
                    <div><label class="form-label">الرمز</label><input wire:model="halaqaCode" class="form-input" dir="ltr" required><x-input-error :messages="$errors->get('halaqaCode')" /></div>
                    <div><label class="form-label">البرنامج</label><input wire:model="halaqaProgram" class="form-input"><x-input-error :messages="$errors->get('halaqaProgram')" /></div>
                    <div><label class="form-label">السعة</label><input wire:model="halaqaCapacity" class="form-input" type="number" min="1" max="500"><x-input-error :messages="$errors->get('halaqaCapacity')" /></div>
                    <div><label class="form-label">الغرفة</label><input wire:model="halaqaRoom" class="form-input"><x-input-error :messages="$errors->get('halaqaRoom')" /></div>
                    <div><label class="form-label">تاريخ البداية</label><input wire:model="halaqaStartDate" class="form-input" type="date"><x-input-error :messages="$errors->get('halaqaStartDate')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">حفظ الحلقة</button></div>
                </form>
            @elseif ($activeForm === 'schedule' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveSchedule" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><label class="form-label">الحلقة</label><select wire:model="scheduleHalaqaId" class="form-input" required><option value="">اختر الحلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }} — {{ $halaqa->branch->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('scheduleHalaqaId')" /></div>
                    <div><label class="form-label">اليوم</label><select wire:model="weekday" class="form-input">@foreach($days as $key => $day)<option value="{{ $key }}">{{ $day }}</option>@endforeach</select></div>
                    <div><label class="form-label">من</label><input wire:model="scheduleStartsAt" class="form-input" type="time" required><x-input-error :messages="$errors->get('scheduleStartsAt')" /></div>
                    <div><label class="form-label">إلى</label><input wire:model="scheduleEndsAt" class="form-input" type="time" required><x-input-error :messages="$errors->get('scheduleEndsAt')" /></div>
                    <div><label class="form-label">الغرفة</label><input wire:model="scheduleRoom" class="form-input"><x-input-error :messages="$errors->get('scheduleRoom')" /></div>
                    <div class="flex items-end"><button wire:loading.attr="disabled" class="btn-primary w-full" type="submit">إضافة الموعد</button></div>
                </form>
            @elseif ($activeForm === 'assignment' && auth()->user()->can('halaqas.manage'))
                <form wire:submit="saveAssignment" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="form-label">الحلقة</label><select wire:model="assignmentHalaqaId" class="form-input" required><option value="">اختر الحلقة</option>@foreach($halaqas as $halaqa)<option value="{{ $halaqa->id }}">{{ $halaqa->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('assignmentHalaqaId')" /></div>
                    <div><label class="form-label">المحفظ</label><select wire:model="assignmentTeacherId" class="form-input" required><option value="">اختر المحفظ</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('assignmentTeacherId')" /></div>
                    <div><label class="form-label">نوع الإسناد</label><select wire:model="assignmentRole" class="form-input"><option value="primary">أساسي</option><option value="assistant">مساعد</option></select></div>
                    <div><label class="form-label">من تاريخ</label><input wire:model="assignmentStartsAt" class="form-input" type="date" required><x-input-error :messages="$errors->get('assignmentStartsAt')" /></div>
                    <div class="xl:col-span-4"><button wire:loading.attr="disabled" class="btn-primary" type="submit">حفظ الإسناد</button></div>
                </form>
            @endif
        </section>
    @endif

    <section class="panel">
        <div class="mb-5"><p class="eyebrow">المؤسسة</p><h2 class="section-title">المراكز والفروع</h2></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>النوع</th><th>الاسم</th><th>الرمز</th><th>التبعية</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                    @forelse($centers as $center)
                        <tr><td>مركز</td><td class="font-bold">{{ $center->name }}</td><td dir="ltr">{{ $center->code }}</td><td>{{ $center->branches_count }} فرع</td><td><span class="{{ $center->active ? 'badge-active' : 'badge-inactive' }}">{{ $center->active ? 'نشط' : 'متوقف' }}</span></td><td>@can('organization.manage')<button wire:click="toggleActive('center', {{ $center->id }})" class="text-xs font-bold text-emerald-700">تبديل الحالة</button>@endcan</td></tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-400">لا توجد مراكز بعد.</td></tr>
                    @endforelse
                    @foreach($branches as $branch)
                        <tr><td>فرع</td><td class="font-bold">{{ $branch->name }}</td><td dir="ltr">{{ $branch->code }}</td><td>{{ $branch->center->name }}</td><td><span class="{{ $branch->active ? 'badge-active' : 'badge-inactive' }}">{{ $branch->active ? 'نشط' : 'متوقف' }}</span></td><td>@can('organization.manage')<button wire:click="toggleActive('branch', {{ $branch->id }})" class="text-xs font-bold text-emerald-700">تبديل الحالة</button>@endcan</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="mb-5"><p class="eyebrow">الحلقات</p><h2 class="section-title">الحلقات والجداول والمحفظون</h2></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>الحلقة</th><th>الفرع</th><th>المحفظ الأساسي</th><th>المواعيد</th><th>السعة</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                    @forelse($halaqas as $halaqa)
                        <tr>
                            <td><span class="font-bold">{{ $halaqa->name }}</span><span class="mr-2 text-xs text-slate-400" dir="ltr">{{ $halaqa->code }}</span></td>
                            <td>{{ $halaqa->branch->name }}</td>
                            <td>{{ $halaqa->primaryTeacher?->user?->name ?? 'غير مسند' }}</td>
                            <td>@forelse($halaqa->schedules as $schedule)<span class="ml-2 inline-block text-xs">{{ $days[$schedule->weekday] }} {{ substr($schedule->starts_at, 0, 5) }}</span>@empty—@endforelse</td>
                            <td>{{ $halaqa->capacity }}</td>
                            <td><span class="{{ $halaqa->active ? 'badge-active' : 'badge-inactive' }}">{{ $halaqa->active ? 'نشطة' : 'متوقفة' }}</span></td>
                            <td>@can('organization.manage')<button wire:click="toggleActive('halaqa', {{ $halaqa->id }})" class="text-xs font-bold text-emerald-700">تبديل الحالة</button>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">لا توجد حلقات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="mb-5"><p class="eyebrow">فريق العمل</p><h2 class="section-title">المحفظون</h2></div>
            <div class="space-y-3">
                @forelse($teachers as $teacher)
                    <article class="flex items-center justify-between rounded-2xl border border-slate-100 p-4"><div><p class="font-bold">{{ $teacher->user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $teacher->center->name }}{{ $teacher->branch ? ' — '.$teacher->branch->name : '' }}</p></div><span class="text-xs font-semibold text-slate-500" dir="ltr">{{ $teacher->employee_number }}</span></article>
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
