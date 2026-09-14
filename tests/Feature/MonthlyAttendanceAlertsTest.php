<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\StudentProgressSnapshot;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\StudentAlertEngine;
use Carbon\Carbon;
use Database\Seeders\AcademicSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MonthlyAttendanceAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-09 10:00:00');
        $this->seed([RolesAndPermissionsSeeder::class, AcademicSettingsSeeder::class]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_unexcused_absence_notifies_assigned_teacher_and_center_manager_once_per_month(): void
    {
        Notification::fake();
        [$student, $halaqa, $teacherUser, $manager, $unrelatedManager, $inactiveTeacher] = $this->workspace();

        $this->attendance($student, $halaqa, '2026-09-05', 'absent', $teacherUser);
        $this->evaluate($student, '2026-09-05');

        $alert = StudentAlert::query()->where('type', 'unexcused_absence')->firstOrFail();
        $this->assertSame(AlertSeverity::Warning, $alert->severity);
        $this->assertSame(1, $alert->evidence['absence_count']);
        $this->assertSame('2026-09-01', $alert->evidence['period_from']);
        $this->assertSame('2026-09-05', $alert->evidence['period_to']);
        Notification::assertSentToTimes($teacherUser, SystemNotification::class, 1);
        Notification::assertSentToTimes($manager, SystemNotification::class, 1);
        Notification::assertNotSentTo($unrelatedManager, SystemNotification::class);
        Notification::assertNotSentTo($inactiveTeacher, SystemNotification::class);

        $this->attendance($student, $halaqa, '2026-09-06', 'absent', $teacherUser);
        $this->evaluate($student, '2026-09-06');

        $this->assertSame(1, StudentAlert::query()->where('type', 'unexcused_absence')->count());
        $this->assertSame(2, $alert->refresh()->evidence['absence_count']);
        Notification::assertSentToTimes($teacherUser, SystemNotification::class, 1);
        Notification::assertSentToTimes($manager, SystemNotification::class, 1);

        $this->attendance($student, $halaqa, '2026-10-02', 'absent', $teacherUser);
        $this->evaluate($student, '2026-10-02');

        $this->assertSame(2, StudentAlert::query()->where('type', 'unexcused_absence')->count());
        $this->assertSame(2, StudentAlert::query()->where('type', 'unexcused_absence')->distinct()->count('fingerprint'));
        Notification::assertSentToTimes($teacherUser, SystemNotification::class, 2);
        Notification::assertSentToTimes($manager, SystemNotification::class, 2);
    }

    public function test_excused_absence_alert_starts_on_fourth_day_and_does_not_repeat_while_open(): void
    {
        Notification::fake();
        [$student, $halaqa, $teacherUser, $manager] = $this->workspace();

        foreach (range(1, 3) as $day) {
            $date = "2026-09-0{$day}";
            $this->attendance($student, $halaqa, $date, 'excused', $teacherUser);
            $this->evaluate($student, $date);
        }

        $this->assertDatabaseMissing('student_alerts', [
            'student_id' => $student->id,
            'type' => 'excessive_excused_absence',
        ]);
        Notification::assertNothingSent();

        $this->attendance($student, $halaqa, '2026-09-04', 'excused', $teacherUser);
        $this->evaluate($student, '2026-09-04');

        $alert = StudentAlert::query()->where('type', 'excessive_excused_absence')->firstOrFail();
        $this->assertSame(4, $alert->evidence['absence_count']);
        $this->assertSame(3, $alert->evidence['allowance']);
        Notification::assertSentToTimes($teacherUser, SystemNotification::class, 1);
        Notification::assertSentToTimes($manager, SystemNotification::class, 1);

        $this->attendance($student, $halaqa, '2026-09-05', 'excused', $teacherUser);
        $this->evaluate($student, '2026-09-05');

        $this->assertSame(1, StudentAlert::query()->where('type', 'excessive_excused_absence')->count());
        $this->assertSame(5, $alert->refresh()->evidence['absence_count']);
        Notification::assertSentToTimes($teacherUser, SystemNotification::class, 1);
        Notification::assertSentToTimes($manager, SystemNotification::class, 1);
    }

    /** @return array{Student, Halaqa, User, User, User, User} */
    private function workspace(): array
    {
        $center = Center::query()->create(['name' => 'مركز الغفران', 'code' => 'MONTHLY-ALERT-CENTER']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'الفرع الرئيس', 'code' => 'MONTHLY-ALERT-BRANCH']);
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'MONTHLY-TEACHER',
            'active' => true,
        ]);
        $manager = User::factory()->create();
        $manager->assignRole(['center-manager', 'teacher']);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'MONTHLY-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $managerTeacher = TeacherProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'MONTHLY-MANAGER-TEACHER',
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة الاختبار الشهري',
            'code' => 'MONTHLY-ALERT-HALAQA',
            'capacity' => 20,
            'active' => true,
        ]);
        foreach ([[$teacher->id, 'primary'], [$managerTeacher->id, 'assistant']] as [$teacherId, $role]) {
            $halaqa->teacherAssignments()->create([
                'teacher_profile_id' => $teacherId,
                'role' => $role,
                'starts_at' => '2026-01-01',
                'assigned_by' => $manager->id,
            ]);
        }
        $student = Student::query()->create([
            'student_number' => 'MONTHLY-STUDENT',
            'first_name' => 'طالب',
            'father_name' => 'اختبار',
            'grandfather_name' => 'تنبيهات',
            'family_name' => 'شهرية',
            'full_name' => 'طالب اختبار تنبيهات شهرية',
            'registration_date' => '2026-09-01',
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
        $student->enrollments()->create([
            'halaqa_id' => $halaqa->id,
            'starts_at' => '2026-01-01',
            'enrolled_by' => $manager->id,
        ]);

        $otherCenter = Center::query()->create(['name' => 'مركز آخر', 'code' => 'OTHER-ALERT-CENTER']);
        $otherBranch = Branch::query()->create(['center_id' => $otherCenter->id, 'name' => 'فرع آخر', 'code' => 'OTHER-ALERT-BRANCH']);
        $unrelatedManager = User::factory()->create();
        $unrelatedManager->assignRole('center-manager');
        StaffProfile::query()->create([
            'user_id' => $unrelatedManager->id,
            'center_id' => $otherCenter->id,
            'branch_id' => $otherBranch->id,
            'employee_number' => 'OTHER-MANAGER',
            'job_title' => 'مدير مركز آخر',
            'active' => true,
        ]);
        $inactiveTeacher = User::factory()->create();
        $inactiveTeacher->assignRole('teacher');
        $inactiveProfile = TeacherProfile::query()->create([
            'user_id' => $inactiveTeacher->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'INACTIVE-TEACHER',
            'active' => false,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $inactiveProfile->id,
            'role' => 'assistant',
            'starts_at' => '2026-01-01',
            'assigned_by' => $manager->id,
        ]);

        return [$student, $halaqa, $teacherUser, $manager, $unrelatedManager, $inactiveTeacher];
    }

    private function attendance(Student $student, Halaqa $halaqa, string $date, string $status, User $recorder): void
    {
        Attendance::query()->create([
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'record_date' => $date,
            'status' => $status,
            'recorded_by' => $recorder->id,
        ]);
    }

    private function evaluate(Student $student, string $date): void
    {
        app(StudentAlertEngine::class)->evaluate(
            $student->refresh(),
            new StudentProgressSnapshot([
                'as_of_date' => $date,
                'memorization_sessions' => 0,
                'performance_trend' => 0,
                'metrics' => ['evaluation_count' => 0],
            ]),
            $date,
        );
    }
}
