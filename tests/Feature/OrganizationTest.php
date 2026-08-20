<?php

namespace Tests\Feature;

use App\Livewire\OrganizationManager;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Center;
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

        $user->assignRole('center-manager');
        $this->actingAs($user)->get('/organization')->assertOk()->assertSee('الهيكل التنظيمي');
    }

    public function test_authorized_user_can_create_center_through_livewire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('center-manager');

        Livewire::actingAs($user)
            ->test(OrganizationManager::class)
            ->set('centerName', 'مركز الفرقان')
            ->set('centerCode', 'FRQ')
            ->set('centerPhone', '0599000000')
            ->call('saveCenter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('centers', ['name' => 'مركز الفرقان', 'code' => 'FRQ']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'center.created', 'user_id' => $user->id]);
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
            'role' => 'primary', 'starts_at' => '2026-08-19',
        ]);

        $replacement = $service->createTeacher([
            'name' => 'خالد حسن', 'email' => 'replacement@example.com', 'password' => 'Teacher456!',
            'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-002',
            'specialization' => 'تجويد',
        ]);
        $assignment = $service->assignTeacher([
            'halaqa_id' => $halaqa->id, 'teacher_profile_id' => $replacement->id,
            'role' => 'primary', 'starts_at' => '2026-09-01',
        ]);

        $this->assertSame($replacement->id, $halaqa->fresh()->primary_teacher_id);
        $this->assertSame('2026-08-31', $firstAssignment->fresh()->ends_at->toDateString());
        $this->assertSame($admin->id, $assignment->assigned_by);
        $this->assertDatabaseHas('halaqa_schedules', ['halaqa_id' => $halaqa->id, 'weekday' => 0]);
        $this->assertGreaterThanOrEqual(8, AuditLog::query()->count());
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
}
