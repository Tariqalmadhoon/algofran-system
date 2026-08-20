<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExport;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Guardian;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\ReportDataService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSixSecurityAndReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_security_headers_are_attached_to_web_and_api_responses(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_api_tokens_expire_rotate_per_device_require_ability_and_are_revoked_for_inactive_users(): void
    {
        $user = User::factory()->create(['email' => 'mobile@example.com', 'password' => Hash::make('MobilePass123!')]);

        $first = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'MobilePass123!',
            'device_name' => 'qa-device',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user']]);

        $second = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'MobilePass123!',
            'device_name' => 'qa-device',
        ])->assertOk();

        $this->assertNotSame($first->json('data.token'), $second->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull($user->tokens()->firstOrFail()->expires_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'api-token.created']);

        $user->update(['active' => false]);
        $this->withToken($second->json('data.token'))->getJson('/api/v1/user')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'account_inactive');
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $abilityUser = User::factory()->create();
        Sanctum::actingAs($abilityUser, ['unrelated:ability']);
        $this->getJson('/api/v1/user')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
    }

    public function test_api_authorization_and_filter_validation_fail_closed(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $this->completeTwoFactorAuthentication($manager);
        Sanctum::actingAs($manager, ['mobile:read']);

        $this->getJson('/api/v1/daily-records?from=not-a-date')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['from']]]);

        $this->getJson('/api/v1/calendar?from=2024-01-01&to=2026-01-02')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['to']]]);

        $editor = User::factory()->create();
        $editor->assignRole('website-editor');
        $this->completeTwoFactorAuthentication($editor);
        Sanctum::actingAs($editor, ['mobile:read']);
        $this->getJson('/api/v1/students')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
    }

    public function test_api_login_rate_limit_is_enforced(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'unknown@example.com',
                'password' => 'WrongPassword123!',
                'device_name' => 'rate-test',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'WrongPassword123!',
            'device_name' => 'rate-test',
        ])->assertTooManyRequests();
    }

    public function test_password_change_revokes_all_mobile_tokens(): void
    {
        $user = User::factory()->create();
        $user->tokens()->create(['name' => 'old-phone', 'token' => hash('sha256', 'old-token'), 'abilities' => ['mobile:read']]);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_role_access_matrix_and_report_viewer_data_scope(): void
    {
        [$center, $branch, $halaqa] = $this->organization();
        $student = $this->student($halaqa, 'QA-ROLE-001');

        $superAdmin = $this->userWithRole('super-admin');
        $manager = $this->userWithRole('center-manager');
        $supervisor = $this->userWithRole('academic-supervisor');
        $registrar = $this->userWithRole('registrar');
        $teacher = $this->userWithRole('teacher');
        $guardian = $this->userWithRole('guardian');
        $studentUser = $this->userWithRole('student');
        $editor = $this->userWithRole('website-editor');
        $reportViewer = $this->userWithRole('report-viewer');

        $teacherProfile = TeacherProfile::query()->create([
            'user_id' => $teacher->id, 'center_id' => $center->id, 'branch_id' => $branch->id,
            'employee_number' => 'QA-T-001', 'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacherProfile->id, 'role' => 'primary', 'starts_at' => today()->subDay(),
        ]);
        $guardianProfile = Guardian::query()->create(['user_id' => $guardian->id, 'full_name' => $guardian->name, 'phone' => '0590000000']);
        $guardianProfile->students()->attach($student->id, ['relationship' => 'father', 'is_primary' => true, 'can_receive_notifications' => true]);
        $student->update(['user_id' => $studentUser->id]);

        $this->actingAs($superAdmin)->get(route('cms.index'))->assertOk();
        $this->actingAs($manager)->get(route('academic.index'))->assertOk();
        $this->actingAs($manager)->get(route('cms.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('alerts.index'))->assertOk();
        $this->actingAs($registrar)->get(route('students.index'))->assertOk();
        $this->actingAs($registrar)->get(route('academic.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.daily'))->assertOk();
        $this->actingAs($teacher)->get(route('academic.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('students.show', $student))->assertOk();
        $this->actingAs($guardian)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($studentUser)->get(route('students.show', $student))->assertOk();
        $this->actingAs($editor)->get(route('cms.index'))->assertOk();
        $this->actingAs($editor)->get(route('students.index'))->assertForbidden();
        $this->actingAs($reportViewer)->get(route('reports.index'))->assertOk();
        $this->actingAs($reportViewer)->get(route('students.index'))->assertForbidden();

        $report = app(ReportDataService::class)->build('students', [], $reportViewer);
        $this->assertCount(1, $report['rows']);
        $this->assertSame('QA-ROLE-001', $report['rows'][0][0]);
    }

    public function test_queue_retry_window_exceeds_the_long_report_job_timeout(): void
    {
        $this->assertGreaterThan((new GenerateReportExport(1))->timeout, config('queue.connections.database.retry_after'));
    }

    /** @return array{Center, Branch, Halaqa} */
    private function organization(): array
    {
        $center = Center::query()->create(['name' => 'QA Center', 'code' => 'QA-CENTER']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'QA Branch', 'code' => 'QA-BRANCH']);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id, 'branch_id' => $branch->id, 'name' => 'QA Halaqa',
            'code' => 'QA-HALAQA', 'capacity' => 20, 'active' => true,
        ]);

        return [$center, $branch, $halaqa];
    }

    private function student(Halaqa $halaqa, string $number): Student
    {
        return Student::query()->create([
            'student_number' => $number, 'first_name' => 'QA', 'father_name' => 'Student',
            'grandfather_name' => 'Test', 'family_name' => $number, 'full_name' => 'QA Student',
            'registration_date' => today(), 'status' => 'active', 'current_halaqa_id' => $halaqa->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
