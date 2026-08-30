<?php

namespace App\Livewire;

use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrganizationManager extends Component
{
    public string $activeForm = 'center';

    public string $centerName = '';

    public string $centerPhone = '';

    public string $centerEmail = '';

    public string $centerAddress = '';

    public string $personName = '';

    public string $personEmail = '';

    public string $personPhone = '';

    public string $personPassword = '';

    public string $personPassword_confirmation = '';

    public ?int $personCenterId = null;

    public string $jobTitle = '';

    public string $staffRole = 'registrar';

    public string $specialization = '';

    public ?int $existingTeacherUserId = null;

    public ?int $managerTeacherHalaqaId = null;

    public string $managerTeacherRole = 'primary';

    public string $managerTeacherStartsAt = '';

    public string $hiredAt = '';

    public ?int $halaqaCenterId = null;

    public string $halaqaName = '';

    public int $halaqaCapacity = 20;

    public string $halaqaStartDate = '';

    public ?int $scheduleHalaqaId = null;

    public int $weekday = 0;

    public string $scheduleStartsAt = '';

    public string $scheduleEndsAt = '';

    public string $scheduleRoom = '';

    public ?int $assignmentHalaqaId = null;

    public ?int $assignmentTeacherId = null;

    public string $assignmentRole = 'primary';

    public string $assignmentStartsAt = '';

    public function mount(): void
    {
        Gate::authorize('organization.view');
        $this->assignmentStartsAt = today()->toDateString();
        $this->managerTeacherStartsAt = today()->toDateString();
    }

    public function saveCenter(OrganizationService $service): void
    {
        $this->authorizeSystemAdministrator();
        $data = $this->validate([
            'centerName' => ['required', 'string', 'max:255'],
            'centerPhone' => ['nullable', 'string', 'max:30'],
            'centerEmail' => ['nullable', 'email', 'max:255'],
            'centerAddress' => ['nullable', 'string', 'max:1000'],
        ]);
        $center = $service->createCenter([
            'name' => $data['centerName'],
            'phone' => $data['centerPhone'] ?: null, 'email' => $data['centerEmail'] ?: null,
            'address' => $data['centerAddress'] ?: null,
        ]);
        $this->reset('centerName', 'centerPhone', 'centerEmail', 'centerAddress');
        $this->saved("تم إنشاء المركز بالرمز {$center->code} بنجاح.");
    }

    public function saveStaff(OrganizationService $service): void
    {
        $this->authorizeSystemAdministrator();
        $data = $this->validate($this->personRules(false));
        $staff = $service->createStaff($this->personPayload($data) + [
            'job_title' => $data['jobTitle'],
            'role' => $data['staffRole'],
        ]);
        $this->resetPerson();
        $this->saved("تم إنشاء حساب الموظف بالرقم {$staff->employee_number} بنجاح.");
    }

    public function saveTeacher(OrganizationService $service): void
    {
        $this->authorizeSystemAdministrator();
        $data = $this->validate($this->personRules(true));
        $teacher = $service->createTeacher($this->personPayload($data) + ['specialization' => $data['specialization'] ?: null]);
        $this->resetPerson();
        $this->saved("تم إنشاء حساب المحفظ بالرقم {$teacher->employee_number} بنجاح.");
    }

    public function saveManagerAsTeacher(OrganizationService $service): void
    {
        $this->authorizeSystemAdministrator();
        $data = $this->validate([
            'existingTeacherUserId' => ['required', 'exists:users,id'],
            'managerTeacherHalaqaId' => ['required', 'exists:halaqas,id'],
            'managerTeacherRole' => ['required', Rule::in(['primary', 'assistant'])],
            'managerTeacherStartsAt' => ['required', 'date', 'before_or_equal:today'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'hiredAt' => ['nullable', 'date'],
        ]);
        $manager = User::query()->findOrFail($data['existingTeacherUserId']);
        try {
            $assignment = $service->activateManagerAsTeacherAndAssign($manager, [
                'halaqa_id' => $data['managerTeacherHalaqaId'],
                'role' => $data['managerTeacherRole'],
                'starts_at' => $data['managerTeacherStartsAt'],
                'specialization' => $data['specialization'] ?: null,
                'hired_at' => $data['hiredAt'] ?: null,
            ]);
        } catch (ValidationException $exception) {
            $messages = $exception->errors();
            if (isset($messages['halaqa_id'])) {
                $messages['managerTeacherHalaqaId'] = $messages['halaqa_id'];
                unset($messages['halaqa_id']);
            }

            throw ValidationException::withMessages($messages);
        }
        $assignment->load(['teacher', 'halaqa.currentStudents']);
        $teacher = $assignment->teacher;
        $halaqa = $assignment->halaqa;
        $studentsCount = $halaqa->currentStudents->count();

        $this->reset('existingTeacherUserId', 'managerTeacherHalaqaId', 'specialization', 'hiredAt');
        $this->managerTeacherRole = 'primary';
        $this->managerTeacherStartsAt = today()->toDateString();
        $this->saved("تم تفعيل المدير كمحفّظ بالرقم {$teacher->employee_number} وإسناده إلى {$halaqa->name} في عملية واحدة. تضم الحلقة {$studentsCount} طالبًا حاليًا.");
    }

    public function updatedExistingTeacherUserId(): void
    {
        $manager = $this->existingTeacherUserId
            ? User::query()->with('staffProfile')->find($this->existingTeacherUserId)
            : null;

        $this->managerTeacherHalaqaId = $manager?->staffProfile
            ? Halaqa::query()
                ->where('center_id', $manager->staffProfile->center_id)
                ->where('active', true)
                ->orderBy('name')
                ->value('id')
            : null;
    }

    public function saveHalaqa(OrganizationService $service): void
    {
        Gate::authorize('halaqas.manage');
        $data = $this->validate([
            'halaqaCenterId' => ['required', 'exists:centers,id'],
            'halaqaName' => ['required', 'string', 'max:255'],
            'halaqaCapacity' => ['required', 'integer', 'min:1', 'max:500'],
            'halaqaStartDate' => ['nullable', 'date'],
        ]);
        $this->authorizeCenter((int) $data['halaqaCenterId']);
        $halaqa = $service->createHalaqa([
            'center_id' => $data['halaqaCenterId'],
            'name' => $data['halaqaName'],
            'capacity' => $data['halaqaCapacity'],
            'start_date' => $data['halaqaStartDate'] ?: null,
        ]);
        $this->reset('halaqaCenterId', 'halaqaName', 'halaqaStartDate');
        $this->halaqaCapacity = 20;
        $this->saved("تم إنشاء الحلقة بالرمز {$halaqa->code} بنجاح.");
    }

    public function saveSchedule(OrganizationService $service): void
    {
        Gate::authorize('halaqas.manage');
        $data = $this->validate([
            'scheduleHalaqaId' => ['required', 'exists:halaqas,id'],
            'weekday' => ['required', 'integer', 'between:0,6'],
            'scheduleStartsAt' => ['required', 'date_format:H:i'],
            'scheduleEndsAt' => ['required', 'date_format:H:i', 'after:scheduleStartsAt'],
            'scheduleRoom' => ['nullable', 'string', 'max:255'],
        ]);
        $halaqa = Halaqa::query()->findOrFail($data['scheduleHalaqaId']);
        $this->authorizeCenter((int) $halaqa->center_id);
        $service->createSchedule([
            'halaqa_id' => $data['scheduleHalaqaId'], 'weekday' => $data['weekday'],
            'starts_at' => $data['scheduleStartsAt'], 'ends_at' => $data['scheduleEndsAt'],
            'room' => $data['scheduleRoom'] ?: null,
        ]);
        $this->reset('scheduleHalaqaId', 'scheduleStartsAt', 'scheduleEndsAt', 'scheduleRoom');
        $this->weekday = 0;
        $this->saved('تمت إضافة الموعد بنجاح.');
    }

    public function saveAssignment(OrganizationService $service): void
    {
        Gate::authorize('halaqas.manage');
        $data = $this->validate([
            'assignmentHalaqaId' => ['required', 'exists:halaqas,id'],
            'assignmentTeacherId' => ['required', 'exists:teacher_profiles,id'],
            'assignmentRole' => ['required', Rule::in(['primary', 'assistant'])],
            'assignmentStartsAt' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $halaqa = Halaqa::query()->findOrFail($data['assignmentHalaqaId']);
        $teacher = TeacherProfile::query()->findOrFail($data['assignmentTeacherId']);
        $this->authorizeCenter((int) $halaqa->center_id);

        if ((int) $teacher->center_id !== (int) $halaqa->center_id) {
            $this->addError('assignmentTeacherId', 'اختر محفّظًا يتبع مركز الحلقة نفسه.');

            return;
        }
        $assignment = $service->assignTeacher([
            'halaqa_id' => $data['assignmentHalaqaId'], 'teacher_profile_id' => $data['assignmentTeacherId'],
            'role' => $data['assignmentRole'], 'starts_at' => $data['assignmentStartsAt'],
        ]);
        $this->reset('assignmentHalaqaId', 'assignmentTeacherId', 'assignmentStartsAt');
        $this->assignmentRole = 'primary';
        $this->assignmentStartsAt = today()->toDateString();
        $this->saved($assignment->wasRecentlyCreated
            ? 'تم إسناد المحفّظ للحلقة وتفعيل جميع صلاحياته التعليمية.'
            : 'هذا الإسناد مسجّل وفعّال بالفعل؛ لم يتم إنشاء سجل مكرر.');
    }

    public function toggleActive(string $type, int $id, OrganizationService $service): void
    {
        Gate::authorize('organization.manage');
        $model = match ($type) {
            'center' => Center::query()->findOrFail($id),
            'halaqa' => Halaqa::query()->findOrFail($id),
            default => abort(404),
        };
        $this->authorizeCenter((int) ($model instanceof Center ? $model->id : $model->center_id));
        $service->setActive($model, ! $model->active);
        $this->saved('تم تحديث الحالة.');
    }

    public function render()
    {
        $centerIds = $this->visibleCenterIds();

        return view('livewire.organization-manager', [
            'centers' => Center::query()
                ->when($centerIds !== null, fn ($query) => $query->whereIn('id', $centerIds))
                ->withCount('halaqas')->latest()->limit(100)->get(),
            'staff' => StaffProfile::query()
                ->when($centerIds !== null, fn ($query) => $query->whereIn('center_id', $centerIds))
                ->where('active', true)
                ->whereHas('user', fn ($query) => $query->where('active', true)->whereNull('archived_at'))
                ->with(['user:id,name,email,phone', 'center:id,name'])->latest()->limit(100)->get(),
            'teachers' => TeacherProfile::query()
                ->when($centerIds !== null, fn ($query) => $query->whereIn('center_id', $centerIds))
                ->where('active', true)
                ->whereHas('user', fn ($query) => $query->where('active', true)->whereNull('archived_at'))
                ->with(['user:id,name,email,phone', 'user.roles:id,name', 'center:id,name'])
                ->withCount(['assignments as active_assignments_count' => fn ($assignments) => $assignments
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))])
                ->latest()
                ->limit(100)
                ->get(),
            'eligibleTeacherManagers' => User::query()
                ->where('active', true)
                ->whereNull('archived_at')
                ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['center-manager', 'super-admin']))
                ->whereHas('staffProfile', fn ($staff) => $staff
                    ->where('active', true)
                    ->when($centerIds !== null, fn ($query) => $query->whereIn('center_id', $centerIds)))
                ->with(['staffProfile.center:id,name', 'teacherProfile:id,user_id,employee_number,active'])
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'halaqas' => Halaqa::query()
                ->when($centerIds !== null, fn ($query) => $query->whereIn('center_id', $centerIds))
                ->with([
                    'center:id,name',
                    'primaryTeacher.user:id,name',
                    'schedules',
                    'teacherAssignments' => fn ($assignments) => $assignments
                        ->whereDate('starts_at', '<=', today())
                        ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                        ->with('teacher.user:id,name'),
                ])
                ->withCount('currentStudents')
                ->latest()
                ->limit(100)
                ->get(),
            'studentsCount' => Student::query()
                ->when($centerIds !== null, fn ($query) => $query->whereHas('currentHalaqa', fn ($halaqas) => $halaqas->whereIn('center_id', $centerIds)))
                ->count(),
        ]);
    }

    protected function validationAttributes(): array
    {
        return [
            'centerName' => 'اسم المركز', 'centerEmail' => 'البريد الإلكتروني',
            'personName' => 'الاسم', 'personEmail' => 'البريد الإلكتروني', 'personPassword' => 'كلمة المرور',
            'personCenterId' => 'المركز',
            'jobTitle' => 'المسمى الوظيفي', 'halaqaCenterId' => 'المركز',
            'halaqaName' => 'اسم الحلقة', 'scheduleHalaqaId' => 'الحلقة',
            'scheduleStartsAt' => 'وقت البداية', 'scheduleEndsAt' => 'وقت النهاية',
            'assignmentHalaqaId' => 'الحلقة', 'assignmentTeacherId' => 'المحفظ', 'assignmentStartsAt' => 'تاريخ الإسناد',
            'existingTeacherUserId' => 'مدير المركز',
            'managerTeacherHalaqaId' => 'حلقة المدير', 'managerTeacherStartsAt' => 'تاريخ بدء الإسناد',
        ];
    }

    private function personRules(bool $teacher): array
    {
        $rules = [
            'personName' => ['required', 'string', 'max:255'],
            'personEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'personPhone' => ['nullable', 'string', 'max:30'],
            'personPassword' => ['required', 'string', 'min:10', 'confirmed'],
            'personCenterId' => ['required', 'exists:centers,id'],
            'hiredAt' => ['nullable', 'date'],
        ];

        if ($teacher) {
            $rules['specialization'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['jobTitle'] = ['required', 'string', 'max:255'];
            $rules['staffRole'] = ['required', Rule::in(['center-manager', 'academic-supervisor', 'registrar', 'website-editor', 'report-viewer'])];
        }

        return $rules;
    }

    private function personPayload(array $data): array
    {
        return [
            'name' => $data['personName'], 'email' => $data['personEmail'],
            'phone' => $data['personPhone'] ?: null, 'password' => $data['personPassword'],
            'center_id' => $data['personCenterId'],
            'hired_at' => $data['hiredAt'] ?: null,
        ];
    }

    private function resetPerson(): void
    {
        $this->reset('personName', 'personEmail', 'personPhone', 'personPassword', 'personPassword_confirmation', 'personCenterId', 'jobTitle', 'specialization', 'hiredAt');
        $this->staffRole = 'registrar';
    }

    private function saved(string $message): void
    {
        session()->flash('success', $message);
    }

    private function authorizeSystemAdministrator(): User
    {
        $user = auth()->user();
        abort_unless($user?->active && ! $user->archived_at && $user->hasRole('super-admin'), 403);

        return $user;
    }

    /** @return list<int>|null Null means unrestricted super-admin access. */
    private function visibleCenterIds(): ?array
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            return null;
        }

        return $user->staffProfile?->active ? [(int) $user->staffProfile->center_id] : [];
    }

    private function authorizeCenter(int $centerId): void
    {
        $visibleCenterIds = $this->visibleCenterIds();
        abort_if($visibleCenterIds !== null && ! in_array($centerId, $visibleCenterIds, true), 403);
    }
}
