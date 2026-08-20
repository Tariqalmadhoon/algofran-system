<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Services\OrganizationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrganizationManager extends Component
{
    public string $activeForm = 'center';

    public string $centerName = '';

    public string $centerCode = '';

    public string $centerPhone = '';

    public string $centerEmail = '';

    public string $centerAddress = '';

    public ?int $branchCenterId = null;

    public string $branchName = '';

    public string $branchCode = '';

    public string $branchPhone = '';

    public string $branchAddress = '';

    public string $personName = '';

    public string $personEmail = '';

    public string $personPhone = '';

    public string $personPassword = '';

    public string $personPassword_confirmation = '';

    public ?int $personCenterId = null;

    public ?int $personBranchId = null;

    public string $employeeNumber = '';

    public string $jobTitle = '';

    public string $staffRole = 'registrar';

    public string $specialization = '';

    public string $hiredAt = '';

    public ?int $halaqaCenterId = null;

    public ?int $halaqaBranchId = null;

    public string $halaqaName = '';

    public string $halaqaCode = '';

    public string $halaqaProgram = '';

    public int $halaqaCapacity = 20;

    public string $halaqaRoom = '';

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
    }

    public function saveCenter(OrganizationService $service): void
    {
        Gate::authorize('organization.manage');
        $data = $this->validate([
            'centerName' => ['required', 'string', 'max:255'],
            'centerCode' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:centers,code'],
            'centerPhone' => ['nullable', 'string', 'max:30'],
            'centerEmail' => ['nullable', 'email', 'max:255'],
            'centerAddress' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->createCenter([
            'name' => $data['centerName'], 'code' => strtoupper($data['centerCode']),
            'phone' => $data['centerPhone'] ?: null, 'email' => $data['centerEmail'] ?: null,
            'address' => $data['centerAddress'] ?: null,
        ]);
        $this->reset('centerName', 'centerCode', 'centerPhone', 'centerEmail', 'centerAddress');
        $this->saved('تم إنشاء المركز بنجاح.');
    }

    public function saveBranch(OrganizationService $service): void
    {
        Gate::authorize('organization.manage');
        $data = $this->validate([
            'branchCenterId' => ['required', 'exists:centers,id'],
            'branchName' => ['required', 'string', 'max:255'],
            'branchCode' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('branches', 'code')->where('center_id', $this->branchCenterId)],
            'branchPhone' => ['nullable', 'string', 'max:30'],
            'branchAddress' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->createBranch([
            'center_id' => $data['branchCenterId'], 'name' => $data['branchName'],
            'code' => strtoupper($data['branchCode']), 'phone' => $data['branchPhone'] ?: null,
            'address' => $data['branchAddress'] ?: null,
        ]);
        $this->reset('branchCenterId', 'branchName', 'branchCode', 'branchPhone', 'branchAddress');
        $this->saved('تم إنشاء الفرع بنجاح.');
    }

    public function saveStaff(OrganizationService $service): void
    {
        Gate::authorize('users.manage');
        $data = $this->validate($this->personRules(false));
        $service->createStaff($this->personPayload($data) + [
            'job_title' => $data['jobTitle'],
            'role' => $data['staffRole'],
        ]);
        $this->resetPerson();
        $this->saved('تم إنشاء حساب الموظف بنجاح.');
    }

    public function saveTeacher(OrganizationService $service): void
    {
        Gate::authorize('users.manage');
        $data = $this->validate($this->personRules(true));
        $service->createTeacher($this->personPayload($data) + ['specialization' => $data['specialization'] ?: null]);
        $this->resetPerson();
        $this->saved('تم إنشاء حساب المحفظ بنجاح.');
    }

    public function saveHalaqa(OrganizationService $service): void
    {
        Gate::authorize('halaqas.manage');
        $data = $this->validate([
            'halaqaCenterId' => ['required', 'exists:centers,id'],
            'halaqaBranchId' => ['required', 'exists:branches,id'],
            'halaqaName' => ['required', 'string', 'max:255'],
            'halaqaCode' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('halaqas', 'code')->where('branch_id', $this->halaqaBranchId)],
            'halaqaProgram' => ['nullable', 'string', 'max:255'],
            'halaqaCapacity' => ['required', 'integer', 'min:1', 'max:500'],
            'halaqaRoom' => ['nullable', 'string', 'max:255'],
            'halaqaStartDate' => ['nullable', 'date'],
        ]);
        $service->createHalaqa([
            'center_id' => $data['halaqaCenterId'], 'branch_id' => $data['halaqaBranchId'],
            'name' => $data['halaqaName'], 'code' => strtoupper($data['halaqaCode']),
            'program' => $data['halaqaProgram'] ?: null, 'capacity' => $data['halaqaCapacity'],
            'room' => $data['halaqaRoom'] ?: null, 'start_date' => $data['halaqaStartDate'] ?: null,
        ]);
        $this->reset('halaqaCenterId', 'halaqaBranchId', 'halaqaName', 'halaqaCode', 'halaqaProgram', 'halaqaRoom', 'halaqaStartDate');
        $this->halaqaCapacity = 20;
        $this->saved('تم إنشاء الحلقة بنجاح.');
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
            'assignmentStartsAt' => ['required', 'date'],
        ]);
        $service->assignTeacher([
            'halaqa_id' => $data['assignmentHalaqaId'], 'teacher_profile_id' => $data['assignmentTeacherId'],
            'role' => $data['assignmentRole'], 'starts_at' => $data['assignmentStartsAt'],
        ]);
        $this->reset('assignmentHalaqaId', 'assignmentTeacherId', 'assignmentStartsAt');
        $this->assignmentRole = 'primary';
        $this->saved('تم إسناد المحفظ للحلقة مع حفظ تاريخ الإسناد.');
    }

    public function toggleActive(string $type, int $id, OrganizationService $service): void
    {
        Gate::authorize('organization.manage');
        $model = match ($type) {
            'center' => Center::query()->findOrFail($id),
            'branch' => Branch::query()->findOrFail($id),
            'halaqa' => Halaqa::query()->findOrFail($id),
            default => abort(404),
        };
        $service->setActive($model, ! $model->active);
        $this->saved('تم تحديث الحالة.');
    }

    public function render()
    {
        return view('livewire.organization-manager', [
            'centers' => Center::query()->withCount('branches')->latest()->limit(100)->get(),
            'branches' => Branch::query()->with('center:id,name')->withCount('halaqas')->latest()->limit(100)->get(),
            'staff' => StaffProfile::query()->with(['user:id,name,email,phone', 'center:id,name', 'branch:id,name'])->latest()->limit(100)->get(),
            'teachers' => TeacherProfile::query()->with(['user:id,name,email,phone', 'center:id,name', 'branch:id,name'])->latest()->limit(100)->get(),
            'halaqas' => Halaqa::query()->with(['center:id,name', 'branch:id,name', 'primaryTeacher.user:id,name', 'schedules'])->latest()->limit(100)->get(),
        ]);
    }

    protected function validationAttributes(): array
    {
        return [
            'centerName' => 'اسم المركز', 'centerCode' => 'رمز المركز', 'centerEmail' => 'البريد الإلكتروني',
            'branchCenterId' => 'المركز', 'branchName' => 'اسم الفرع', 'branchCode' => 'رمز الفرع',
            'personName' => 'الاسم', 'personEmail' => 'البريد الإلكتروني', 'personPassword' => 'كلمة المرور',
            'personCenterId' => 'المركز', 'personBranchId' => 'الفرع', 'employeeNumber' => 'الرقم الوظيفي',
            'jobTitle' => 'المسمى الوظيفي', 'halaqaCenterId' => 'المركز', 'halaqaBranchId' => 'الفرع',
            'halaqaName' => 'اسم الحلقة', 'halaqaCode' => 'رمز الحلقة', 'scheduleHalaqaId' => 'الحلقة',
            'scheduleStartsAt' => 'وقت البداية', 'scheduleEndsAt' => 'وقت النهاية',
            'assignmentHalaqaId' => 'الحلقة', 'assignmentTeacherId' => 'المحفظ', 'assignmentStartsAt' => 'تاريخ الإسناد',
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
            'personBranchId' => ['nullable', 'exists:branches,id'],
            'employeeNumber' => ['required', 'string', 'max:50', Rule::unique($teacher ? 'teacher_profiles' : 'staff_profiles', 'employee_number')],
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
            'center_id' => $data['personCenterId'], 'branch_id' => $data['personBranchId'] ?: null,
            'employee_number' => $data['employeeNumber'], 'hired_at' => $data['hiredAt'] ?: null,
        ];
    }

    private function resetPerson(): void
    {
        $this->reset('personName', 'personEmail', 'personPhone', 'personPassword', 'personPassword_confirmation', 'personCenterId', 'personBranchId', 'employeeNumber', 'jobTitle', 'specialization', 'hiredAt');
        $this->staffRole = 'registrar';
    }

    private function saved(string $message): void
    {
        session()->flash('success', $message);
    }
}
