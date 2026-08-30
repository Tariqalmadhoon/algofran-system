<div class="space-y-6">
    <x-flash-messages inline consume />

    <header class="relative isolate overflow-hidden rounded-[2.25rem] border border-emerald-200/10 bg-[linear-gradient(125deg,#043c32_0%,#075b48_58%,#0f766e_100%)] p-6 text-white shadow-[0_30px_80px_-44px_rgba(4,70,56,.75)] sm:p-8">
        <div aria-hidden="true" class="absolute inset-0 -z-10 opacity-[.08]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1.5px);background-size:28px 28px"></div>
        <div aria-hidden="true" class="absolute -left-24 -top-28 -z-10 size-72 rounded-full bg-amber-300/10 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -bottom-32 right-1/3 -z-10 size-80 rounded-full border-[56px] border-emerald-200/[.07]"></div>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-4 flex items-center gap-3"><span class="grid size-12 place-items-center rounded-2xl border border-amber-200/20 bg-amber-200/10 text-amber-100 shadow-lg shadow-emerald-950/10"><x-nav-icon name="access" class="size-6" /></span><span class="text-xs font-black tracking-[.16em] text-amber-100">إدارة وصول مركزية وآمنة</span></div>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">الحسابات والصلاحيات</h1>
                <p class="mt-3 text-sm leading-7 text-emerald-50/75">أنشئ الحساب مرة واحدة، اختر طبقته، ثم عدّل الصلاحيات المباشرة من مكان واحد دون تعقيد.</p>
            </div>
            <button type="button" wire:click="$toggle('showCreateForm')" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-amber-100/60 bg-white px-5 text-sm font-black text-emerald-950 shadow-xl shadow-emerald-950/15 transition hover:-translate-y-0.5 hover:bg-amber-50">
                <span class="text-xl">+</span><span>{{ $showCreateForm ? 'إغلاق النموذج' : 'إضافة حساب جديد' }}</span>
            </button>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([['كل الحسابات', $accountStats['all']], ['الحسابات الفعالة', $accountStats['active']], ['المحفّظون', $accountStats['teachers']], ['مديرو النظام', $accountStats['admins']]] as [$label, $value])
                <div class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-amber-200/25 hover:bg-white/[.1]"><p class="text-2xl font-black text-white">{{ $value }}</p><p class="mt-1 text-xs font-bold text-emerald-50/65">{{ $label }}</p></div>
            @endforeach
        </div>
    </header>

    @if($showCreateForm)
        <section class="panel border-emerald-100/80 bg-gradient-to-b from-emerald-50/35 to-white" data-motion="reveal">
            <div class="mb-6 rounded-2xl border border-emerald-100/70 bg-white/80 p-4"><p class="eyebrow">خطوة واحدة واضحة</p><h2 class="section-title">إنشاء الحساب وتحديد طبقته</h2><p class="mt-2 text-sm leading-6 text-slate-500">الحساب الوظيفي أو حساب المحفّظ يحتاج إلى مركز. ويمكن جمع دور مدير المركز والمحفظ للحساب نفسه.</p></div>
            <form wire:submit="createAccount" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label><span class="form-label">الاسم الكامل</span><input wire:model="createName" class="form-input" required><x-input-error :messages="$errors->get('createName')" /></label>
                    <label><span class="form-label">البريد الإلكتروني</span><input wire:model="createEmail" class="form-input" type="email" dir="ltr" required><x-input-error :messages="$errors->get('createEmail')" /></label>
                    <label><span class="form-label">رقم الهاتف</span><input wire:model="createPhone" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('createPhone')" /></label>
                    <label><span class="form-label">المركز</span><select wire:model="createCenterId" class="form-input"><option value="">غير مرتبط بمركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('createCenterId')" /></label>
                    <label><span class="form-label">كلمة المرور</span><input wire:model="createPassword" class="form-input" type="password" autocomplete="new-password" required><x-input-error :messages="$errors->get('createPassword')" /></label>
                    <label><span class="form-label">تأكيد كلمة المرور</span><input wire:model="createPassword_confirmation" class="form-input" type="password" autocomplete="new-password" required></label>
                    <label><span class="form-label">المسمى الوظيفي</span><input wire:model="createJobTitle" class="form-input" placeholder="يُضبط تلقائيًا عند تركه فارغًا"></label>
                    <label><span class="form-label">تخصص المحفّظ</span><input wire:model="createSpecialization" class="form-input" placeholder="حفظ وتجويد"></label>
                    <label><span class="form-label">رقم هوية المحفّظ</span><input wire:model="createTeacherIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('createTeacherIdentityNumber')" /></label>
                </div>

                <div>
                    <p class="form-label">طبقات الحساب</p>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach($roles as $roleName => $role)
                            <label class="relative flex cursor-pointer gap-3 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-950/5 {{ in_array($roleName, $createRoles, true) ? 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-100/70' : 'border-slate-200 bg-white' }}">
                                <input wire:model.live="createRoles" type="checkbox" value="{{ $roleName }}" class="mt-1 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500">
                                <span><span class="block text-sm font-black text-slate-800">{{ $role['label'] }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ $role['description'] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('createRoles')" />
                </div>

                <div class="flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm leading-6 text-emerald-900"><strong>لاحقًا:</strong> يمكنك فتح الحساب وتخصيص صلاحيات إضافية دقيقة. وإذا كان محفّظًا فسيظهر مباشرة في شاشة إسناد الحلقات.</p><button wire:loading.attr="disabled" wire:target="createAccount" class="btn-primary shrink-0" type="submit"><span wire:loading.remove wire:target="createAccount">إنشاء الحساب</span><span wire:loading wire:target="createAccount">جارٍ الإنشاء…</span></button></div>
            </form>
        </section>
    @endif

    <section class="panel border-slate-200/70 bg-slate-50/35">
        <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200/70 bg-white p-4 lg:flex-row lg:items-end lg:justify-between"><div><p class="eyebrow">دليل الحسابات</p><h2 class="section-title">الحسابات الحالية</h2></div><div class="grid gap-3 sm:grid-cols-2"><label><span class="form-label">بحث</span><input wire:model.live.debounce.350ms="search" class="form-input" placeholder="الاسم أو البريد أو الهاتف"></label><label><span class="form-label">الحالة</span><select wire:model.live="statusFilter" class="form-input"><option value="active">الفعالة</option><option value="inactive">المعطلة</option><option value="all">الكل</option></select></label></div></div>
        <div class="grid gap-3 xl:grid-cols-2">
            @forelse($users as $user)
                <article wire:key="access-user-{{ $user->id }}" class="group flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 transition duration-300 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-950/5 sm:flex-row sm:items-center">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-teal-100 text-lg font-black text-emerald-800 shadow-sm ring-2 ring-amber-100/50">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="truncate font-black text-slate-800">{{ $user->name }}</h3><span class="{{ $user->active ? 'badge-active' : 'badge-inactive' }}">{{ $user->active ? 'فعال' : 'معطل' }}</span></div><p class="mt-1 truncate text-xs text-slate-500" dir="ltr">{{ $user->email }}</p><div class="mt-2 flex flex-wrap gap-1.5">@foreach($user->roles as $role)<span class="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">{{ $roles[$role->name]['label'] ?? $role->name }}</span>@endforeach @if($user->permissions->isNotEmpty())<span class="rounded-full border border-amber-100 bg-amber-50 px-2 py-1 text-[10px] font-black text-amber-700">+ {{ $user->permissions->count() }} مباشرة</span>@endif</div></div>
                    <div class="flex items-center gap-2 sm:flex-col sm:items-end"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-500">{{ $user->staffProfile?->center?->name ?? $user->teacherProfile?->center?->name ?? 'دون مركز' }}</span><button wire:click="selectUser({{ $user->id }})" type="button" class="btn-secondary shrink-0 border-emerald-200 text-emerald-800">إدارة الحساب</button></div>
                </article>
            @empty
                <div class="empty-state min-h-52 xl:col-span-2"><div><p class="font-black text-slate-600">لا توجد حسابات مطابقة</p><p class="mt-1 text-sm">غيّر البحث أو عامل تصفية الحالة.</p></div></div>
            @endforelse
        </div>
        <div class="mt-5">{{ $users->links() }}</div>
    </section>

    @if($selectedUser)
        <section class="panel border-emerald-200/80 bg-gradient-to-b from-emerald-50/25 to-white shadow-[0_24px_70px_-45px_rgba(6,78,59,.35)]" data-motion="reveal">
            <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p class="eyebrow">تحكم دقيق</p><h2 class="section-title">{{ $selectedUser->name }}</h2><p class="mt-1 text-xs text-slate-500" dir="ltr">{{ $selectedUser->email }}</p></div><button wire:click="closeEditor" type="button" class="btn-ghost border border-slate-200 bg-slate-50 px-3">إغلاق</button></div>
            <form wire:submit="saveAccess" class="space-y-6">
                <div class="grid gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-3"><label><span class="form-label">حالة الدخول</span><select wire:model="editActive" class="form-input"><option value="1">فعال</option><option value="0">معطل</option></select></label><label><span class="form-label">المركز</span><select wire:model="editCenterId" class="form-input"><option value="">دون مركز</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('editCenterId')" /></label><label><span class="form-label">المسمى الوظيفي</span><input wire:model="editJobTitle" class="form-input"></label></div>

                <div class="rounded-2xl border border-emerald-100/80 bg-white p-4"><div class="mb-4"><p class="text-sm font-black text-emerald-950">طبقات الصلاحيات</p><p class="mt-1 text-xs text-slate-500">يمكن الجمع بين «مدير مركز» و«محفّظ» للحساب نفسه.</p></div><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">@foreach($roles as $roleName => $role)<label class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 {{ in_array($roleName, $editRoles, true) ? 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-100/60' : 'border-slate-200 bg-white' }}"><input wire:model.live="editRoles" value="{{ $roleName }}" type="checkbox" class="mt-1 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"><span><span class="block text-sm font-black text-slate-800">{{ $role['label'] }}</span><span class="mt-1 block text-[11px] leading-5 text-slate-500">{{ $role['description'] }}</span></span></label>@endforeach</div><x-input-error :messages="$errors->get('editRoles')" /></div>

                @if(in_array('teacher', $editRoles, true))
                    <div class="grid gap-4 md:grid-cols-2"><label><span class="form-label">تخصص المحفّظ</span><input wire:model="editSpecialization" class="form-input" placeholder="حفظ وتجويد القرآن الكريم"></label><label><span class="form-label">رقم هوية المحفّظ</span><input wire:model="editTeacherIdentityNumber" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('editTeacherIdentityNumber')" /></label></div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm leading-6 text-emerald-900">@if($selectedUser->teacherProfile?->assignments?->whereNull('ends_at')->isNotEmpty())الحلقات المسندة: <strong>{{ $selectedUser->teacherProfile->assignments->whereNull('ends_at')->pluck('halaqa.name')->filter()->join('، ') }}</strong>@else الحساب مفعّل كمحفّظ. بعد الحفظ يمكنك <a class="font-black underline" href="{{ route('organization.index') }}">إسناد حلقة له من الهيكلية</a>. @endif</div>
                @endif

                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-slate-50/45 p-4"><div><p class="text-sm font-black text-slate-800">صلاحيات إضافية مباشرة</p><p class="mt-1 text-xs leading-5 text-slate-500">استخدمها فقط للاستثناءات؛ الطبقة المختارة تمنح صلاحياتها الأساسية تلقائيًا.</p></div>@foreach($permissionGroups as $group => $permissions)<div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm"><p class="mb-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $group }}</p><div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">@foreach($permissions as $permission => $label)<label class="flex cursor-pointer items-center gap-2 rounded-xl border border-transparent bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-100 hover:bg-emerald-50"><input wire:model="editPermissions" type="checkbox" value="{{ $permission }}" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"><span>{{ $label }}</span></label>@endforeach</div></div>@endforeach<x-input-error :messages="$errors->get('editPermissions')" /></div>

                <div class="flex justify-end rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4"><button wire:loading.attr="disabled" wire:target="saveAccess" class="btn-primary min-w-48" type="submit"><span wire:loading.remove wire:target="saveAccess">حفظ الصلاحيات والحالة</span><span wire:loading wire:target="saveAccess">جارٍ التطبيق…</span></button></div>
            </form>

            <form wire:submit="resetSelectedPassword" class="mt-7 rounded-2xl border border-amber-200/70 bg-amber-50/45 p-4 sm:p-5"><div class="mb-4"><p class="text-sm font-black text-amber-950">تعيين كلمة مرور جديدة</p><p class="mt-1 text-xs leading-5 text-amber-900/60">سيتم إلغاء جلسات الحساب ورموزه السابقة فور الحفظ.</p></div><div class="grid gap-3 md:grid-cols-[1fr_1fr_auto]"><label><span class="form-label">كلمة المرور الجديدة</span><input wire:model="newPassword" class="form-input" type="password" autocomplete="new-password"><x-input-error :messages="$errors->get('newPassword')" /></label><label><span class="form-label">التأكيد</span><input wire:model="newPassword_confirmation" class="form-input" type="password" autocomplete="new-password"></label><div class="flex items-end"><button class="btn-secondary w-full border-amber-200 text-amber-900 hover:bg-amber-100" type="submit">تحديث كلمة المرور</button></div></div></form>
        </section>
    @endif
</div>
