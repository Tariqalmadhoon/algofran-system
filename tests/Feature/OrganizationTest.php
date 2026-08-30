<?php

namespace Tests\Feature;

use App\Livewire\OrganizationManager;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\User;
use App\Services\OrganizationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_organization_screen_is_protected_by_permission(): void
    {
        $user = User::factory()->create();

        $this->get('/organization')->assertRedirect('/login');
        $this->actingAs($user)->get('/organization')->assertForbidden();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher)->get('/organization')->assertForbidden();

        $user->assignRole('center-manager');
        $this->actingAs($user)->get('/organization')->assertOk()->assertSee('الهيكل التنظيمي');
    }

    public function test_center_manager_can_also_have_an_official_teacher_profile_and_halaqa(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $center = Center::query()->create(['name' => 'مركز الأمل', 'code' => 'HOPE']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'السجل الداخلي', 'code' => 'SYSTEM']);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة المدير الرسمية',
            'code' => 'HLQ-MANAGER',
            'capacity' => 20,
            'active' => true,
        ]);

        $this->actingAs($manager);
        $teacher = app(OrganizationService::class)->createTeacherProfileForManager($manager, [
            'specialization' => 'حفظ وتجويد',
        ]);
        $assignment = app(OrganizationService::class)->assignTeacher([
            'halaqa_id' => $halaqa->id,
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);
        $duplicateAssignment = app(OrganizationService::class)->assignTeacher([
            'halaqa_id' => $halaqa->id,
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);
        $reactivatedProfile = app(OrganizationService::class)->createTeacherProfileForManager($manager);

        $this->assertTrue($manager->can('organization.manage'));
        $this->assertTrue($manager->can('students.archive'));
        $this->assertTrue($manager->can('recitations.create'));
        $this->assertTrue($manager->hasRole('teacher'));
        $this->assertFalse($manager->requiresTeacherAssignmentScope());
        $this->assertSame($manager->id, $teacher->user_id);
        $this->assertSame($teacher->id, $reactivatedProfile->id);
        $this->assertSame($assignment->id, $duplicateAssignment->id);
        $this->assertDatabaseCount('halaqa_teacher_assignments', 1);
        $this->assertSame($teacher->id, $halaqa->fresh()->primary_teacher_id);
        $this->get(route('teacher.daily'))->assertOk()->assertSee('تصدير سجلات الحفظ');
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('مركز الأمل')
            ->assertSee('حلقة المدير الرسمية')
            ->assertSee('مدير المركز · محفّظ')
            ->assertSee('مساحتي كمحفّظ')
            ->assertSee('التسجيل اليومي')
            ->assertSee('تنبيهات طلاب حلقاتي')
            ->assertSee('فتح التسجيل اليومي')
            ->assertSee('تنبيهات الطلاب');
        $this->get(route('alerts.index', ['scope' => 'teaching']))
            ->assertOk()
            ->assertSee('نطاق المحفّظ · طلاب حلقاتي فقط');

        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة إدارية أخرى',
            'code' => 'HLQ-OTHER',
            'capacity' => 20,
            'active' => true,
        ]);
        $ownStudent = $this->studentInHalaqa($manager, $halaqa, 'MGR-OWN', 'طالب حلقة المدير');
        $otherStudent = $this->studentInHalaqa($manager, $otherHalaqa, 'MGR-OTHER', 'طالب الحلقة الأخرى');
        StudentAlert::query()->create([
            'student_id' => $ownStudent->id,
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $halaqa->id,
            'fingerprint' => 'manager-own-alert',
            'type' => 'followup',
            'severity' => 'warning',
            'reason' => 'تنبيه خاص بطلاب حلقة المدير',
            'status' => 'open',
            'generated_at' => now(),
        ]);
        StudentAlert::query()->create([
            'student_id' => $otherStudent->id,
            'halaqa_id' => $otherHalaqa->id,
            'fingerprint' => 'other-halaqa-alert',
            'type' => 'followup',
            'severity' => 'warning',
            'reason' => 'تنبيه إداري لحلقة أخرى',
            'status' => 'open',
            'generated_at' => now(),
        ]);

        $this->get(route('alerts.index', ['scope' => 'teaching']))
            ->assertOk()
            ->assertSee('تنبيه خاص بطلاب حلقة المدير')
            ->assertDontSee('تنبيه إداري لحلقة أخرى');
        $this->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('تنبيه خاص بطلاب حلقة المدير')
            ->assertSee('تنبيه إداري لحلقة أخرى');
    }

    public function test_authorized_user_can_create_center_through_livewire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        Livewire::actingAs($user)
            ->test(OrganizationManager::class)
            ->set('centerName', 'مركز الفرقان')
            ->set('centerPhone', '0599000000')
            ->call('saveCenter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('centers', ['name' => 'مركز الفرقان', 'code' => 'CTR-001']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'center.created', 'user_id' => $user->id]);
    }

    public function test_organization_uses_center_to_halaqa_structure_without_exposing_branches(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $center = Center::query()->create(['name' => 'مركز الاختبار', 'code' => 'TEST']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'السجل الداخلي للمركز', 'code' => 'SYSTEM']);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-ORG-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('organization.index'))
            ->assertOk()
            ->assertSee('المراكز والحلقات')
            ->assertSee('الهيكل الشجري للمراكز والحلقات')
            ->assertDontSeeHtml('wire:model="halaqaProgram"')
            ->assertDontSeeHtml('wire:model="halaqaRoom"')
            ->assertDontSee('الفرع');

        Livewire::actingAs($manager)
            ->test(OrganizationManager::class)
            ->set('activeForm', 'halaqa')
            ->set('halaqaCenterId', $center->id)
            ->set('halaqaName', 'حلقة المركز')
            ->set('halaqaCapacity', 18)
            ->call('saveHalaqa')
            ->assertHasNoErrors();

        $halaqa = Halaqa::query()->where('name', 'حلقة المركز')->firstOrFail();
        $this->assertSame($center->id, $halaqa->center_id);
        $this->assertNotNull($halaqa->branch_id);
        $this->assertDatabaseHas('branches', [
            'id' => $halaqa->branch_id,
            'center_id' => $center->id,
            'name' => 'السجل الداخلي للمركز',
        ]);
    }

    public function test_organization_service_preserves_teacher_assignment_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $service = app(OrganizationService::class);

        $center = $service->createCenter(['name' => 'المركز الرئيس', 'code' => 'MAIN']);
        $branch = $service->createBranch(['center_id' => $center->id, 'name' => 'الفرع الأول', 'code' => 'B1']);
        $teacher = $service->createTeacher([
            'name' => 'أحمد علي', 'email' => 'teacher@example.com', 'password' => 'Teacher123!',
            'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-001',
            'specialization' => 'حفظ وتجويد',
        ]);
        $halaqa = $service->createHalaqa([
            'center_id' => $center->id, 'branch_id' => $branch->id, 'name' => 'حلقة الإتقان',
            'code' => 'H1', 'capacity' => 20,
        ]);
        $service->createSchedule([
            'halaqa_id' => $halaqa->id, 'weekday' => 0, 'starts_at' => '08:00', 'ends_at' => '10:00',
        ]);
        $firstAssignment = $service->assignTeacher([
            'halaqa_id' => $halaqa->id, 'teacher_profile_id' => $teacher->id,
            'role' => 'primary', 'starts_at' => today()->subDays(7)->toDateString(),
        ]);

        $replacement = $service->createTeacher([
            'name' => 'خالد حسن', 'email' => 'replacement@example.com', 'password' => 'Teacher456!',
            'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-002',
            'specialization' => 'تجويد',
        ]);
        $assignment = $service->assignTeacher([
            'halaqa_id' => $halaqa->id, 'teacher_profile_id' => $replacement->id,
            'role' => 'primary', 'starts_at' => today()->toDateString(),
        ]);

        $this->assertSame($replacement->id, $halaqa->fresh()->primary_teacher_id);
        $this->assertSame(today()->subDay()->toDateString(), $firstAssignment->fresh()->ends_at->toDateString());
        $this->assertSame($admin->id, $assignment->assigned_by);
        $this->assertDatabaseHas('halaqa_schedules', ['halaqa_id' => $halaqa->id, 'weekday' => 0]);
        $this->assertGreaterThanOrEqual(8, AuditLog::query()->count());
    }

    public function test_organization_service_assigns_scoped_sequential_identifiers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $service = app(OrganizationService::class);

        $firstCenter = $service->createCenter(['name' => 'المركز الأول']);
        $secondCenter = $service->createCenter(['name' => 'المركز الثاني']);
        $firstBranch = $service->createBranch(['center_id' => $firstCenter->id, 'name' => 'الفرع الأول']);
        $secondBranch = $service->createBranch(['center_id' => $firstCenter->id, 'name' => 'الفرع الثاني']);
        $otherCenterBranch = $service->createBranch(['center_id' => $secondCenter->id, 'name' => 'فرع المركز الثاني']);

        $firstHalaqa = $service->createHalaqa([
            'center_id' => $firstCenter->id, 'branch_id' => $firstBranch->id,
            'name' => 'حلقة أولى', 'capacity' => 20,
        ]);
        $secondHalaqa = $service->createHalaqa([
            'center_id' => $firstCenter->id, 'branch_id' => $firstBranch->id,
            'name' => 'حلقة ثانية', 'capacity' => 20,
        ]);
        $otherBranchHalaqa = $service->createHalaqa([
            'center_id' => $firstCenter->id, 'branch_id' => $secondBranch->id,
            'name' => 'حلقة الفرع الثاني', 'capacity' => 20,
        ]);
        $staff = $service->createStaff([
            'name' => 'موظف اختبار', 'email' => 'staff-sequence@example.com', 'password' => 'StaffSequence123!',
            'center_id' => $firstCenter->id, 'branch_id' => $firstBranch->id,
            'job_title' => 'مسجل', 'role' => 'registrar',
        ]);
        $teacher = $service->createTeacher([
            'name' => 'محفظ اختبار', 'email' => 'teacher-sequence@example.com', 'password' => 'TeacherSequence123!',
            'center_id' => $firstCenter->id, 'branch_id' => $firstBranch->id,
            'specialization' => 'حفظ وتجويد',
        ]);

        $this->assertSame('CTR-001', $firstCenter->code);
        $this->assertSame('CTR-002', $secondCenter->code);
        $this->assertSame('BR-001', $firstBranch->code);
        $this->assertSame('BR-002', $secondBranch->code);
        $this->assertSame('BR-001', $otherCenterBranch->code);
        $this->assertSame('HLQ-001', $firstHalaqa->code);
        $this->assertSame('HLQ-002', $secondHalaqa->code);
        $this->assertSame('HLQ-001', $otherBranchHalaqa->code);
        $this->assertSame('STF-0001', $staff->employee_number);
        $this->assertSame('TCH-0001', $teacher->employee_number);
    }

    public function test_halaqa_rejects_branch_from_another_center(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $service = app(OrganizationService::class);
        $first = Center::query()->create(['name' => 'الأول', 'code' => 'C1']);
        $second = Center::query()->create(['name' => 'الثاني', 'code' => 'C2']);
        $branch = Branch::query()->create(['center_id' => $second->id, 'name' => 'فرع', 'code' => 'B1']);

        $this->expectException(ValidationException::class);
        $service->createHalaqa([
            'center_id' => $first->id, 'branch_id' => $branch->id,
            'name' => 'حلقة غير صالحة', 'code' => 'HX', 'capacity' => 10,
        ]);

        $this->assertDatabaseMissing('halaqas', ['code' => 'HX']);
    }

    private function studentInHalaqa(User $actor, Halaqa $halaqa, string $number, string $name): Student
    {
        return Student::query()->create([
            'student_number' => $number,
            'first_name' => $name,
            'father_name' => 'اختبار',
            'grandfather_name' => 'نطاق',
            'family_name' => 'التنبيهات',
            'full_name' => $name,
            'registration_date' => today(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
