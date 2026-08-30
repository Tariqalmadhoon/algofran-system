<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\HalaqaTeacherAssignment;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\PrimaryAccountsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrimaryAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_primary_accounts_are_reset_safely_and_idempotently_without_losing_teaching_history(): void
    {
        $center = Center::query()->create([
            'name' => 'Primary Test Center',
            'code' => 'PRIMARY-CTR',
            'active' => true,
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'Internal Primary Branch',
            'code' => 'SYSTEM',
            'active' => true,
        ]);

        $manager = User::factory()->create([
            'name' => 'Existing Manager Teacher',
            'email' => 'existing-manager@example.com',
        ]);
        $manager->assignRole(['center-manager', 'teacher']);
        $managerStaff = StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-PRIMARY-001',
            'job_title' => 'Center manager',
            'hired_at' => today()->subYear(),
            'active' => true,
        ]);
        $managerTeacher = TeacherProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-PRIMARY-001',
            'specialization' => 'Quran memorization',
            'hired_at' => today()->subYear(),
            'active' => true,
        ]);
        $managerHalaqa = $this->halaqa(
            $center,
            $branch,
            $managerTeacher,
            'Manager Halaqa',
            'HLQ-MANAGER',
        );
        $managerAssignment = $this->assignment($managerHalaqa, $managerTeacher, $manager);

        $teacher = User::factory()->create([
            'name' => 'Existing Primary Teacher',
            'email' => 'teacher1@gofran.local',
        ]);
        $teacher->assignRole('teacher');
        $teacherProfile = TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-PRIMARY-002',
            'specialization' => 'Tajweed',
            'hired_at' => today()->subMonths(6),
            'active' => true,
        ]);

        $historical = User::factory()->create([
            'name' => 'Historical Teacher',
            'email' => 'historical-teacher@example.com',
        ]);
        $historical->assignRole('teacher');
        $historicalProfile = TeacherProfile::query()->create([
            'user_id' => $historical->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-HISTORICAL-001',
            'specialization' => 'Historical records',
            'hired_at' => today()->subYears(2),
            'active' => true,
        ]);
        $historicalHalaqa = $this->halaqa(
            $center,
            $branch,
            $historicalProfile,
            'Historical Halaqa',
            'HLQ-HISTORICAL',
        );
        $historicalAssignment = $this->assignment($historicalHalaqa, $historicalProfile, $manager);

        $unrelated = User::factory()->create([
            'name' => 'Unrelated Account',
            'email' => 'unrelated-account@example.com',
        ]);

        $this->seed(PrimaryAccountsSeeder::class);

        $administrator = User::query()->where('email', 'admin1@gofran.com')->firstOrFail();
        $primaryTeacher = User::query()->where('email', 'teacher1@gofran.com')->firstOrFail();

        $this->assertSame($manager->id, $administrator->id);
        $this->assertSame($teacher->id, $primaryTeacher->id);
        $this->assertTrue($administrator->active);
        $this->assertNull($administrator->archived_at);
        $this->assertEqualsCanonicalizing(
            ['super-admin', 'teacher'],
            $administrator->getRoleNames()->all(),
        );
        $this->assertTrue(Hash::check('123456789', $administrator->password));
        $this->assertSame($managerStaff->id, $administrator->staffProfile->id);
        $this->assertSame($managerTeacher->id, $administrator->teacherProfile->id);
        $this->assertTrue($administrator->teacherProfile->active);
        $this->assertDatabaseHas('halaqa_teacher_assignments', [
            'id' => $managerAssignment->id,
            'halaqa_id' => $managerHalaqa->id,
            'teacher_profile_id' => $managerTeacher->id,
            'ends_at' => null,
        ]);

        $this->assertTrue($primaryTeacher->active);
        $this->assertNull($primaryTeacher->archived_at);
        $this->assertTrue($primaryTeacher->hasExactRoles(['teacher']));
        $this->assertTrue(Hash::check('123456789', $primaryTeacher->password));
        $this->assertSame($teacherProfile->id, $primaryTeacher->teacherProfile->id);
        $this->assertTrue($primaryTeacher->teacherProfile->active);

        $this->assertDatabaseMissing('users', ['id' => $unrelated->id]);
        $historical->refresh();
        $historicalProfile->refresh();
        $this->assertFalse($historical->active);
        $this->assertNotNull($historical->archived_at);
        $this->assertStringStartsWith("archived-{$historical->id}-", $historical->email);
        $this->assertStringEndsWith('@invalid.local', $historical->email);
        $this->assertCount(0, $historical->getRoleNames());
        $this->assertFalse($historicalProfile->active);
        $this->assertDatabaseHas('halaqa_teacher_assignments', [
            'id' => $historicalAssignment->id,
            'halaqa_id' => $historicalHalaqa->id,
            'teacher_profile_id' => $historicalProfile->id,
            'ends_at' => null,
        ]);

        $this->assertSame(2, $this->activeAccountQuery()->count());
        $this->assertSame(3, User::query()->count());
        $this->assertSame(2, HalaqaTeacherAssignment::query()->count());

        $archivedEmail = $historical->email;
        $archivedAt = $historical->archived_at->toISOString();
        $administratorId = $administrator->id;
        $primaryTeacherId = $primaryTeacher->id;

        $this->seed(PrimaryAccountsSeeder::class);

        $administrator = User::query()->where('email', 'admin1@gofran.com')->firstOrFail();
        $primaryTeacher = User::query()->where('email', 'teacher1@gofran.com')->firstOrFail();
        $historical->refresh();

        $this->assertSame($administratorId, $administrator->id);
        $this->assertSame($primaryTeacherId, $primaryTeacher->id);
        $this->assertSame($managerStaff->id, $administrator->staffProfile->id);
        $this->assertSame($managerTeacher->id, $administrator->teacherProfile->id);
        $this->assertSame($teacherProfile->id, $primaryTeacher->teacherProfile->id);
        $this->assertTrue(Hash::check('123456789', $administrator->password));
        $this->assertTrue(Hash::check('123456789', $primaryTeacher->password));
        $this->assertSame($archivedEmail, $historical->email);
        $this->assertSame($archivedAt, $historical->archived_at->toISOString());
        $this->assertSame(2, $this->activeAccountQuery()->count());
        $this->assertSame(3, User::query()->count());
        $this->assertSame(2, HalaqaTeacherAssignment::query()->count());
        $this->assertDatabaseHas('halaqa_teacher_assignments', ['id' => $managerAssignment->id]);
        $this->assertDatabaseHas('halaqa_teacher_assignments', ['id' => $historicalAssignment->id]);
    }

    private function halaqa(
        Center $center,
        Branch $branch,
        TeacherProfile $teacher,
        string $name,
        string $code,
    ): Halaqa {
        return Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => $name,
            'code' => $code,
            'capacity' => 20,
            'active' => true,
            'start_date' => today()->subYear(),
        ]);
    }

    private function assignment(Halaqa $halaqa, TeacherProfile $teacher, User $actor): HalaqaTeacherAssignment
    {
        return HalaqaTeacherAssignment::query()->create([
            'halaqa_id' => $halaqa->id,
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subYear(),
            'ends_at' => null,
            'assigned_by' => $actor->id,
        ]);
    }

    private function activeAccountQuery()
    {
        return User::query()
            ->where('active', true)
            ->whereNull('archived_at');
    }
}
