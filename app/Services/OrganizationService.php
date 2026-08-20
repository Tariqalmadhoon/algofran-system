<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\HalaqaSchedule;
use App\Models\HalaqaTeacherAssignment;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function createCenter(array $data): Center
    {
        $center = Center::query()->create($data);
        $this->auditLogger->record('center.created', $center, newValues: $center->getAttributes());

        return $center;
    }

    public function createBranch(array $data): Branch
    {
        $branch = Branch::query()->create($data);
        $this->auditLogger->record('branch.created', $branch, newValues: $branch->getAttributes());

        return $branch;
    }

    public function createStaff(array $data): StaffProfile
    {
        if (! in_array($data['role'] ?? null, ['center-manager', 'academic-supervisor', 'registrar', 'website-editor', 'report-viewer'], true)) {
            throw ValidationException::withMessages(['role' => 'الدور الوظيفي المحدد غير صالح.']);
        }

        return DB::transaction(function () use ($data) {
            $profile = $this->createUserProfile($data, StaffProfile::class, [
                'job_title' => $data['job_title'],
            ]);
            $profile->user->assignRole($data['role']);
            $this->auditLogger->record('staff.created', $profile, newValues: $profile->load('user')->toArray());

            return $profile;
        });
    }

    public function createTeacher(array $data): TeacherProfile
    {
        return DB::transaction(function () use ($data) {
            $profile = $this->createUserProfile($data, TeacherProfile::class, [
                'specialization' => $data['specialization'] ?? null,
            ]);
            $profile->user->assignRole('teacher');
            $this->auditLogger->record('teacher.created', $profile, newValues: $profile->load('user')->toArray());

            return $profile;
        });
    }

    public function createHalaqa(array $data): Halaqa
    {
        $branch = Branch::query()->findOrFail($data['branch_id']);

        if ((int) $branch->center_id !== (int) $data['center_id']) {
            throw ValidationException::withMessages(['branch_id' => 'الفرع المحدد لا يتبع المركز.']);
        }

        $halaqa = Halaqa::query()->create($data);
        $this->auditLogger->record('halaqa.created', $halaqa, newValues: $halaqa->getAttributes());

        return $halaqa;
    }

    public function createSchedule(array $data): HalaqaSchedule
    {
        $schedule = HalaqaSchedule::query()->create($data);
        $this->auditLogger->record('halaqa.schedule.created', $schedule, newValues: $schedule->getAttributes());

        return $schedule;
    }

    public function assignTeacher(array $data): HalaqaTeacherAssignment
    {
        return DB::transaction(function () use ($data) {
            $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($data['halaqa_id']);
            $teacher = TeacherProfile::query()->findOrFail($data['teacher_profile_id']);

            if ((int) $teacher->center_id !== (int) $halaqa->center_id) {
                throw ValidationException::withMessages(['teacher_profile_id' => 'المحفظ المحدد لا يتبع مركز الحلقة.']);
            }

            if ($data['role'] === 'primary') {
                HalaqaTeacherAssignment::query()
                    ->where('halaqa_id', $halaqa->id)
                    ->where('role', 'primary')
                    ->whereNull('ends_at')
                    ->update(['ends_at' => Carbon::parse($data['starts_at'])->subDay()->toDateString()]);

                $halaqa->update(['primary_teacher_id' => $teacher->id]);
            }

            $assignment = HalaqaTeacherAssignment::query()->create([
                ...$data,
                'assigned_by' => auth()->id(),
            ]);
            $this->auditLogger->record('halaqa.teacher.assigned', $assignment, newValues: $assignment->getAttributes());

            return $assignment;
        });
    }

    public function setActive(Model $model, bool $active): void
    {
        $old = ['active' => (bool) $model->getAttribute('active')];
        $model->update(['active' => $active]);
        $this->auditLogger->record($model->getTable().'.status.changed', $model, $old, ['active' => $active]);
    }

    private function createUserProfile(array $data, string $profileClass, array $profileFields): StaffProfile|TeacherProfile
    {
        $branch = isset($data['branch_id']) && $data['branch_id']
            ? Branch::query()->findOrFail($data['branch_id'])
            : null;

        if ($branch && (int) $branch->center_id !== (int) $data['center_id']) {
            throw ValidationException::withMessages(['branch_id' => 'الفرع المحدد لا يتبع المركز.']);
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'active' => true,
        ]);

        return $profileClass::query()->create([
            'user_id' => $user->id,
            'center_id' => $data['center_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'employee_number' => $data['employee_number'],
            'hired_at' => $data['hired_at'] ?? null,
            'active' => true,
            ...$profileFields,
        ]);
    }
}
