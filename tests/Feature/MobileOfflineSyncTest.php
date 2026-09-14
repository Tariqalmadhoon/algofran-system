<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\MobileDevice;
use App\Models\MobileSyncOperation;
use App\Models\QuranAyah;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\MobileDailySyncService;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QuranReferenceSeeder::class);
    }

    public function test_teacher_device_bootstraps_offline_data_and_syncs_each_operation_exactly_once(): void
    {
        [$teacherUser, $teacher, $halaqa] = $this->teacherWorkspace();
        $firstStudent = $this->student($halaqa, 'MOB-001', 'الطالب الأول');
        $secondStudent = $this->student($halaqa, 'MOB-002', 'الطالب الثاني');
        $deviceUuid = (string) Str::uuid();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $teacherUser->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'هاتف المحفظ',
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])->assertOk()
            ->assertJsonPath('data.device_uuid', $deviceUuid)
            ->assertJsonPath('data.abilities.0', 'mobile:read')
            ->assertJsonPath('data.abilities.1', 'mobile:sync');

        $token = $login->json('data.token');
        $bootstrap = $this->withToken($token)
            ->getJson('/api/v1/mobile/bootstrap?device_uuid='.$deviceUuid)
            ->assertOk()
            ->assertJsonPath('data.schema_version', 3)
            ->assertJsonPath('data.teacher.id', $teacher->id)
            ->assertJsonPath('data.halaqas.0.id', $halaqa->id)
            ->assertJsonCount(2, 'data.halaqas.0.students')
            ->assertJsonCount(114, 'data.quran.surahs')
            ->assertJsonCount(6236, 'data.quran.ayahs');

        $cursor = $bootstrap->json('data.sync_cursor');
        $operationUuid = (string) Str::uuid();
        $payload = $this->operation($operationUuid, $firstStudent, $halaqa);

        $this->withToken($token)->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $deviceUuid,
            'operations' => [$payload],
        ])->assertOk()
            ->assertJsonPath('data.results.0.operation_uuid', $operationUuid)
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.0.record.student.id', $firstStudent->id)
            ->assertJsonPath('data.summary.accepted', 1)
            ->assertJsonPath('data.summary.conflicts', 0);

        $this->assertDatabaseCount('daily_records', 1);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('recitation_items', 1);
        $this->assertDatabaseHas('mobile_sync_operations', [
            'operation_uuid' => $operationUuid,
            'status' => 'accepted',
            'user_id' => $teacherUser->id,
        ]);

        $this->withToken($token)->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $deviceUuid,
            'operations' => [$payload],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'already_processed')
            ->assertJsonPath('data.results.0.original_status', 'accepted');

        $this->assertDatabaseCount('daily_records', 1);
        $this->assertDatabaseCount('mobile_sync_operations', 1);

        $this->withToken($token)->getJson('/api/v1/mobile/sync/changes?device_uuid='.$deviceUuid.'&cursor='.urlencode($cursor))
            ->assertOk()
            ->assertJsonCount(1, 'data.records')
            ->assertJsonPath('data.records.0.student.id', $firstStudent->id)
            ->assertJsonStructure(['data' => ['next_cursor', 'has_more', 'server_time']]);

        $this->assertNotNull(MobileDevice::query()->where('uuid', $deviceUuid)->value('last_synced_at'));
        $this->assertDatabaseMissing('daily_records', ['student_id' => $secondStudent->id]);
    }

    public function test_sync_batch_is_partial_and_conflicts_never_overwrite_the_official_record(): void
    {
        [$teacherUser, $teacher, $halaqa] = $this->teacherWorkspace();
        $allowedStudent = $this->student($halaqa, 'MOB-010', 'الطالب المسموح');
        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $halaqa->center_id,
            'branch_id' => $halaqa->branch_id,
            'name' => 'حلقة أخرى',
            'code' => 'MOB-H-OTHER',
            'capacity' => 20,
            'active' => true,
        ]);
        $outsideStudent = $this->student($otherHalaqa, 'MOB-011', 'طالب خارج الحلقة');
        $outsideAttendance = Attendance::query()->create([
            'student_id' => $outsideStudent->id,
            'halaqa_id' => $otherHalaqa->id,
            'record_date' => today(),
            'status' => 'present',
            'recorded_by' => $teacherUser->id,
        ]);
        DailyRecord::query()->create([
            'student_id' => $outsideStudent->id,
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $otherHalaqa->id,
            'attendance_id' => $outsideAttendance->id,
            'record_date' => today(),
            'general_evaluation' => 'excellent',
            'notes' => 'سجل خارج نطاق المحفظ يجب ألا تُكشف تفاصيله',
            'created_by' => $teacherUser->id,
            'updated_by' => $teacherUser->id,
        ]);
        $device = MobileDevice::query()->create([
            'user_id' => $teacherUser->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'جهاز الاختبار',
            'platform' => 'android',
        ]);
        Sanctum::actingAs($teacherUser, ['mobile:read', 'mobile:sync']);

        $firstOperation = $this->operation((string) Str::uuid(), $allowedStudent, $halaqa);
        $outsideOperation = $this->operation((string) Str::uuid(), $outsideStudent, $otherHalaqa);
        $invalidOperation = [
            'operation_uuid' => (string) Str::uuid(),
            'client_created_at' => now()->toISOString(),
            'daily_record' => [
                'student_id' => $allowedStudent->id,
                'halaqa_id' => $halaqa->id,
                'record_date' => today()->toDateString(),
                'items' => [],
            ],
        ];

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$firstOperation, $outsideOperation, $invalidOperation],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.1.status', 'rejected')
            ->assertJsonPath('data.results.1.error.code', 'validation_failed')
            ->assertJsonMissingPath('data.results.1.server_record')
            ->assertJsonPath('data.results.2.status', 'rejected')
            ->assertJsonPath('data.results.2.error.code', 'validation_failed')
            ->assertJsonPath('data.summary.accepted', 1)
            ->assertJsonPath('data.summary.rejected', 2);

        $conflictingOperation = $this->operation((string) Str::uuid(), $allowedStudent, $halaqa);
        $conflictingOperation['daily_record']['notes'] = 'بيانات مختلفة لا ينبغي أن تستبدل السجل الرسمي';

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$conflictingOperation],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.error.code', 'daily_record_already_exists')
            ->assertJsonPath('data.results.0.server_record.student.id', $allowedStudent->id);

        $reusedKey = $firstOperation;
        $reusedKey['daily_record']['notes'] = 'إعادة استخدام UUID مع حمولة معدلة';
        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$reusedKey],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.error.code', 'idempotency_key_reused');

        $this->assertDatabaseCount('daily_records', 2);
        $this->assertDatabaseHas('daily_records', [
            'student_id' => $allowedStudent->id,
            'notes' => 'سجل محفوظ محليًا',
        ]);
        $this->assertDatabaseHas('daily_records', [
            'student_id' => $outsideStudent->id,
            'notes' => 'سجل خارج نطاق المحفظ يجب ألا تُكشف تفاصيله',
        ]);
    }

    public function test_logging_in_from_a_second_device_keeps_the_first_device_token_active(): void
    {
        [$teacherUser] = $this->teacherWorkspace();
        $firstDeviceUuid = (string) Str::uuid();
        $secondDeviceUuid = (string) Str::uuid();
        $credentials = [
            'email' => $teacherUser->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'تطبيق مركز الغفران',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ];

        $firstToken = $this->postJson('/api/v1/auth/login', $credentials + [
            'device_uuid' => $firstDeviceUuid,
        ])->assertOk()->json('data.token');

        $this->postJson('/api/v1/auth/login', $credentials + [
            'device_uuid' => $secondDeviceUuid,
        ])->assertOk();

        $this->withToken($firstToken)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $teacherUser->id);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertDatabaseHas('mobile_devices', ['uuid' => $firstDeviceUuid]);
        $this->assertDatabaseHas('mobile_devices', ['uuid' => $secondDeviceUuid]);
    }

    public function test_in_progress_receipt_is_retried_safely_after_it_becomes_stale(): void
    {
        [$teacherUser, , $halaqa] = $this->teacherWorkspace();
        $student = $this->student($halaqa, 'MOB-STALE', 'طالب العملية المعلقة');
        $device = MobileDevice::query()->create([
            'user_id' => $teacherUser->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'جهاز العملية المعلقة',
            'platform' => 'android',
        ]);
        $operation = $this->operation((string) Str::uuid(), $student, $halaqa);
        $hashMethod = new \ReflectionMethod(MobileDailySyncService::class, 'payloadHash');
        $payloadHash = $hashMethod->invoke(app(MobileDailySyncService::class), $operation['daily_record']);
        $receipt = MobileSyncOperation::query()->create([
            'mobile_device_id' => $device->id,
            'user_id' => $teacherUser->id,
            'operation_uuid' => $operation['operation_uuid'],
            'operation_type' => 'daily_record.create',
            'payload_hash' => $payloadHash,
            'status' => 'processing',
        ]);
        Sanctum::actingAs($teacherUser, ['mobile:read', 'mobile:sync']);

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$operation],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'failed')
            ->assertJsonPath('data.results.0.error.code', 'operation_in_progress')
            ->assertJsonPath('data.summary.failed', 1);

        $receipt->timestamps = false;
        $receipt->forceFill(['updated_at' => now()->subMinutes(3)])->save();

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$operation],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.summary.accepted', 1);

        $this->assertDatabaseHas('daily_records', ['student_id' => $student->id]);
        $this->assertDatabaseCount('mobile_sync_operations', 1);
    }

    public function test_sync_requires_write_ability_and_center_manager_teacher_is_supported(): void
    {
        [$manager, $teacher, $halaqa] = $this->teacherWorkspace('center-manager');
        $student = $this->student($halaqa, 'MOB-020', 'طالب المدير المحفظ');
        $deviceUuid = (string) Str::uuid();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $manager->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'هاتف مدير المركز',
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])->assertOk()
            ->assertJsonPath('data.abilities.1', 'mobile:sync');

        $this->withToken($login->json('data.token'))
            ->getJson('/api/v1/mobile/bootstrap?device_uuid='.$deviceUuid)
            ->assertOk()
            ->assertJsonPath('data.teacher.id', $teacher->id)
            ->assertJsonPath('data.halaqas.0.students.0.id', $student->id);

        Sanctum::actingAs($manager, ['mobile:read']);
        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $deviceUuid,
            'operations' => [$this->operation((string) Str::uuid(), $student, $halaqa)],
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_excused_mobile_record_rejects_recitation_items_and_accepts_attendance_only(): void
    {
        [$teacherUser, , $halaqa] = $this->teacherWorkspace();
        $student = $this->student($halaqa, 'MOB-EXCUSED', 'طالب الغياب بعذر');
        $device = MobileDevice::query()->create([
            'user_id' => $teacherUser->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'هاتف اختبار الغياب بعذر',
            'platform' => 'android',
        ]);
        Sanctum::actingAs($teacherUser, ['mobile:read', 'mobile:sync']);

        $rejected = $this->operation((string) Str::uuid(), $student, $halaqa);
        $rejected['daily_record']['attendance_status'] = 'excused';

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$rejected],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'rejected')
            ->assertJsonPath('data.results.0.error.code', 'validation_failed')
            ->assertJsonPath('data.results.0.error.fields.items.0', 'لا يمكن مزامنة تسميع لطالب غائب، سواء كان الغياب بعذر أو دون عذر.')
            ->assertJsonPath('data.summary.rejected', 1);

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('daily_records', 0);
        $this->assertDatabaseCount('recitation_items', 0);

        $accepted = $this->operation((string) Str::uuid(), $student, $halaqa);
        $accepted['daily_record']['attendance_status'] = 'excused';
        $accepted['daily_record']['items'] = [];

        $this->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $device->uuid,
            'operations' => [$accepted],
        ])->assertOk()
            ->assertJsonPath('data.results.0.status', 'accepted')
            ->assertJsonPath('data.results.0.record.attendance.status', 'excused')
            ->assertJsonPath('data.results.0.record.general_evaluation', null)
            ->assertJsonCount(0, 'data.results.0.record.recitations')
            ->assertJsonPath('data.summary.accepted', 1);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'excused',
        ]);
        $this->assertDatabaseHas('daily_records', [
            'student_id' => $student->id,
            'general_evaluation' => null,
        ]);
        $this->assertDatabaseCount('recitation_items', 0);
    }

    /** @return array{User,TeacherProfile,Halaqa} */
    private function teacherWorkspace(string $role = 'teacher'): array
    {
        $center = Center::query()->create(['name' => 'مركز الغفران', 'code' => 'MOB-CENTER-'.Str::random(4)]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'المقر الرئيسي',
            'code' => 'MOB-BRANCH-'.Str::random(4),
        ]);
        $user = User::factory()->create([
            'email' => Str::lower(Str::random(8)).'@mobile.test',
            'password' => Hash::make('TeacherMobile123!'),
        ]);
        $user->assignRole($role);
        $teacher = TeacherProfile::query()->create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'MOB-T-'.Str::random(6),
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة المحفظ',
            'code' => 'MOB-H-'.Str::random(6),
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

    private function student(Halaqa $halaqa, string $number, string $name): Student
    {
        $student = Student::query()->create([
            'student_number' => $number,
            'first_name' => $name,
            'father_name' => 'اختبار',
            'grandfather_name' => 'الجوال',
            'family_name' => $number,
            'full_name' => $name,
            'registration_date' => today(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
        $student->enrollments()->create([
            'halaqa_id' => $halaqa->id,
            'starts_at' => today()->subMonth(),
        ]);

        return $student;
    }

    private function operation(string $uuid, Student $student, Halaqa $halaqa): array
    {
        $start = QuranAyah::query()->where('surah_id', 114)->where('ayah_number', 1)->firstOrFail();
        $end = QuranAyah::query()->where('surah_id', 114)->where('ayah_number', 6)->firstOrFail();

        return [
            'operation_uuid' => $uuid,
            'client_created_at' => now()->toISOString(),
            'daily_record' => [
                'student_id' => $student->id,
                'halaqa_id' => $halaqa->id,
                'record_date' => today()->toDateString(),
                'attendance_status' => 'present',
                'attendance_notes' => null,
                'general_evaluation' => 'very_good',
                'notes' => 'سجل محفوظ محليًا',
                'items' => [[
                    'type' => 'new_memorization',
                    'start_ayah_id' => $start->id,
                    'end_ayah_id' => $end->id,
                    'evaluation' => 'very_good',
                    'memorization_errors' => 1,
                    'tajweed_errors' => 0,
                    'hesitation_count' => 0,
                    'teacher_prompt_count' => 0,
                    'notes' => null,
                ]],
            ],
        ];
    }
}
