<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileProfileAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QuranReferenceSeeder::class);
    }

    public function test_bootstrap_v3_and_profile_endpoints_include_offline_profiles_without_private_identity_data(): void
    {
        [$user, $teacher, $halaqa] = $this->teacherWorkspace();
        $student = $this->student($halaqa, 'PROFILE-001', 'طالب الملف المتكامل');
        foreach (range(1, 13) as $index) {
            Achievement::query()->create([
                'student_id' => $student->id,
                'type' => 'manual',
                'title' => 'إنجاز تجريبي '.$index,
                'description' => 'إنجاز ظاهر في الجوال',
                'achieved_at' => today()->subDays(13 - $index),
                'issuer' => 'مركز الغفران',
                'created_by' => $user->id,
            ]);
        }
        foreach (range(1, 12) as $daysAgo) {
            $attendance = Attendance::query()->create([
                'student_id' => $student->id,
                'halaqa_id' => $halaqa->id,
                'record_date' => today()->subDays($daysAgo),
                'status' => 'present',
                'recorded_by' => $user->id,
            ]);
            DailyRecord::query()->create([
                'student_id' => $student->id,
                'teacher_profile_id' => $teacher->id,
                'halaqa_id' => $halaqa->id,
                'attendance_id' => $attendance->id,
                'record_date' => today()->subDays($daysAgo),
                'general_evaluation' => 'good',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $deviceUuid = (string) Str::uuid();
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'هاتف اختبار الملف',
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'app_version' => '1.2.0',
        ])->assertOk()
            ->assertJsonPath('data.abilities.2', 'mobile:export');
        $token = $login->json('data.token');

        $this->withToken($token)->postJson('/api/v1/mobile/sync/daily-records', [
            'device_uuid' => $deviceUuid,
            'operations' => [$this->operation((string) Str::uuid(), $student, $halaqa)],
        ])->assertOk()->assertJsonPath('data.results.0.status', 'accepted');

        $bootstrap = $this->withToken($token)
            ->getJson('/api/v1/mobile/bootstrap?device_uuid='.$deviceUuid)
            ->assertOk()
            ->assertJsonPath('data.schema_version', 3)
            ->assertJsonPath('data.teacher.id', $teacher->id)
            ->assertJsonPath('data.teacher.user_id', $user->id)
            ->assertJsonPath('data.teacher.center.name', $teacher->center->name)
            ->assertJsonPath('data.teacher.can_export_reports', true)
            ->assertJsonPath('data.teacher.can_create_students', true)
            ->assertJsonPath('data.teacher.can_update_students', true)
            ->assertJsonPath('data.teacher.can_archive_students', true)
            ->assertJsonPath('data.teacher.summary.halaqas_count', 1)
            ->assertJsonPath('data.teacher.summary.students_count', 1)
            ->assertJsonPath('data.teacher.summary.recorded_today', 1)
            ->assertJsonPath('data.halaqas.0.students.0.profile.status', 'active')
            ->assertJsonPath('data.halaqas.0.students.0.profile.recent_records.0.general_evaluation', 'very_good')
            ->assertJsonPath('data.halaqas.0.students.0.profile.recent_records.0.attendance.status', 'present')
            ->assertJsonPath('data.halaqas.0.students.0.profile.recent_records.0.recitations.0.start.surah_name', 'الناس')
            ->assertJsonMissingPath('data.halaqas.0.students.0.profile.identity_number')
            ->assertJsonMissingPath('data.halaqas.0.students.0.profile.guardians')
            ->assertJsonCount(12, 'data.halaqas.0.students.0.profile.achievements')
            ->assertJsonCount(12, 'data.halaqas.0.students.0.profile.recent_records')
            ->assertJsonFragment(['title' => 'إنجاز تجريبي 13'])
            ->assertJsonMissing(['title' => 'إنجاز تجريبي 1']);

        $this->assertNotNull($bootstrap->json('data.halaqas.0.students.0.profile.progress'));

        $this->withToken($token)->getJson('/api/v1/mobile/profile')
            ->assertOk()
            ->assertJsonPath('data.employee_number', $teacher->employee_number)
            ->assertJsonPath('data.email', $user->email);
        $this->withToken($token)->getJson('/api/v1/mobile/students/'.$student->id.'/profile')
            ->assertOk()
            ->assertJsonCount(12, 'data.profile.achievements')
            ->assertJsonCount(12, 'data.profile.recent_records')
            ->assertJsonFragment(['title' => 'إنجاز تجريبي 13']);
    }

    public function test_manager_teacher_mobile_profiles_are_limited_to_current_assignments(): void
    {
        [$manager, , $assignedHalaqa] = $this->teacherWorkspace('center-manager');
        $assignedStudent = $this->student($assignedHalaqa, 'MANAGER-OWN', 'طالب حلقة المدير');
        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $assignedHalaqa->center_id,
            'branch_id' => $assignedHalaqa->branch_id,
            'name' => 'حلقة غير مسندة',
            'code' => 'UNASSIGNED-'.Str::random(6),
            'capacity' => 20,
            'active' => true,
        ]);
        $otherStudent = $this->student($otherHalaqa, 'MANAGER-OTHER', 'طالب حلقة أخرى');

        Sanctum::actingAs($manager, ['mobile:read']);

        $this->getJson('/api/v1/mobile/students/'.$assignedStudent->id.'/profile')->assertOk();
        $this->getJson('/api/v1/mobile/students/'.$otherStudent->id.'/profile')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_teacher_can_create_track_and_download_only_own_assigned_exports(): void
    {
        Storage::fake('private');
        [$user, $teacher, $halaqa] = $this->teacherWorkspace();
        $this->student($halaqa, 'EXPORT-001', 'طالب كشف المحفظ');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'TeacherMobile123!',
            'device_name' => 'هاتف التصدير',
        ])->assertOk();
        $token = $login->json('data.token');

        $created = $this->withToken($token)->postJson('/api/v1/mobile/report-exports', [
            'report_type' => 'student_comprehensive',
            'halaqa_id' => $halaqa->id,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.report_type', 'student_comprehensive');

        $uuid = $created->json('data.uuid');
        $this->withToken($token)->getJson('/api/v1/mobile/report-exports')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $uuid);
        $this->withToken($token)->getJson('/api/v1/mobile/report-exports/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $uuid);
        $this->withToken($token)->get('/api/v1/mobile/report-exports/'.$uuid.'/download')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->withToken($token)->postJson('/api/v1/mobile/report-exports', [
            'report_type' => 'memorization_records',
            'teacher_profile_id' => 999999,
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.filters.teacher_profile_id', $teacher->id)
            ->assertJsonPath('data.filters.date_from', today()->toDateString())
            ->assertJsonPath('data.filters.date_to', today()->toDateString());

        $unassignedHalaqa = Halaqa::query()->create([
            'center_id' => $halaqa->center_id,
            'branch_id' => $halaqa->branch_id,
            'name' => 'حلقة ممنوعة',
            'code' => 'DENIED-'.Str::random(6),
            'capacity' => 20,
            'active' => true,
        ]);
        $this->withToken($token)->postJson('/api/v1/mobile/report-exports', [
            'report_type' => 'student_comprehensive',
            'halaqa_id' => $unassignedHalaqa->id,
        ])->assertForbidden();

        Sanctum::actingAs($user, ['mobile:read']);
        $this->postJson('/api/v1/mobile/report-exports', [
            'report_type' => 'student_comprehensive',
        ])->assertForbidden();
    }

    public function test_mobile_export_is_owner_only(): void
    {
        Storage::fake('private');
        [$owner, , $halaqa] = $this->teacherWorkspace();
        $this->student($halaqa, 'OWNER-001', 'طالب صاحب الكشف');
        Sanctum::actingAs($owner, ['mobile:read', 'mobile:export']);
        $created = $this->postJson('/api/v1/mobile/report-exports', [
            'report_type' => 'student_comprehensive',
            'halaqa_id' => $halaqa->id,
        ])->assertCreated();

        [$other] = $this->teacherWorkspace();
        Sanctum::actingAs($other, ['mobile:read', 'mobile:export']);
        $this->getJson('/api/v1/mobile/report-exports/'.$created->json('data.uuid'))->assertForbidden();
        $this->get('/api/v1/mobile/report-exports/'.$created->json('data.uuid').'/download')->assertForbidden();
    }

    /** @return array{User, TeacherProfile, Halaqa} */
    private function teacherWorkspace(string $role = 'teacher'): array
    {
        $center = Center::query()->create([
            'name' => 'مركز الغفران',
            'code' => 'PROFILE-CENTER-'.Str::random(6),
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'المقر الرئيسي',
            'code' => 'PROFILE-BRANCH-'.Str::random(6),
        ]);
        $user = User::factory()->create([
            'email' => Str::lower(Str::random(8)).'@profile.test',
            'password' => Hash::make('TeacherMobile123!'),
            'phone' => '0599000000',
        ]);
        $user->assignRole($role);
        $teacher = TeacherProfile::query()->create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'PROFILE-T-'.Str::random(6),
            'specialization' => 'تحفيظ القرآن الكريم',
            'hired_at' => today()->subYear(),
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة المحفظ',
            'code' => 'PROFILE-H-'.Str::random(6),
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
            'identity_number' => 'ID-'.$number,
            'birth_date' => today()->subYears(10),
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
                'notes' => 'سجل ملف الطالب',
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
