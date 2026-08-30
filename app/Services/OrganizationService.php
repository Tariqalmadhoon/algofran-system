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
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SequentialCodeService $sequentialCodes,
    ) {}

    public function createCenter(array $data): Center
    {
        return DB::transaction(function () use ($data) {
            $data['code'] ??= $this->sequentialCodes->center();
            $center = Center::query()->create($data);
            $this->auditLogger->record('center.created', $center, newValues: $center->getAttributes());

            return $center;
        });
    }

    public function createBranch(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $data['code'] ??= $this->sequentialCodes->branch((int) $data['center_id']);
            $branch = Branch::query()->create($data);
            $this->auditLogger->record('branch.created', $branch, newValues: $branch->getAttributes());

            return $branch;
        });
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

    public function createTeacherProfileForManager(User $user, array $data = []): TeacherProfile
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::query()
                ->with(['roles', 'staffProfile'])
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (! $user->active || $user->archived_at || ! $user->hasAnyRole(['center-manager', 'super-admin']) || ! $user->staffProfile?->active) {
                throw ValidationException::withMessages([
                    'existingTeacherUserId' => 'يجب اختيار مدير نظام أو مدير مركز فعّال له ملف وظيفي.',
                ]);
            }

            $staff = $user->staffProfile()->lockForUpdate()->firstOrFail();
            $existingProfile = $user->teacherProfile()->lockForUpdate()->first();

            if ($existingProfile) {
                $existingProfile->update([
                    'center_id' => $staff->center_id,
                    'branch_id' => $staff->branch_id,
                    'specialization' => $data['specialization'] ?? $existingProfile->specialization,
                    'hired_at' => $data['hired_at'] ?? $existingProfile->hired_at ?? $staff->hired_at ?? today()->toDateString(),
                    'active' => true,
                ]);
                $user->assignRole('teacher');
                $this->auditLogger->record(
                    'manager.teacher-profile.reactivated',
                    $existingProfile,
                    newValues: $existingProfile->fresh()->load(['user', 'center'])->toArray(),
                );

                return $existingProfile->fresh();
            }

            $profile = TeacherProfile::query()->create([
                'user_id' => $user->id,
                'center_id' => $staff->center_id,
                'branch_id' => $staff->branch_id,
                'employee_number' => $data['employee_number'] ?? $this->sequentialCodes->teacherEmployeeNumber(),
                'specialization' => $data['specialization'] ?? null,
                'hired_at' => $data['hired_at'] ?? $staff->hired_at ?? today()->toDateString(),
                'active' => true,
            ]);
            $user->assignRole('teacher');
            $this->auditLogger->record(
                'manager.teacher-profile.created',
                $profile,
                newValues: $profile->load(['user', 'center'])->toArray(),
            );

            return $profile;
        });
    }

    /**
     * Activate a manager as a teacher and assign the selected Halaqa atomically.
     * No partial teacher profile is left behind if the assignment is invalid.
     */
    public function activateManagerAsTeacherAndAssign(User $user, array $data): HalaqaTeacherAssignment
    {
        return DB::transaction(function () use ($user, $data): HalaqaTeacherAssignment {
            $user = User::query()
                ->with('staffProfile')
                ->lockForUpdate()
                ->findOrFail($user->id);
            $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($data['halaqa_id']);

            if (! $user->staffProfile?->active || (int) $user->staffProfile->center_id !== (int) $halaqa->center_id) {
                throw ValidationException::withMessages([
                    'halaqa_id' => 'يجب اختيار حلقة فعّالة تتبع مركز المدير نفسه.',
                ]);
            }

            $teacher = $this->createTeacherProfileForManager($user, [
                'specialization' => $data['specialization'] ?? null,
                'hired_at' => $data['hired_at'] ?? null,
            ]);

            return $this->assignTeacher([
                'halaqa_id' => $halaqa->id,
                'teacher_profile_id' => $teacher->id,
                'role' => $data['role'] ?? 'primary',
                'starts_at' => $data['starts_at'] ?? today()->toDateString(),
            ]);
        });
    }

    public function createHalaqa(array $data): Halaqa
    {
        return DB::transaction(function () use ($data) {
            $branch = isset($data['branch_id']) && $data['branch_id']
                ? Branch::query()->findOrFail($data['branch_id'])
                : $this->internalBranch((int) $data['center_id']);

            if ((int) $branch->center_id !== (int) $data['center_id']) {
                throw ValidationException::withMessages(['branch_id' => 'الفرع المحدد لا يتبع المركز.']);
            }

            $data['branch_id'] = $branch->id;
            $data['code'] ??= $this->sequentialCodes->halaqa($branch->id);
            $halaqa = Halaqa::query()->create($data);
            $this->auditLogger->record('halaqa.created', $halaqa, newValues: $halaqa->getAttributes());

            return $halaqa;
        });
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
            $teacher = TeacherProfile::query()->with('user')->findOrFail($data['teacher_profile_id']);
            $startsAt = Carbon::parse($data['starts_at'])->startOfDay();

            if (! $halaqa->active) {
                throw ValidationException::withMessages(['assignmentHalaqaId' => 'لا يمكن إسناد محفّظ إلى حلقة متوقفة.']);
            }

            if ($startsAt->isFuture()) {
                throw ValidationException::withMessages(['assignmentStartsAt' => 'فعّل الإسناد بتاريخ اليوم أو بتاريخ سابق حتى يظهر للمحفّظ مباشرة.']);
            }

            if (! in_array($data['role'] ?? null, ['primary', 'assistant'], true)) {
                throw ValidationException::withMessages(['assignmentRole' => 'نوع الإسناد المحدد غير صالح.']);
            }

            if (! $teacher->active || ! $teacher->user?->active) {
                throw ValidationException::withMessages(['assignmentTeacherId' => 'ملف المحفّظ المحدد غير فعّال.']);
            }

            if ((int) $teacher->center_id !== (int) $halaqa->center_id) {
                throw ValidationException::withMessages(['assignmentTeacherId' => 'المحفّظ المحدد لا يتبع مركز الحلقة.']);
            }

            // The teacher role guarantees the full teaching permission set for managers
            // who also keep their original administrative role.
            $teacher->user->assignRole('teacher');

            $existingAssignment = HalaqaTeacherAssignment::query()
                ->where('halaqa_id', $halaqa->id)
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('starts_at', $startsAt->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existingAssignment) {
                if ($existingAssignment->role !== $data['role']) {
                    throw ValidationException::withMessages([
                        'assignmentTeacherId' => 'يوجد إسناد مسجل لهذا المحفّظ والحلقة في التاريخ نفسه بنوع مختلف.',
                    ]);
                }

                if ($existingAssignment->ends_at) {
                    throw ValidationException::withMessages([
                        'assignmentTeacherId' => 'يوجد سجل إسناد منتهٍ للمحفّظ في التاريخ نفسه. اختر تاريخًا آخر أو راجع سجل الإسناد.',
                    ]);
                }

                if ($data['role'] === 'primary') {
                    $halaqa->update(['primary_teacher_id' => $teacher->id]);
                }

                return $existingAssignment;
            }

            $activeAssignment = HalaqaTeacherAssignment::query()
                ->where('halaqa_id', $halaqa->id)
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('starts_at', '<=', $startsAt->toDateString())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $startsAt->toDateString()))
                ->lockForUpdate()
                ->first();

            if ($activeAssignment) {
                if ($activeAssignment->role !== $data['role']) {
                    throw ValidationException::withMessages([
                        'assignmentTeacherId' => 'المحفّظ مسند لهذه الحلقة بالفعل بنوع آخر؛ أنهِ الإسناد السابق أولًا.',
                    ]);
                }

                if ($data['role'] === 'primary') {
                    $halaqa->update(['primary_teacher_id' => $teacher->id]);
                }

                return $activeAssignment;
            }

            if ($data['role'] === 'primary') {
                $previousPrimaryAssignments = HalaqaTeacherAssignment::query()
                    ->where('halaqa_id', $halaqa->id)
                    ->where('teacher_profile_id', '!=', $teacher->id)
                    ->where('role', 'primary')
                    ->whereDate('starts_at', '<=', $startsAt->toDateString())
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $startsAt->toDateString()))
                    ->lockForUpdate()
                    ->get();

                foreach ($previousPrimaryAssignments as $previousAssignment) {
                    $endsAt = $startsAt->copy()->subDay();
                    if ($previousAssignment->starts_at->greaterThan($endsAt)) {
                        $endsAt = $previousAssignment->starts_at->copy();
                    }
                    $previousAssignment->update(['ends_at' => $endsAt->toDateString()]);
                }

                $halaqa->update(['primary_teacher_id' => $teacher->id]);
            }

            $assignment = HalaqaTeacherAssignment::query()->create([
                'halaqa_id' => $halaqa->id,
                'teacher_profile_id' => $teacher->id,
                'role' => $data['role'],
                'starts_at' => $startsAt->toDateString(),
                'assigned_by' => $data['assigned_by'] ?? auth()->id(),
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

        $employeeNumber = $data['employee_number'] ?? match ($profileClass) {
            StaffProfile::class => $this->sequentialCodes->staffEmployeeNumber(),
            TeacherProfile::class => $this->sequentialCodes->teacherEmployeeNumber(),
        };

        return $profileClass::query()->create([
            'user_id' => $user->id,
            'center_id' => $data['center_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'employee_number' => $employeeNumber,
            'hired_at' => $data['hired_at'] ?? null,
            'active' => true,
            ...$profileFields,
        ]);
    }

    private function internalBranch(int $centerId): Branch
    {
        Center::query()->findOrFail($centerId);

        $branch = Branch::query()
            ->where('center_id', $centerId)
            ->oldest('id')
            ->first();

        if ($branch) {
            return $branch;
        }

        return $this->createBranch([
            'center_id' => $centerId,
            'name' => 'السجل الداخلي للمركز',
            'code' => 'SYSTEM',
            'active' => true,
        ]);
    }
}
