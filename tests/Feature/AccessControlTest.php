<?php

namespace Tests\Feature;

use App\Livewire\UserAccessManager;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\StudentVisibilityService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_access_control_screen_and_service_reject_non_super_admin_even_with_users_manage(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $manager->givePermissionTo('users.manage');

        $this->get(route('access.index'))->assertRedirect(route('login'));
        $this->actingAs($manager)->get(route('access.index'))->assertForbidden();

        [$center] = $this->organization('DENY');

        try {
            app(AccessControlService::class)->createAccount([
                'name' => 'Unauthorized Teacher',
                'email' => 'unauthorized-teacher@example.com',
                'password' => 'TeacherPass123!',
                'roles' => ['teacher'],
                'permissions' => [],
                'center_id' => $center->id,
            ], $manager);

            $this->fail('A non-super-admin was able to create an account.');
        } catch (AuthorizationException) {
            $this->assertDatabaseMissing('users', ['email' => 'unauthorized-teacher@example.com']);
        }
    }

    public function test_super_admin_can_open_screen_and_create_teacher_with_active_profile_through_livewire(): void
    {
        $admin = $this->superAdmin();
        [$center, $branch] = $this->organization('CREATE');

        $this->actingAs($admin)->get(route('access.index'))->assertOk();

        Livewire::actingAs($admin)
            ->test(UserAccessManager::class)
            ->set('showCreateForm', true)
            ->set('createName', 'Teacher Account')
            ->set('createEmail', 'teacher-access@example.com')
            ->set('createPassword', 'TeacherPass123!')
            ->set('createPassword_confirmation', 'TeacherPass123!')
            ->set('createRoles', ['teacher'])
            ->set('createPermissions', ['students.export'])
            ->set('createCenterId', $center->id)
            ->set('createSpecialization', 'Quran memorization')
            ->call('createAccount')
            ->assertHasNoErrors();

        $teacher = User::query()->where('email', 'teacher-access@example.com')->firstOrFail();

        $this->assertTrue($teacher->active);
        $this->assertTrue($teacher->hasExactRoles(['teacher']));
        $this->assertTrue($teacher->hasDirectPermission('students.export'));
        $this->assertTrue(Hash::check('TeacherPass123!', $teacher->password));
        $this->assertNotNull($teacher->email_verified_at);
        $this->assertDatabaseHas('teacher_profiles', [
            'user_id' => $teacher->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'access.account.created',
            'auditable_id' => $teacher->id,
        ]);
    }

    public function test_super_admin_can_update_roles_and_direct_permissions_without_losing_teacher_profile(): void
    {
        $admin = $this->superAdmin();
        [$center] = $this->organization('UPDATE');
        $access = app(AccessControlService::class);
        $teacher = $access->createAccount([
            'name' => 'Role Update Teacher',
            'email' => 'role-update@example.com',
            'password' => 'TeacherPass123!',
            'roles' => ['teacher'],
            'permissions' => ['students.export'],
            'center_id' => $center->id,
        ], $admin);

        $updated = $access->updateAccess($teacher, [
            'roles' => ['teacher', 'report-viewer'],
            'permissions' => ['students.export', 'website.manage'],
            'active' => true,
            'center_id' => $center->id,
            'job_title' => 'Reports reviewer',
            'specialization' => 'Quran memorization',
        ], $admin);

        $this->assertEqualsCanonicalizing(
            ['teacher', 'report-viewer'],
            $updated->getRoleNames()->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['students.export', 'website.manage'],
            $updated->getDirectPermissions()->pluck('name')->all(),
        );
        $this->assertTrue($updated->teacherProfile->active);
        $this->assertSame($center->id, $updated->teacherProfile->center_id);
        $this->assertTrue($updated->staffProfile->active);
        $this->assertSame($center->id, $updated->staffProfile->center_id);
    }

    public function test_current_and_last_super_admin_cannot_disable_or_demote_self(): void
    {
        $admin = $this->superAdmin();
        $access = app(AccessControlService::class);

        $this->assertSame(1, User::query()->role('super-admin')->where('active', true)->count());

        try {
            $access->updateAccess($admin, [
                'roles' => ['super-admin'],
                'permissions' => [],
                'active' => false,
            ], $admin);
            $this->fail('The current administrator was able to disable their own account.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('editRoles', $exception->errors());
        }

        try {
            $access->updateAccess($admin->fresh(), [
                'roles' => ['report-viewer'],
                'permissions' => [],
                'active' => true,
            ], $admin->fresh());
            $this->fail('The last administrator was able to remove their own super-admin role.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('editRoles', $exception->errors());
        }

        $admin->refresh();
        $this->assertTrue($admin->active);
        $this->assertTrue($admin->hasRole('super-admin'));
    }

    public function test_disabling_account_revokes_all_database_sessions_and_api_tokens(): void
    {
        $admin = $this->superAdmin();
        [$center] = $this->organization('DISABLE');
        $access = app(AccessControlService::class);
        $teacher = $this->teacherAccount($access, $admin, $center, 'disable@example.com');
        $token = $teacher->createToken('teacher-device', ['mobile:read'])->accessToken;
        $this->insertSession($teacher, 'teacher-session-disable');

        $access->updateAccess($teacher, [
            'roles' => ['teacher'],
            'permissions' => [],
            'active' => false,
            'center_id' => $center->id,
        ], $admin);

        $this->assertFalse($teacher->fresh()->active);
        $this->assertDatabaseMissing('sessions', ['id' => 'teacher-session-disable']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_administrator_password_reset_revokes_all_sessions_and_tokens(): void
    {
        $admin = $this->superAdmin();
        [$center] = $this->organization('PASSWORD');
        $access = app(AccessControlService::class);
        $teacher = $this->teacherAccount($access, $admin, $center, 'password-reset@example.com');
        $teacher->forceFill(['remember_token' => 'remember-this-session'])->save();
        $token = $teacher->createToken('password-reset-device', ['mobile:read'])->accessToken;
        $this->insertSession($teacher, 'teacher-session-password');

        $access->resetPassword($teacher, 'NewTeacherPass456!', $admin);

        $teacher->refresh();
        $this->assertTrue(Hash::check('NewTeacherPass456!', $teacher->password));
        $this->assertNull($teacher->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'teacher-session-password']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'access.account.password-reset',
            'auditable_id' => $teacher->id,
        ]);
    }

    public function test_direct_students_view_permission_does_not_leak_students_to_unscoped_account(): void
    {
        $admin = $this->superAdmin();
        [$staffCenter] = $this->organization('STAFF');
        [$studentCenter, $studentBranch] = $this->organization('PRIVATE');
        $access = app(AccessControlService::class);
        $viewer = $access->createAccount([
            'name' => 'Unscoped Website Editor',
            'email' => 'unscoped-viewer@example.com',
            'password' => 'ViewerPass123!',
            'roles' => ['website-editor'],
            'permissions' => ['students.view'],
            'center_id' => $staffCenter->id,
        ], $admin);
        $halaqa = Halaqa::query()->create([
            'center_id' => $studentCenter->id,
            'branch_id' => $studentBranch->id,
            'name' => 'Private Halaqa',
            'code' => 'HLQ-PRIVATE',
            'capacity' => 20,
            'active' => true,
        ]);
        $student = $this->studentInHalaqa($halaqa, 'PRIVATE-001', 'Private Student Record', $admin);

        $this->assertTrue($viewer->can('students.view'));
        $this->assertFalse(app(StudentVisibilityService::class)->canView($viewer, $student));
        $this->assertSame(0, app(StudentVisibilityService::class)->queryFor($viewer)->count());

        $this->actingAs($viewer)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('Private Student Record');
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['active' => true, 'archived_at' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    /** @return array{Center, Branch} */
    private function organization(string $suffix): array
    {
        $center = Center::query()->create([
            'name' => "Center {$suffix}",
            'code' => "CTR-{$suffix}",
            'active' => true,
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => "Internal {$suffix}",
            'code' => 'SYSTEM',
            'active' => true,
        ]);

        return [$center, $branch];
    }

    private function teacherAccount(
        AccessControlService $access,
        User $admin,
        Center $center,
        string $email,
    ): User {
        return $access->createAccount([
            'name' => 'Teacher Session Account',
            'email' => $email,
            'password' => 'TeacherPass123!',
            'roles' => ['teacher'],
            'permissions' => [],
            'center_id' => $center->id,
        ], $admin);
    }

    private function insertSession(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-session-payload',
            'last_activity' => now()->timestamp,
        ]);
    }

    private function studentInHalaqa(Halaqa $halaqa, string $number, string $name, User $actor): Student
    {
        return Student::query()->create([
            'student_number' => $number,
            'first_name' => 'Private',
            'father_name' => 'Student',
            'grandfather_name' => 'Test',
            'family_name' => 'Record',
            'full_name' => $name,
            'registration_date' => today(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
