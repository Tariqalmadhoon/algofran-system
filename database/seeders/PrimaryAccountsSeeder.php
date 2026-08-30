<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Center;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\SequentialCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PrimaryAccountsSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin1@gofran.com';

    private const TEACHER_EMAIL = 'teacher1@gofran.com';

    private const INITIAL_PASSWORD = '123456789';

    public function run(SequentialCodeService $sequentialCodes): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Primary account reset may only run in local or testing environments.');
        }

        $this->call(RolesAndPermissionsSeeder::class);

        DB::transaction(function () use ($sequentialCodes): void {
            [$center, $branch] = $this->organization();
            $administrator = $this->administratorCandidate();
            $teacher = $this->teacherCandidate($administrator);

            foreach (User::query()->whereIn('email', [self::ADMIN_EMAIL, self::TEACHER_EMAIL])->get() as $conflict) {
                if (! in_array($conflict->id, [$administrator?->id, $teacher?->id], true)) {
                    $conflict->forceFill(['email' => $this->archiveEmail($conflict)])->save();
                }
            }

            $administrator ??= User::query()->create([
                'name' => 'مدير النظام',
                'email' => self::ADMIN_EMAIL,
                'password' => Hash::make(self::INITIAL_PASSWORD),
                'active' => true,
                'email_verified_at' => now(),
                'locale' => 'ar',
            ]);
            $teacher ??= User::query()->create([
                'name' => 'المحفّظ الأول',
                'email' => self::TEACHER_EMAIL,
                'password' => Hash::make(self::INITIAL_PASSWORD),
                'active' => true,
                'email_verified_at' => now(),
                'locale' => 'ar',
            ]);

            $this->configureAdministrator($administrator, $center, $branch, $sequentialCodes);
            $this->configureTeacher($teacher, $center, $branch, $sequentialCodes);

            User::query()
                ->whereKeyNot($administrator->id)
                ->whereKeyNot($teacher->id)
                ->whereNull('archived_at')
                ->orderBy('id')
                ->get()
                ->each(fn (User $user) => $this->removeLoginAccount($user));

            $this->command?->info('Primary access accounts are ready: '.self::ADMIN_EMAIL.' and '.self::TEACHER_EMAIL.'.');
        });
    }

    /** @return array{Center, Branch} */
    private function organization(): array
    {
        $center = Center::query()->where('active', true)->oldest('id')->first()
            ?? Center::query()->create([
                'name' => 'مركز الغفران لتحفيظ القرآن الكريم',
                'code' => 'MAIN',
                'active' => true,
            ]);
        $branch = Branch::query()->where('center_id', $center->id)->oldest('id')->first()
            ?? Branch::query()->create([
                'center_id' => $center->id,
                'name' => 'السجل الداخلي للمركز',
                'code' => 'SYSTEM',
                'active' => true,
            ]);

        return [$center, $branch];
    }

    private function administratorCandidate(): ?User
    {
        return User::query()
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'center-manager'))
            ->whereHas('teacherProfile.assignments', fn ($assignments) => $assignments
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())))
            ->oldest('id')
            ->first()
            ?? User::query()->where('email', self::ADMIN_EMAIL)->first()
            ?? User::query()->where('email', 'admin1@gofran.local')->first()
            ?? User::query()->whereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'))->oldest('id')->first();
    }

    private function teacherCandidate(?User $administrator): ?User
    {
        return User::query()
            ->whereKeyNot($administrator?->id)
            ->whereIn('email', [self::TEACHER_EMAIL, 'teacher1@gofran.local'])
            ->first()
            ?? User::query()
                ->whereKeyNot($administrator?->id)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
                ->whereHas('teacherProfile')
                ->oldest('id')
                ->first();
    }

    private function configureAdministrator(User $user, Center $center, Branch $branch, SequentialCodeService $sequentialCodes): void
    {
        $user->forceFill([
            'name' => 'مدير النظام',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::INITIAL_PASSWORD),
            'active' => true,
            'archived_at' => null,
            'email_verified_at' => now(),
            'remember_token' => null,
        ])->save();
        $user->syncRoles(['super-admin', 'teacher']);
        $user->syncPermissions([]);

        StaffProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'center_id' => $user->staffProfile?->center_id ?? $center->id,
                'branch_id' => $user->staffProfile?->branch_id ?? $branch->id,
                'employee_number' => $user->staffProfile?->employee_number ?? $sequentialCodes->staffEmployeeNumber(),
                'job_title' => 'مدير النظام',
                'hired_at' => $user->staffProfile?->hired_at ?? today(),
                'active' => true,
            ],
        );
        TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'center_id' => $user->teacherProfile?->center_id ?? $center->id,
                'branch_id' => $user->teacherProfile?->branch_id ?? $branch->id,
                'employee_number' => $user->teacherProfile?->employee_number ?? $sequentialCodes->teacherEmployeeNumber(),
                'specialization' => $user->teacherProfile?->specialization ?? 'حفظ وتجويد القرآن الكريم',
                'hired_at' => $user->teacherProfile?->hired_at ?? today(),
                'active' => true,
            ],
        );
    }

    private function configureTeacher(User $user, Center $center, Branch $branch, SequentialCodeService $sequentialCodes): void
    {
        $user->forceFill([
            'name' => 'المحفّظ الأول',
            'email' => self::TEACHER_EMAIL,
            'password' => Hash::make(self::INITIAL_PASSWORD),
            'active' => true,
            'archived_at' => null,
            'email_verified_at' => now(),
            'remember_token' => null,
        ])->save();
        $user->syncRoles(['teacher']);
        $user->syncPermissions([]);
        TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'center_id' => $user->teacherProfile?->center_id ?? $center->id,
                'branch_id' => $user->teacherProfile?->branch_id ?? $branch->id,
                'employee_number' => $user->teacherProfile?->employee_number ?? $sequentialCodes->teacherEmployeeNumber(),
                'specialization' => $user->teacherProfile?->specialization ?? 'حفظ وتجويد القرآن الكريم',
                'hired_at' => $user->teacherProfile?->hired_at ?? today(),
                'active' => true,
            ],
        );
    }

    private function removeLoginAccount(User $user): void
    {
        $user->studentProfile?->update(['user_id' => null]);
        $user->guardianProfile?->update(['user_id' => null]);
        $user->staffProfile?->delete();

        $teacherProfile = $user->teacherProfile;
        $preservesAcademicHistory = $teacherProfile && (
            $teacherProfile->assignments()->exists()
            || $teacherProfile->dailyRecords()->exists()
            || $teacherProfile->courses()->exists()
            || $teacherProfile->alerts()->exists()
        );

        if ($teacherProfile && ! $preservesAcademicHistory) {
            $teacherProfile->delete();
        } elseif ($teacherProfile) {
            $teacherProfile->update(['active' => false]);
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->tokens()->delete();
        $user->notifications()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        if (! $preservesAcademicHistory) {
            $user->delete();

            return;
        }

        $user->forceFill([
            'name' => 'سجل مستخدم محفوظ #'.$user->id,
            'email' => $this->archiveEmail($user),
            'phone' => null,
            'password' => Hash::make(Str::random(64)),
            'active' => false,
            'archived_at' => now(),
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    private function archiveEmail(User $user): string
    {
        return 'archived-'.$user->id.'-'.Str::lower(Str::random(10)).'@invalid.local';
    }
}
