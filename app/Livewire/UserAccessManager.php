<?php

namespace App\Livewire;

use App\Models\Center;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class UserAccessManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    public bool $showCreateForm = false;

    public string $createName = '';

    public string $createEmail = '';

    public string $createPhone = '';

    public string $createPassword = '';

    public string $createPassword_confirmation = '';

    public array $createRoles = ['teacher'];

    public array $createPermissions = [];

    public ?int $createCenterId = null;

    public string $createJobTitle = '';

    public string $createSpecialization = '';

    public string $createTeacherIdentityNumber = '';

    public ?int $selectedUserId = null;

    public array $editRoles = [];

    public array $editPermissions = [];

    public bool $editActive = true;

    public ?int $editCenterId = null;

    public string $editJobTitle = '';

    public string $editSpecialization = '';

    public string $editTeacherIdentityNumber = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function createAccount(AccessControlService $access): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $data = $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'createPhone' => ['nullable', 'string', 'max:30'],
            'createPassword' => ['required', 'confirmed', Password::defaults()],
            'createRoles' => ['required', 'array', 'min:1'],
            'createRoles.*' => ['required', Rule::in(array_keys(config('access_control.roles', [])))],
            'createPermissions' => ['array'],
            'createPermissions.*' => ['required', Rule::in($this->permissionNames())],
            'createCenterId' => [Rule::requiredIf(fn (): bool => $this->rolesNeedCenter($this->createRoles)), 'nullable', 'integer', 'exists:centers,id'],
            'createJobTitle' => ['nullable', 'string', 'max:255'],
            'createSpecialization' => ['nullable', 'string', 'max:255'],
            'createTeacherIdentityNumber' => ['nullable', 'string', 'max:50', 'unique:teacher_profiles,identity_number'],
        ]);

        $user = $access->createAccount([
            'name' => $data['createName'],
            'email' => $data['createEmail'],
            'phone' => $data['createPhone'] ?: null,
            'password' => $data['createPassword'],
            'roles' => $data['createRoles'],
            'permissions' => $data['createPermissions'],
            'center_id' => $data['createCenterId'],
            'job_title' => $data['createJobTitle'] ?: null,
            'specialization' => $data['createSpecialization'] ?: null,
            'teacher_identity_number' => $data['createTeacherIdentityNumber'] ?: null,
        ], auth()->user());

        $this->resetCreateForm();
        $this->selectedUserId = $user->id;
        $this->loadSelectedUser();
        session()->flash('success', 'تم إنشاء الحساب وتطبيق طبقته وصلاحياته بنجاح.');
    }

    public function selectUser(int $userId): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->selectedUserId = User::query()->whereNull('archived_at')->findOrFail($userId)->id;
        $this->loadSelectedUser();
        $this->resetValidation();
    }

    public function closeEditor(): void
    {
        $this->reset(
            'selectedUserId',
            'editRoles',
            'editPermissions',
            'editCenterId',
            'editJobTitle',
            'editSpecialization',
            'editTeacherIdentityNumber',
            'newPassword',
            'newPassword_confirmation',
        );
        $this->editActive = true;
        $this->resetValidation();
    }

    public function saveAccess(AccessControlService $access): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $target = User::query()->whereNull('archived_at')->with('teacherProfile')->findOrFail($this->selectedUserId);
        $data = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'editRoles' => ['required', 'array', 'min:1'],
            'editRoles.*' => ['required', Rule::in(array_keys(config('access_control.roles', [])))],
            'editPermissions' => ['array'],
            'editPermissions.*' => ['required', Rule::in($this->permissionNames())],
            'editActive' => ['boolean'],
            'editCenterId' => [Rule::requiredIf(fn (): bool => $this->rolesNeedCenter($this->editRoles)), 'nullable', 'integer', 'exists:centers,id'],
            'editJobTitle' => ['nullable', 'string', 'max:255'],
            'editSpecialization' => ['nullable', 'string', 'max:255'],
            'editTeacherIdentityNumber' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('teacher_profiles', 'identity_number')->ignore($target->teacherProfile?->id),
            ],
        ]);

        $access->updateAccess($target, [
            'roles' => $data['editRoles'],
            'permissions' => $data['editPermissions'],
            'active' => $data['editActive'],
            'center_id' => $data['editCenterId'],
            'job_title' => $data['editJobTitle'] ?: null,
            'specialization' => $data['editSpecialization'] ?: null,
            'teacher_identity_number' => $data['editTeacherIdentityNumber'] ?: null,
        ], auth()->user());

        $this->loadSelectedUser();
        session()->flash('success', 'تم تحديث حالة الحساب وطبقات الصلاحيات فورًا.');
    }

    public function resetSelectedPassword(AccessControlService $access): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $data = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'newPassword' => ['required', 'confirmed', Password::defaults()],
        ]);
        $target = User::query()->whereNull('archived_at')->findOrFail($data['selectedUserId']);
        $access->resetPassword($target, $data['newPassword'], auth()->user());
        $this->reset('newPassword', 'newPassword_confirmation');
        session()->flash('success', 'تم تعيين كلمة المرور الجديدة وإلغاء الجلسات السابقة للحساب.');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $users = User::query()
            ->whereNull('archived_at')
            ->with([
                'roles:id,name',
                'permissions:id,name',
                'staffProfile.center:id,name',
                'teacherProfile.center:id,name',
            ])
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('email', 'like', '%'.trim($this->search).'%')
                ->orWhere('phone', 'like', '%'.trim($this->search).'%')))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('active', false))
            ->latest('id')
            ->paginate(12);

        return view('livewire.user-access-manager', [
            'users' => $users,
            'roles' => config('access_control.roles', []),
            'permissionGroups' => config('access_control.permission_groups', []),
            'centers' => Center::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'selectedUser' => $this->selectedUserId
                ? User::query()->whereNull('archived_at')->with(['roles.permissions', 'permissions', 'staffProfile.center', 'teacherProfile.center', 'teacherProfile.assignments.halaqa'])->find($this->selectedUserId)
                : null,
            'accountStats' => [
                'all' => User::query()->whereNull('archived_at')->count(),
                'active' => User::query()->whereNull('archived_at')->where('active', true)->count(),
                'teachers' => User::query()->whereNull('archived_at')->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->count(),
                'admins' => User::query()->whereNull('archived_at')->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))->count(),
            ],
        ]);
    }

    private function loadSelectedUser(): void
    {
        $user = User::query()->whereNull('archived_at')->with(['roles', 'permissions', 'staffProfile', 'teacherProfile'])->findOrFail($this->selectedUserId);
        $this->editRoles = $user->getRoleNames()->values()->all();
        $this->editPermissions = $user->getDirectPermissions()->pluck('name')->values()->all();
        $this->editActive = (bool) $user->active;
        $this->editCenterId = $user->staffProfile?->center_id ?? $user->teacherProfile?->center_id;
        $this->editJobTitle = $user->staffProfile?->job_title ?? '';
        $this->editSpecialization = $user->teacherProfile?->specialization ?? '';
        $this->editTeacherIdentityNumber = $user->teacherProfile?->identity_number ?? '';
        $this->reset('newPassword', 'newPassword_confirmation');
    }

    private function resetCreateForm(): void
    {
        $this->reset(
            'createName',
            'createEmail',
            'createPhone',
            'createPassword',
            'createPassword_confirmation',
            'createPermissions',
            'createCenterId',
            'createJobTitle',
            'createSpecialization',
            'createTeacherIdentityNumber',
        );
        $this->createRoles = ['teacher'];
        $this->showCreateForm = false;
        $this->resetValidation();
    }

    /** @return list<string> */
    private function permissionNames(): array
    {
        return collect(config('access_control.permission_groups', []))
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values()
            ->all();
    }

    private function rolesNeedCenter(array $roles): bool
    {
        return array_intersect($roles, [
            'center-manager',
            'teacher',
            'academic-supervisor',
            'registrar',
            'website-editor',
            'report-viewer',
        ]) !== [];
    }
}
