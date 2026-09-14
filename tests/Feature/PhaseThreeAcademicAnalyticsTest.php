<?php

namespace Tests\Feature;

use App\Actions\Students\CreateStudentAction;
use App\Actions\Students\RecordInitialBaselineAction;
use App\Enums\AlertStatus;
use App\Livewire\DashboardOverview;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AcademicRecordsService;
use App\Services\DashboardMetricsService;
use App\Services\PrivateFileService;
use App\Services\StudentAlertEngine;
use App\Services\StudentPeriodRankingService;
use App\Services\StudentProgressService;
use Database\Seeders\AcademicSettingsSeeder;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreeAcademicAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QuranReferenceSeeder::class);
        $this->seed(AcademicSettingsSeeder::class);
    }

    public function test_progress_engine_merges_memorized_ranges_and_explains_score(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $this->actingAs($manager);
        [$center, $branch, $halaqa, $teacher] = $this->organization($manager);
        $student = $this->student($manager, $halaqa, 'P-001');

        app(RecordInitialBaselineAction::class)->execute($student, 1, 7, today()->subDays(20)->toDateString(), $manager, 'سورة الفاتحة');
        $this->record($student, $halaqa, $teacher, $manager, today()->subDays(2)->toDateString(), 'present', [
            ['type' => 'new_memorization', 'start' => 5, 'end' => 12, 'evaluation' => 'excellent'],
        ]);
        $this->record($student, $halaqa, $teacher, $manager, today()->subDay()->toDateString(), 'absent', []);
        $this->record($student, $halaqa, $teacher, $manager, today()->toDateString(), 'late', [
            ['type' => 'recent_revision', 'start' => 1, 'end' => 7, 'evaluation' => 'good'],
        ]);
        $this->record($student, $halaqa, $teacher, $manager, today()->subDays(3)->toDateString(), 'excused', []);

        $snapshot = app(StudentProgressService::class)->snapshot($student);

        $this->assertSame(12, $snapshot->memorized_ayahs);
        $this->assertSame(12, $snapshot->last_memorized_ayah_id);
        $this->assertSame(1, $snapshot->completed_surahs);
        $this->assertSame(1, $snapshot->memorization_sessions);
        $this->assertSame(1, $snapshot->revision_sessions);
        $this->assertSame(1, $snapshot->metrics['monthly_revision_sessions']);
        $this->assertEqualsWithDelta(80, $snapshot->evaluation_average, 0.01);
        $this->assertEqualsWithDelta(50, $snapshot->attendance_rate, 0.01);
        $this->assertGreaterThanOrEqual(0, $snapshot->score);
        $this->assertLessThanOrEqual(100, $snapshot->score);
        $this->assertArrayHasKey('weights', $snapshot->score_breakdown);
        $this->assertNotEmpty($snapshot->score_breakdown['reasons']);
        $this->assertDatabaseHas('achievements', ['student_id' => $student->id, 'title' => 'إكمال أول سورة']);
    }

    public function test_alert_engine_detects_risk_and_supports_resolution_workflow(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $this->actingAs($manager);
        [, , $halaqa, $teacher] = $this->organization($manager);
        $student = $this->student($manager, $halaqa, 'A-001');

        foreach ([4, 3, 2] as $daysAgo) {
            $this->record($student, $halaqa, $teacher, $manager, today()->subDays($daysAgo)->toDateString(), 'absent', [
                ['type' => 'new_memorization', 'start' => 1, 'end' => 2, 'evaluation' => 'poor'],
            ]);
        }
        $snapshot = app(StudentProgressService::class)->snapshot($student);
        app(StudentAlertEngine::class)->evaluate($student->refresh(), $snapshot);

        $this->assertDatabaseHas('student_alerts', ['student_id' => $student->id, 'type' => 'consecutive_poor_evaluations', 'severity' => 'critical', 'status' => 'open']);
        $this->assertDatabaseHas('student_alerts', ['student_id' => $student->id, 'type' => 'unexcused_absence', 'status' => 'open']);
        $this->assertDatabaseHas('student_alerts', ['student_id' => $student->id, 'type' => 'revision_delay', 'status' => 'open']);

        $alert = StudentAlert::query()->where('type', 'unexcused_absence')->firstOrFail();
        app(StudentAlertEngine::class)->acknowledge($alert, $manager);
        $this->assertSame(AlertStatus::Acknowledged, $alert->refresh()->status);
        app(StudentAlertEngine::class)->resolve($alert, $manager, 'تم التواصل مع ولي الأمر ووضع خطة حضور.');
        $this->assertSame(AlertStatus::Resolved, $alert->refresh()->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertSame($manager->id, $alert->resolved_by);
    }

    public function test_courses_certificates_and_achievements_are_linked_to_student_timeline(): void
    {
        Storage::fake('private');
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $this->actingAs($manager);
        [$center, $branch, $halaqa, $teacher] = $this->organization($manager);
        $student = $this->student($manager, $halaqa, 'C-001');
        $academic = app(AcademicRecordsService::class);
        $course = $academic->createCourse([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'instructor_id' => $teacher->id,
            'name' => 'دورة أحكام التجويد',
            'description' => 'دورة تطبيقية',
            'starts_at' => today()->subMonth()->toDateString(),
            'ends_at' => today()->toDateString(),
            'hours' => 20,
            'status' => 'completed',
        ], $manager);
        $academic->enrollStudent($course, $student, [
            'enrolled_at' => today()->subMonth()->toDateString(),
            'status' => 'completed',
            'result' => 94,
            'grade' => 'ممتاز',
            'completed_at' => today()->toDateString(),
            'notes' => null,
        ], $manager);
        $certificate = $academic->issueCertificate($student, [
            'course_id' => $course->id,
            'name' => 'شهادة إتمام التجويد',
            'issuer' => 'المركز الرئيس',
            'certificate_number' => 'CERT-001',
            'issued_at' => today()->toDateString(),
            'expires_at' => null,
            'grade' => 'ممتاز',
            'notes' => null,
        ], $manager);
        $file = app(PrivateFileService::class)->store(UploadedFile::fake()->create('certificate.pdf', 120, 'application/pdf'), $certificate, $manager, "certificates/{$certificate->id}", 'certificate');
        $certificate->update(['private_file_id' => $file->id]);
        $academic->recordAchievement($student, [
            'type' => 'competition',
            'title' => 'المركز الأول في المسابقة',
            'description' => null,
            'achieved_at' => today()->toDateString(),
            'issuer' => 'المركز الرئيس',
            'metadata' => null,
        ], $manager);

        $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'completed', 'result' => 94]);
        $this->assertDatabaseHas('certificates', ['student_id' => $student->id, 'certificate_number' => 'CERT-001', 'private_file_id' => $file->id]);
        $this->assertDatabaseHas('achievements', ['student_id' => $student->id, 'type' => 'competition']);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $student->id, 'event_type' => 'course.completed']);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $student->id, 'event_type' => 'certificate.issued']);
        $this->get(route('private-files.show', $file))->assertOk();
    }

    public function test_dashboard_uses_scoped_real_data_for_teacher(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [$center, $branch, $ownHalaqa, $teacher] = $this->organization($manager);
        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة أخرى',
            'code' => 'H2',
            'capacity' => 20,
            'active' => true,
        ]);
        $ownStudent = $this->student($manager, $ownHalaqa, 'D-OWN');
        $this->student($manager, $otherHalaqa, 'D-OTHER');
        app(StudentProgressService::class)->snapshot($ownStudent);

        $teacherUser = $teacher->user;
        $metrics = app(DashboardMetricsService::class)->for($teacherUser, [
            'date_from' => today()->subMonth()->toDateString(),
            'date_to' => today()->toDateString(),
        ]);
        $this->assertSame(1, $metrics['stats']['active_students']);
        $this->assertSame(1, $metrics['stats']['halaqas']);
        $this->assertCount(1, $metrics['students']);

        Livewire::actingAs($teacherUser)
            ->test(DashboardOverview::class)
            ->assertSee('لوحة المحفظ')
            ->assertSee($ownStudent->full_name)
            ->assertDontSee('D-OTHER');
    }

    public function test_period_ranking_merges_overlapping_memorization_and_counts_excused_as_absence(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [, , $halaqa, $teacher] = $this->organization($manager);
        $memorizer = $this->student($manager, $halaqa, 'R-MEM');
        $committed = $this->student($manager, $halaqa, 'R-COM');
        $from = today()->subMonthNoOverflow()->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $this->record($memorizer, $halaqa, $teacher, $manager, $from->copy()->addDay()->toDateString(), 'present', [
            ['type' => 'new_memorization', 'start' => 1, 'end' => 10, 'evaluation' => 'excellent'],
        ]);
        $this->record($memorizer, $halaqa, $teacher, $manager, $from->copy()->addDays(2)->toDateString(), 'present', [
            ['type' => 'new_memorization', 'start' => 5, 'end' => 15, 'evaluation' => 'very_good'],
        ]);
        $this->record($memorizer, $halaqa, $teacher, $manager, $from->copy()->addDays(3)->toDateString(), 'excused', []);

        $this->record($committed, $halaqa, $teacher, $manager, $from->copy()->addDay()->toDateString(), 'present', [
            ['type' => 'new_memorization', 'start' => 20, 'end' => 24, 'evaluation' => 'good'],
        ]);
        $this->record($committed, $halaqa, $teacher, $manager, $from->copy()->addDays(2)->toDateString(), 'present', []);
        $this->record($committed, $halaqa, $teacher, $manager, $from->copy()->addDays(3)->toDateString(), 'late', []);

        $analytics = app(StudentPeriodRankingService::class)->calculate(
            collect([$memorizer, $committed]),
            $from,
            $to,
        );

        $this->assertSame($memorizer->id, $analytics['top_memorizer']['student']->id);
        $this->assertSame(15, $analytics['top_memorizer']['memorized_ayahs']);
        $this->assertSame($committed->id, $analytics['most_committed']['student']->id);
        $this->assertEqualsWithDelta(100, $analytics['most_committed']['commitment_rate'], 0.01);

        $memorizerRow = $analytics['rankings']->first(fn (array $row) => $row['student']->is($memorizer));
        $this->assertNotNull($memorizerRow);
        $this->assertSame(1, $memorizerRow['excused_days']);
        $this->assertEqualsWithDelta(66.7, $memorizerRow['commitment_rate'], 0.01);
        $this->assertSame(1, $memorizerRow['memorization_rank']);

        $committedRow = $analytics['rankings']->first(fn (array $row) => $row['student']->is($committed));
        $this->assertNotNull($committedRow);
        $this->assertSame(1, $committedRow['commitment_rank']);
    }

    public function test_center_manager_dashboard_counts_and_rankings_never_leak_another_center(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [$center, $branch, $halaqa] = $this->organization($manager);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'CENTER-MANAGER-SCOPE',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $ownStudent = $this->student($manager, $halaqa, 'CENTER-OWN');

        $otherCenter = Center::query()->create(['name' => 'مركز آخر', 'code' => 'OTHER-CENTER-DASH']);
        $otherBranch = Branch::query()->create(['center_id' => $otherCenter->id, 'name' => 'فرع آخر', 'code' => 'OTHER-BRANCH-DASH']);
        $otherTeacherUser = User::factory()->create();
        $otherTeacherUser->assignRole('teacher');
        $otherTeacher = TeacherProfile::query()->create([
            'user_id' => $otherTeacherUser->id,
            'center_id' => $otherCenter->id,
            'branch_id' => $otherBranch->id,
            'employee_number' => 'OTHER-TEACHER-DASH',
            'active' => true,
        ]);
        $otherHalaqa = Halaqa::query()->create([
            'center_id' => $otherCenter->id,
            'branch_id' => $otherBranch->id,
            'primary_teacher_id' => $otherTeacher->id,
            'name' => 'حلقة مركز آخر',
            'code' => 'OTHER-HALAQA-DASH',
            'capacity' => 20,
            'active' => true,
        ]);
        $this->student($manager, $otherHalaqa, 'CENTER-OTHER');

        $metrics = app(DashboardMetricsService::class)->for($manager->refresh(), [
            'date_from' => today()->startOfMonth()->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $this->assertSame(1, $metrics['stats']['active_students']);
        $this->assertSame(1, $metrics['stats']['teachers']);
        $this->assertSame(1, $metrics['stats']['halaqas']);
        $this->assertCount(1, $metrics['student_rankings']['rankings']);
        $this->assertSame($ownStudent->id, $metrics['student_rankings']['rankings']->first()['student']->id);
    }

    /** @return array{Center, Branch, Halaqa, TeacherProfile} */
    private function organization(User $manager): array
    {
        $center = Center::query()->create(['name' => 'المركز الرئيس', 'code' => 'MAIN']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'الفرع الأول', 'code' => 'B1']);
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-'.fake()->unique()->numerify('####'),
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة الإتقان',
            'code' => 'H1',
            'capacity' => 20,
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subYear()->toDateString(),
            'assigned_by' => $manager->id,
        ]);

        return [$center, $branch, $halaqa, $teacher];
    }

    private function student(User $actor, Halaqa $halaqa, string $number): Student
    {
        return app(CreateStudentAction::class)->execute([
            'student_number' => $number,
            'first_name' => 'طالب',
            'father_name' => 'اختبار',
            'grandfather_name' => 'أكاديمي',
            'family_name' => $number,
            'identity_number' => null,
            'birth_date' => '2013-01-01',
            'contact_phone' => null,
            'registration_date' => today()->subMonths(2)->toDateString(),
            'status' => 'active',
            'halaqa_id' => $halaqa->id,
            'notes' => null,
        ], $actor);
    }

    private function record(Student $student, Halaqa $halaqa, TeacherProfile $teacher, User $actor, string $date, string $attendanceStatus, array $items): DailyRecord
    {
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'record_date' => $date,
            'status' => $attendanceStatus,
            'recorded_by' => $actor->id,
        ]);
        $record = DailyRecord::query()->create([
            'student_id' => $student->id,
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $halaqa->id,
            'attendance_id' => $attendance->id,
            'record_date' => $date,
            'general_evaluation' => $items[0]['evaluation'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        foreach ($items as $item) {
            $record->recitationItems()->create([
                'type' => $item['type'],
                'start_ayah_id' => $item['start'],
                'end_ayah_id' => $item['end'],
                'evaluation' => $item['evaluation'],
                'memorization_errors' => 0,
                'tajweed_errors' => 0,
                'hesitation_count' => 0,
                'teacher_prompt_count' => 0,
            ]);
        }

        return $record;
    }
}
