<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileStudentOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_offline_create_is_sequential_idempotent_and_does_not_expose_identity(): void
    {
        [$user, , $halaqa] = $this->teacherWorkspace();
        [$token, $deviceUuid] = $this->loginDevice($user);
        $operationUuid = (string) Str::uuid();
        $clientUuid = (string) Str::uuid();
        $operation = $this->operation($operationUuid, 'create', [
            'client_uuid' => $clientUuid,
            'halaqa_id' => $halaqa->id,
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'علي',
            'family_name' => 'الغفران',
            'birth_date' => '2014-01-01',
            'contact_phone' => '0599000001',
            'registration_date' => today()->toDateString(),
            'notes' => 'أضيف دون اتصال',
        ]);

        $first = $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.0.client_uuid', $clientUuid)
            ->assertJsonPath('data.results.0.student.student_number', 'STD-00001')
            ->assertJsonPath('data.results.0.student.full_name', 'أحمد محمد علي الغفران')
            ->assertJsonPath('data.results.0.student.halaqa.id', $halaqa->id)
            ->assertJsonPath('data.summary.accepted', 1)
            ->assertJsonMissingPath('data.results.0.student.identity_number');
        $this->assertNotNull($first->json('data.results.0.student.updated_at'));

        $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'already_processed')
            ->assertJsonPath('data.results.0.original_status', 'accepted')
            ->assertJsonPath('data.results.0.student.id', $first->json('data.results.0.student.id'));

        $changed = $operation;
        $changed['student']['notes'] = 'بيانات مختلفة';
        $this->push($token, $deviceUuid, [$changed])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.error.code', 'idempotency_key_reused');

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('mobile_sync_operations', 1);
        $this->assertDatabaseHas('students', ['student_number' => 'STD-00001']);

        $sensitivePayload = $operation;
        $sensitivePayload['operation_uuid'] = (string) Str::uuid();
        $sensitivePayload['student']['client_uuid'] = (string) Str::uuid();
        $sensitivePayload['student']['identity_number'] = '900000002';
        $this->push($token, $deviceUuid, [$sensitivePayload])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'rejected')
            ->assertJsonPath('data.results.0.error.code', 'validation_failed');
        $this->assertDatabaseMissing('students', ['identity_number' => '900000002']);
    }

    public function test_update_uses_optimistic_conflicts_and_replays_an_accepted_operation(): void
    {
        [$user, , $halaqa] = $this->teacherWorkspace();
        $student = $this->student($halaqa, 'EXISTING-001');
        [$token, $deviceUuid] = $this->loginDevice($user);
        $baseUpdatedAt = $student->updated_at->toISOString();
        $operation = $this->operation((string) Str::uuid(), 'update', [
            'id' => $student->id,
            'base_updated_at' => $baseUpdatedAt,
            'first_name' => 'محمود',
            'contact_phone' => '0599111111',
        ]);

        $this->travel(1)->seconds();
        $accepted = $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.0.student.first_name', 'محمود')
            ->assertJsonPath('data.results.0.student.contact_phone', '0599111111');

        $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'already_processed')
            ->assertJsonPath('data.results.0.student.updated_at', $accepted->json('data.results.0.student.updated_at'));

        $stale = $this->operation((string) Str::uuid(), 'update', [
            'id' => $student->id,
            'base_updated_at' => $baseUpdatedAt,
            'notes' => 'تعديل قديم',
        ]);
        $this->push($token, $deviceUuid, [$stale])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.error.code', 'student_changed_on_server')
            ->assertJsonPath('data.results.0.server_student.first_name', 'محمود');

        $this->assertSame('محمود', $student->fresh()->first_name);
        $this->assertNull($student->fresh()->notes);
    }

    public function test_partial_batch_rejects_idor_for_manager_teacher_and_continues_other_operations(): void
    {
        [$manager, , $assignedHalaqa] = $this->teacherWorkspace('center-manager');
        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $assignedHalaqa->center_id,
            'branch_id' => $assignedHalaqa->branch_id,
            'name' => 'حلقة في المركز غير مسندة',
            'code' => 'OTHER-'.Str::random(6),
            'capacity' => 30,
            'active' => true,
        ]);
        $outsideStudent = $this->student($otherHalaqa, 'OUTSIDE-001');
        [$token, $deviceUuid] = $this->loginDevice($manager);

        $validCreate = $this->operation((string) Str::uuid(), 'create', [
            'client_uuid' => (string) Str::uuid(),
            'halaqa_id' => $assignedHalaqa->id,
            'first_name' => 'طالب',
            'father_name' => 'جديد',
            'grandfather_name' => 'داخل',
            'family_name' => 'الحلقة',
            'registration_date' => today()->toDateString(),
        ]);
        $unassignedCreate = $validCreate;
        $unassignedCreate['operation_uuid'] = (string) Str::uuid();
        $unassignedCreate['student']['client_uuid'] = (string) Str::uuid();
        $unassignedCreate['student']['halaqa_id'] = $otherHalaqa->id;
        $outsideUpdate = $this->operation((string) Str::uuid(), 'update', [
            'id' => $outsideStudent->id,
            'base_updated_at' => $outsideStudent->updated_at->toISOString(),
            'notes' => 'محاولة IDOR',
        ]);
        $invalidCreate = $this->operation((string) Str::uuid(), 'create', [
            'client_uuid' => (string) Str::uuid(),
            'halaqa_id' => $assignedHalaqa->id,
            'first_name' => 'ناقص',
            'registration_date' => today()->toDateString(),
        ]);

        $this->push($token, $deviceUuid, [$unassignedCreate, $validCreate, $outsideUpdate, $invalidCreate])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'rejected')
            ->assertJsonPath('data.results.0.error.code', 'student_not_available')
            ->assertJsonPath('data.results.1.status', 'accepted')
            ->assertJsonPath('data.results.2.status', 'rejected')
            ->assertJsonPath('data.results.2.error.code', 'student_not_available')
            ->assertJsonPath('data.results.3.status', 'rejected')
            ->assertJsonPath('data.results.3.error.code', 'validation_failed')
            ->assertJsonPath('data.summary.total', 4)
            ->assertJsonPath('data.summary.accepted', 1)
            ->assertJsonPath('data.summary.rejected', 3)
            ->assertJsonPath('data.summary.failed', 0);

        $this->assertNull($outsideStudent->fresh()->notes);
        $this->assertDatabaseHas('students', ['full_name' => 'طالب جديد داخل الحلقة']);
    }

    public function test_archive_preserves_records_ends_enrollment_and_never_soft_deletes_student(): void
    {
        [$user, $teacher, $halaqa] = $this->teacherWorkspace();
        $student = $this->student($halaqa, 'ARCHIVE-001');
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'record_date' => today(),
            'status' => 'present',
            'recorded_by' => $user->id,
        ]);
        DailyRecord::query()->create([
            'student_id' => $student->id,
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $halaqa->id,
            'attendance_id' => $attendance->id,
            'record_date' => today(),
            'general_evaluation' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        [$token, $deviceUuid] = $this->loginDevice($user);
        $operation = $this->operation((string) Str::uuid(), 'archive', [
            'id' => $student->id,
            'base_updated_at' => $student->updated_at->toISOString(),
        ]);

        $this->travel(1)->seconds();
        $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.0.student.status', 'archived')
            ->assertJsonPath('data.results.0.student.halaqa', null);

        $archived = Student::query()->findOrFail($student->id);
        $this->assertSame('archived', $archived->status->value);
        $this->assertNull($archived->current_halaqa_id);
        $this->assertNull($archived->deleted_at);
        $this->assertDatabaseHas('halaqa_enrollments', [
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'ends_at' => today()->toDateString(),
        ]);
        $this->assertDatabaseHas('daily_records', ['student_id' => $student->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.archived']);
        $this->assertDatabaseHas('student_timeline_events', [
            'student_id' => $student->id,
            'event_type' => 'student.status-changed',
        ]);

        $this->push($token, $deviceUuid, [$operation])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'already_processed');
    }

    /** @return array{User, TeacherProfile, Halaqa} */
    private function teacherWorkspace(string $role = 'teacher'): array
    {
        $center = Center::query()->create([
            'name' => 'مركز الغفران',
            'code' => 'STUDENT-OPS-C-'.Str::random(6),
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'المقر الرئيسي',
            'code' => 'STUDENT-OPS-B-'.Str::random(6),
        ]);
        $user = User::factory()->create([
            'email' => Str::lower(Str::random(8)).'@student-ops.test',
            'password' => Hash::make('TeacherMobile123!'),
        ]);
        $user->assignRole($role);
        $teacher = TeacherProfile::query()->create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STUDENT-OPS-T-'.Str::random(6),
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة المحفظ',
            'code' => 'STUDENT-OPS-H-'.Str::random(6),
            'capacity' => 30,
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth(),
            'assigned_by' => $user->id,
        ]);

        return [$user, $teacher, $halaqa];
    }

    private function student(Halaqa $halaqa, string $number): Student
    {
        $student = Student::query()->create([
            'student_number' => $number,
            'first_name' => 'طالب',
            'father_name' => 'اختبار',
            'grandfather_name' => 'عمليات',
            'family_name' => $number,
            'full_name' => 'طالب اختبار عمليات '.$number,
            'registration_date' => today()->subMonth(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
        $student->enrollments()->create([
            'halaqa_id' => $halaqa->id,
            'starts_at' => today()->subMonth(),
        ]);

        return $student;
    }

    /** @return array{string, string} */
    private function loginDevice(User $user): array
    {
        $deviceUuid = (string) Str::uuid();
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'هاتف عمليات الطلاب',
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'app_version' => '1.2.0',
        ])->assertOk();

        return [$login->json('data.token'), $deviceUuid];
    }

    private function operation(string $uuid, string $type, array $student): array
    {
        return [
            'operation_uuid' => $uuid,
            'client_created_at' => now()->toISOString(),
            'type' => $type,
            'student' => $student,
        ];
    }

    private function push(string $token, string $deviceUuid, array $operations)
    {
        return $this->withToken($token)->postJson('/api/v1/mobile/sync/student-operations', [
            'device_uuid' => $deviceUuid,
            'operations' => $operations,
        ]);
    }
}
