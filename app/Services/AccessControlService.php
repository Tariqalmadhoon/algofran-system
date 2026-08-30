<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Center;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlService
{
    private const STAFF_ROLES = [
        'center-manager',
        'academic-supervisor',
        'registrar',
        'website-editor',
        'report-viewer',
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SequentialCodeService $sequentialCodes,
    ) {}

    public function createAccount(array $data, User $actor): User
    {
        $this->authorizeAdministrator($actor);
        $roles = $this->validatedRoles($data['roles'] ?? []);
        $permissions = $this->validatedPermissions($data['permissions'] ?? []);

        return DB::transaction(function () use ($data, $roles, $permissions, $actor): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'active' => true,
                'archived_at' => null,
                'email_verified_at' => now(),
                'locale' => 'ar',
            ]);
            $user->syncRoles($roles);
            $user->syncPermissions($permissions);
            $this->synchronizeProfiles($user, $roles, $data);
            $this->auditLogger->record('access.account.created', $user, newValues: [
                'roles' => $roles,
                'direct_permissions' => $permissions,
                'center_id' => $data['center_id'] ?? null,
            ], actor: $actor);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user->fresh(['roles', 'permissions', 'staffProfile', 'teacherProfile']);
        });
    }

    public function updateAccess(User $target, array $data, User $actor): User
    {
        $this->authorizeAdministrator($actor);
        $roles = $this->validatedRoles($data['roles'] ?? []);
        $permissions = $this->validatedPermissions($data['permissions'] ?? []);
        $active = (bool) ($data['active'] ?? false);

        return DB::transaction(function () use ($target, $data, $roles, $permissions, $active, $actor): User {
            $target = User::query()
                ->with(['roles', 'permissions', 'staffProfile', 'teacherProfile.assignments'])
                ->lockForUpdate()
                ->findOrFail($target->id);

            // Lock every active administrator row before checking continuity. This
            // prevents two concurrent demotions from leaving the system unmanaged.
            User::query()
                ->where('active', true)
                ->whereNull('archived_at')
                ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
                ->lockForUpdate()
                ->get(['users.id']);

            $this->ensureAccountIsManageable($target);
            $this->protectAdministrativeContinuity($target, $actor, $roles, $active);
            $this->protectActiveTeachingAssignment($target, $roles);

            $oldValues = [
                'active' => (bool) $target->active,
                'roles' => $target->getRoleNames()->values()->all(),
                'direct_permissions' => $target->getDirectPermissions()->pluck('name')->values()->all(),
            ];

            $target->update(['active' => $active]);
            $target->syncRoles($roles);
            $target->syncPermissions($permissions);
            $this->synchronizeProfiles($target, $roles, $data);

            if (! $active) {
                $target->tokens()->delete();
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            $this->auditLogger->record('access.account.updated', $target, $oldValues, [
                'active' => $active,
                'roles' => $roles,
                'direct_permissions' => $permissions,
                'center_id' => $data['center_id'] ?? null,
            ], $actor);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $target->fresh(['roles', 'permissions', 'staffProfile', 'teacherProfile']);
        });
    }

    public function resetPassword(User $target, string $password, User $actor): void
    {
        $this->authorizeAdministrator($actor);

        DB::transaction(function () use ($target, $password, $actor): void {
            $target = User::query()->lockForUpdate()->findOrFail($target->id);
            $this->ensureAccountIsManageable($target);
            $target->forceFill([
                'password' => Hash::make($password),
                'remember_token' => null,
            ])->save();
            $target->tokens()->delete();
            DB::table('sessions')->where('user_id', $target->id)->delete();
            $this->auditLogger->record('access.account.password-reset', $target, actor: $actor);
        });
    }

    private function authorizeAdministrator(User $actor): void
    {
        if (! $actor->active || $actor->archived_at || ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('إدارة الحسابات والصلاحيات متاحة لمدير النظام فقط.');
        }
    }

    private function ensureAccountIsManageable(User $target): void
    {
        if ($target->archived_at) {
            throw ValidationException::withMessages(['selectedUserId' => 'هذا سجل مؤرشف وليس حساب دخول قابلًا للتعديل.']);
        }
    }

    /** @return list<string> */
    private function validatedRoles(array $roles): array
    {
        $roles = array_values(array_unique(array_filter($roles, 'is_string')));
        $allowedRoles = array_keys(config('access_control.roles', []));

        if ($roles === [] || array_diff($roles, $allowedRoles) !== []) {
            throw ValidationException::withMessages(['editRoles' => 'اختر طبقة صلاحيات واحدة صحيحة على الأقل.']);
        }

        if (Role::query()->whereIn('name', $roles)->count() !== count($roles)) {
            throw ValidationException::withMessages(['editRoles' => 'تعذر العثور على إحدى طبقات الصلاحيات المحددة.']);
        }

        return $roles;
    }

    /** @return list<string> */
    private function validatedPermissions(array $permissions): array
    {
        $permissions = array_values(array_unique(array_filter($permissions, 'is_string')));
        $allowedPermissions = collect(config('access_control.permission_groups', []))
            ->flatMap(fn (array $items) => array_keys($items))
            ->values()
            ->all();

        if (array_diff($permissions, $allowedPermissions) !== []) {
            throw ValidationException::withMessages(['editPermissions' => 'توجد صلاحية مباشرة غير معتمدة.']);
        }

        if ($permissions !== [] && Permission::query()->whereIn('name', $permissions)->count() !== count($permissions)) {
            throw ValidationException::withMessages(['editPermissions' => 'تعذر العثور على إحدى الصلاحيات المباشرة.']);
        }

        return $permissions;
    }

    private function protectAdministrativeContinuity(User $target, User $actor, array $roles, bool $active): void
    {
        if ($target->is($actor) && (! $active || ! in_array('super-admin', $roles, true))) {
            throw ValidationException::withMessages(['editRoles' => 'لا يمكنك تعطيل حسابك الحالي أو إزالة صلاحية مدير النظام منه.']);
        }

        if (! $target->hasRole('super-admin') || ($active && in_array('super-admin', $roles, true))) {
            return;
        }

        $otherActiveAdministrators = User::query()
            ->whereKeyNot($target->id)
            ->where('active', true)
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
            ->exists();

        if (! $otherActiveAdministrators) {
            throw ValidationException::withMessages(['editRoles' => 'يجب الإبقاء على مدير نظام فعّال واحد على الأقل.']);
        }
    }

    private function protectActiveTeachingAssignment(User $target, array $roles): void
    {
        if (in_array('teacher', $roles, true) || ! $target->teacherProfile) {
            return;
        }

        if ($target->teacherProfile->assignments()
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->exists()) {
            throw ValidationException::withMessages([
                'editRoles' => 'لا يمكن إزالة دور المحفّظ قبل إنهاء أو نقل إسناد حلقته الفعّال.',
            ]);
        }
    }

    private function synchronizeProfiles(User $user, array $roles, array $data): void
    {
        $needsStaffProfile = array_intersect($roles, self::STAFF_ROLES) !== [];
        $needsTeacherProfile = in_array('teacher', $roles, true);
        $existingCenterId = $user->staffProfile?->center_id ?? $user->teacherProfile?->center_id;
        $centerId = (int) ($data['center_id'] ?? $existingCenterId ?? 0);

        if (($needsStaffProfile || $needsTeacherProfile) && ! $centerId) {
            throw ValidationException::withMessages(['editCenterId' => 'اختر المركز للحساب الوظيفي أو التعليمي.']);
        }

        $center = $centerId ? Center::query()->where('active', true)->find($centerId) : null;
        if (($needsStaffProfile || $needsTeacherProfile) && ! $center) {
            throw ValidationException::withMessages(['editCenterId' => 'المركز المحدد غير فعّال أو غير موجود.']);
        }

        $branch = $center ? $this->internalBranch($center) : null;

        if ($needsStaffProfile) {
            $staffRole = collect(self::STAFF_ROLES)->first(fn (string $role) => in_array($role, $roles, true));
            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'center_id' => $center->id,
                    'branch_id' => $branch?->id,
                    'employee_number' => $user->staffProfile?->employee_number ?? $this->sequentialCodes->staffEmployeeNumber(),
                    'job_title' => $data['job_title'] ?? config("access_control.roles.{$staffRole}.label", 'موظف'),
                    'hired_at' => $user->staffProfile?->hired_at ?? today(),
                    'active' => (bool) $user->active,
                ],
            );
        } elseif ($user->staffProfile) {
            $user->staffProfile->update(['active' => false]);
        }

        if ($needsTeacherProfile) {
            TeacherProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'center_id' => $center->id,
                    'branch_id' => $branch?->id,
                    'employee_number' => $user->teacherProfile?->employee_number ?? $this->sequentialCodes->teacherEmployeeNumber(),
                    'identity_number' => $data['teacher_identity_number'] ?? $user->teacherProfile?->identity_number,
                    'specialization' => $data['specialization'] ?? $user->teacherProfile?->specialization,
                    'hired_at' => $user->teacherProfile?->hired_at ?? today(),
                    'active' => (bool) $user->active,
                ],
            );
        } elseif ($user->teacherProfile) {
            $user->teacherProfile->update(['active' => false]);
        }
    }

    private function internalBranch(Center $center): Branch
    {
        return Branch::query()->where('center_id', $center->id)->oldest('id')->first()
            ?? Branch::query()->create([
                'center_id' => $center->id,
                'name' => 'السجل الداخلي للمركز',
                'code' => $this->sequentialCodes->branch($center->id),
                'active' => true,
            ]);
    }
}
