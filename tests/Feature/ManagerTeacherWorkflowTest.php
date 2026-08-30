<?php

namespace Tests\Feature;

use App\Actions\Students\CreateStudentAction;
use App\Livewire\TeacherDailyRecorder;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\OrganizationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerTeacherWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_and_center_manager_can_be_atomically_activated_and_assigned_in_their_center(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('super-admin');
        $this->actingAs($actor);

        foreach (['super-admin', 'center-manager'] as $index => $managerRole) {
            [$center, $branch] = $this->organization('OWN-'.($index + 1));
            $manager = $this->managerWithStaff($managerRole, $center, $branch, $index + 1);
            $halaqa = $this->halaqa($center, $branch, 'OWN-HALAQA-'.($index + 1));

            app(OrganizationService::class)->activateManagerAsTeacherAndAssign($manager, [
                'halaqa_id' => $halaqa->id,
                'role' => 'primary',
                'starts_at' => today()->startOfDay(),
                'specialization' => 'حفظ وتجويد',
            ]);

            $manager->refresh()->load('teacherProfile');
            $teacher = $manager->teacherProfile;

            $this->assertTrue($manager->hasRole($managerRole));
            $this->assertTrue($manager->hasRole('teacher'));
            $this->assertNotNull($teacher);
            $this->assertTrue($teacher->active);
            $this->assertSame($center->id, $teacher->center_id);
            $this->assertDatabaseHas('halaqa_teacher_assignments', [
                'halaqa_id' => $halaqa->id,
                'teacher_profile_id' => $teacher->id,
                'role' => 'primary',
                'starts_at' => today()->startOfDay(),
                'ends_at' => null,
                'assigned_by' => $actor->id,
            ]);
            $this->assertSame($teacher->id, $halaqa->fresh()->primary_teacher_id);
        }
    }

    public function test_activation_and_assignment_roll_back_together_when_halaqa_is_outside_the_managers_center(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('super-admin');
        $this->actingAs($actor);

        [$managerCenter, $managerBranch] = $this->organization('MANAGER-CENTER');
        [$otherCenter, $otherBranch] = $this->organization('OTHER-CENTER');
        $manager = $this->managerWithStaff('center-manager', $managerCenter, $managerBranch, 10);
        $foreignHalaqa = $this->halaqa($otherCenter, $otherBranch, 'FOREIGN-HALAQA');

        try {
            app(OrganizationService::class)->activateManagerAsTeacherAndAssign($manager, [
                'halaqa_id' => $foreignHalaqa->id,
                'role' => 'primary',
                'starts_at' => today()->toDateString(),
            ]);
            $this->fail('Expected an out-of-center Halaqa to reject the atomic activation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('halaqa_id', $exception->errors());
        }

        $this->assertNull($manager->fresh()->teacherProfile);
        $this->assertFalse($manager->fresh()->hasRole('teacher'));
        $this->assertDatabaseMissing('halaqa_teacher_assignments', [
            'halaqa_id' => $foreignHalaqa->id,
        ]);
    }

    public function test_manager_teacher_sees_daily_workspace_and_student_after_an_active_enrollment(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [$center, $branch] = $this->organization('DAILY-CENTER');
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-DAILY-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $halaqa = $this->halaqa($center, $branch, 'MANAGER-DAILY-HALAQA');

        $this->actingAs($manager);
        app(OrganizationService::class)->activateManagerAsTeacherAndAssign($manager, [
            'halaqa_id' => $halaqa->id,
            'role' => 'primary',
            'starts_at' => today()->startOfDay(),
        ]);
        $student = $this->createEnrolledStudent($manager, $halaqa, 'MGR-DAILY-001');

        $this->assertDatabaseHas('halaqa_enrollments', [
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'starts_at' => today()->startOfDay(),
            'ends_at' => null,
        ]);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('مساحتي كمحفّظ')
            ->assertSee($halaqa->name);
        $this->get(route('teacher.daily'))
            ->assertOk()
            ->assertSee($halaqa->name)
            ->assertSee($student->full_name);
        Livewire::actingAs($manager)
            ->test(TeacherDailyRecorder::class)
            ->assertSet('teacherProfileId', $manager->fresh()->teacherProfile->id)
            ->assertSet('halaqaId', (string) $halaqa->id)
            ->assertSee($student->full_name);
    }

    public function test_manager_teacher_cannot_tamper_with_teacher_profile_id_and_record_as_another_teacher(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [$center, $branch] = $this->organization('TAMPER-CENTER');
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-TAMPER-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $halaqa = $this->halaqa($center, $branch, 'TAMPER-HALAQA');

        $this->actingAs($manager);
        app(OrganizationService::class)->activateManagerAsTeacherAndAssign($manager, [
            'halaqa_id' => $halaqa->id,
            'role' => 'primary',
            'starts_at' => today()->toDateString(),
        ]);
        $student = $this->createEnrolledStudent($manager, $halaqa, 'MGR-TAMPER-001');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('teacher');
        $otherTeacher = TeacherProfile::query()->create([
            'user_id' => $otherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-OTHER-PROFILE',
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $otherTeacher->id,
            'role' => 'assistant',
            'starts_at' => today()->toDateString(),
            'assigned_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(TeacherDailyRecorder::class)
            ->call('selectStudent', $student->id)
            ->assertSet('studentId', (string) $student->id)
            ->set('attendanceStatus', 'absent')
            ->set('teacherProfileId', $otherTeacher->id)
            ->call('save')
            ->assertHasErrors(['teacherProfileId']);

        $this->assertDatabaseMissing('daily_records', [
            'student_id' => $student->id,
            'teacher_profile_id' => $otherTeacher->id,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'record_date' => today()->toDateString(),
        ]);
    }

    /** @return array{Center, Branch} */
    private function organization(string $code): array
    {
        $center = Center::query()->create([
            'name' => 'مركز '.$code,
            'code' => $code,
            'active' => true,
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'السجل الداخلي',
            'code' => 'SYSTEM',
            'active' => true,
        ]);

        return [$center, $branch];
    }

    private function managerWithStaff(string $role, Center $center, Branch $branch, int $sequence): User
    {
        $manager = User::factory()->create();
        $manager->assignRole($role);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-MANAGER-'.$sequence,
            'job_title' => $role === 'super-admin' ? 'مدير النظام' : 'مدير المركز',
            'active' => true,
        ]);

        return $manager;
    }

    private function halaqa(Center $center, Branch $branch, string $code): Halaqa
    {
        return Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة '.$code,
            'code' => $code,
            'capacity' => 20,
            'active' => true,
        ]);
    }

    private function createEnrolledStudent(User $actor, Halaqa $halaqa, string $number)
    {
        return app(CreateStudentAction::class)->execute([
            'student_number' => $number,
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'علي',
            'family_name' => 'الغفران',
            'identity_number' => null,
            'birth_date' => '2014-01-01',
            'contact_phone' => null,
            'registration_date' => today()->toDateString(),
            'status' => 'active',
            'halaqa_id' => $halaqa->id,
            'notes' => null,
        ], $actor);
    }
}
