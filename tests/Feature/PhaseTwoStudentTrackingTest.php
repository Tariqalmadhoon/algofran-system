<?php

namespace Tests\Feature;

use App\Actions\Guardians\CreateGuardianAction;
use App\Actions\Students\CreateStudentAction;
use App\Actions\Students\EnrollStudentInHalaqaAction;
use App\Actions\Students\RecordInitialBaselineAction;
use App\Livewire\StudentProfile;
use App\Livewire\TeacherDailyRecorder;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Guardian;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\PrivateFileService;
use App\Services\QuranRangeService;
use App\Services\StudentVisibilityService;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoStudentTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_quran_reference_seed_is_complete_and_ranges_are_guarded(): void
    {
        $this->seed(QuranReferenceSeeder::class);
        $this->seed(QuranReferenceSeeder::class);

        $this->assertSame(114, QuranSurah::query()->count());
        $this->assertSame(6236, QuranAyah::query()->count());
        $this->assertSame(604, QuranAyah::query()->max('page'));
        $this->assertSame(30, QuranAyah::query()->max('juz'));
        $this->assertSame(60, QuranAyah::query()->max('hizb'));
        $this->assertDatabaseHas('quran_surahs', ['id' => 2, 'name_arabic' => 'البقرة', 'verses_count' => 286]);
        $this->assertDatabaseHas('quran_ayahs', ['surah_id' => 2, 'ayah_number' => 286, 'global_order' => 293]);

        try {
            app(QuranRangeService::class)->validate(293, 1);
            $this->fail('A reversed Quran range was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quran_range', $exception->errors());
        }
    }

    public function test_student_lifecycle_preserves_enrollment_guardian_baseline_and_private_document_history(): void
    {
        $this->seed(QuranReferenceSeeder::class);
        Storage::fake('private');

        $registrar = User::factory()->create();
        $registrar->assignRole('registrar');
        $this->actingAs($registrar);
        [, , $firstHalaqa, $secondHalaqa] = $this->organization();

        $student = $this->createStudent($registrar, $firstHalaqa, 'STU-001');
        $this->assertSame($firstHalaqa->id, $student->current_halaqa_id);
        $this->assertDatabaseHas('halaqa_enrollments', ['student_id' => $student->id, 'halaqa_id' => $firstHalaqa->id, 'ends_at' => null]);

        app(EnrollStudentInHalaqaAction::class)->execute($student, $secondHalaqa, '2026-08-25', $registrar, 'تغيير المستوى');
        $firstEnrollment = $student->enrollments()->where('halaqa_id', $firstHalaqa->id)->firstOrFail();
        $this->assertSame('2026-08-24', $firstEnrollment->ends_at->toDateString());
        $this->assertSame($secondHalaqa->id, $student->fresh()->current_halaqa_id);

        $guardianData = [
            'full_name' => 'محمد أحمد', 'identity_number' => '900001', 'phone' => '0599000001',
            'alternative_phone' => null, 'email' => 'guardian@example.com', 'relationship' => 'father',
            'is_primary' => true, 'can_receive_notifications' => true, 'notes' => null,
        ];
        $guardian = app(CreateGuardianAction::class)->execute($student, $guardianData, $registrar);
        $otherStudent = $this->createStudent($registrar, null, 'STU-002');
        $reusedGuardian = app(CreateGuardianAction::class)->execute($otherStudent, $guardianData, $registrar);
        $this->assertSame($guardian->id, $reusedGuardian->id);
        $this->assertSame(1, Guardian::query()->count());
        $this->assertDatabaseHas('guardian_student', ['student_id' => $student->id, 'guardian_id' => $guardian->id, 'is_primary' => true]);

        app(RecordInitialBaselineAction::class)->execute($student, 1, 7, '2026-08-19', $registrar, 'يحفظ الفاتحة');
        $this->assertDatabaseHas('student_memorization_baselines', ['student_id' => $student->id, 'start_ayah_id' => 1, 'end_ayah_id' => 7]);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $student->id, 'event_type' => 'baseline.recorded']);

        Livewire::actingAs($registrar)
            ->test(StudentProfile::class, ['student' => $student->fresh()])
            ->set('baselineStartSurahId', '3')
            ->assertSet('baselineEndSurahId', '3')
            ->set('baselineStartAyahNumber', '10')
            ->assertSet('baselineEndAyahNumber', '10')
            ->set('baselineEndSurahId', '2')
            ->assertSet('baselineEndSurahId', '3')
            ->assertHasErrors(['baselineEndSurahId']);

        $file = app(PrivateFileService::class)->store(
            UploadedFile::fake()->create('guardian-id.pdf', 100, 'application/pdf'),
            $guardian,
            $registrar,
            "guardians/{$guardian->id}",
            'guardian-identity',
        );
        $guardian->update(['identity_private_file_id' => $file->id]);
        $this->get(route('private-files.show', $file))->assertOk();

        Livewire::actingAs($registrar)
            ->test(StudentProfile::class, ['student' => $student->fresh()])
            ->set('profileStatus', 'suspended')
            ->call('saveProfile')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $student->id, 'event_type' => 'student.status-changed']);

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        $this->actingAs($teacherUser)->get(route('private-files.show', $file))->assertForbidden();
    }

    public function test_teacher_records_daily_attendance_and_quran_items_without_page_reload(): void
    {
        $this->seed(QuranReferenceSeeder::class);
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $halaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-001',
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth()->toDateString(),
        ]);
        $student = $this->createStudent($teacherUser, $halaqa, 'STU-DAILY');

        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->assertSet('halaqaId', (string) $halaqa->id)
            ->assertSee($student->full_name)
            ->set('studentId', (string) $student->id)
            ->set('generalEvaluation', 'very_good')
            ->set('items.0.enabled', true)
            ->set('items.0.start_surah_id', '1')
            ->set('items.0.start_ayah_number', '1')
            ->set('items.0.end_surah_id', '1')
            ->set('items.0.end_ayah_number', '7')
            ->set('items.0.evaluation', 'excellent')
            ->set('items.0.memorization_errors', 1)
            ->set('items.1.enabled', true)
            ->set('items.1.start_surah_id', '2')
            ->set('items.1.start_ayah_number', '1')
            ->set('items.1.end_surah_id', '2')
            ->set('items.1.end_ayah_number', '5')
            ->set('items.1.evaluation', 'good')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('studentId', '')
            ->assertSee('تم حفظ الحضور والتسميع اليومي للطالب.');

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'present']);
        $this->assertDatabaseHas('daily_records', ['student_id' => $student->id, 'general_evaluation' => 'very_good']);
        $this->assertDatabaseCount('recitation_items', 2);
        $this->assertDatabaseHas('recitation_items', ['type' => 'new_memorization', 'start_ayah_id' => 1, 'end_ayah_id' => 7, 'memorization_errors' => 1]);
        $this->assertDatabaseHas('recitation_items', ['type' => 'recent_revision', 'start_ayah_id' => 8, 'end_ayah_id' => 12]);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $student->id, 'event_type' => 'daily-record.created']);
    }

    public function test_teacher_daily_recorder_guides_quran_range_and_exposes_duplicate_errors(): void
    {
        $this->seed(QuranReferenceSeeder::class);
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $halaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-SMART-RANGE',
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth()->toDateString(),
        ]);
        $student = $this->createStudent($teacherUser, $halaqa, 'STU-SMART-RANGE');

        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->call('selectStudent', $student->id)
            ->assertSet('studentId', (string) $student->id)
            ->assertSet('items.0.enabled', true)
            ->assertSet('items.0.evaluation', 'good')
            ->set('items.0.evaluation', 'excellent')
            ->assertSet('items.0.evaluation', 'excellent')
            ->call('selectSurah', 0, 'start', '2')
            ->assertSet('items.0.start_surah_id', '2')
            ->assertSet('items.0.end_surah_id', '2')
            ->set('items.0.start_ayah_number', '5')
            ->assertSet('items.0.end_ayah_number', '5')
            ->call('selectSurah', 0, 'end', '1')
            ->assertHasErrors(['items.0.end_surah_id'])
            ->call('selectSurah', 0, 'end', '2')
            ->set('items.0.end_ayah_number', '4')
            ->call('save')
            ->assertHasErrors(['items.0.end_ayah_number'])
            ->set('items.0.end_ayah_number', '6')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('تم حفظ الحضور والتسميع اليومي للطالب.');

        $this->assertDatabaseHas('recitation_items', [
            'type' => 'new_memorization',
            'start_ayah_id' => 12,
            'end_ayah_id' => 13,
        ]);

        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->call('selectStudent', $student->id)
            ->assertHasErrors(['studentId']);

        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->set('studentId', (string) $student->id)
            ->set('attendanceStatus', 'absent')
            ->call('save')
            ->assertHasErrors(['recordDate']);
    }

    public function test_teacher_and_guardian_student_visibility_is_scoped(): void
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $ownHalaqa, $otherHalaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-SCOPE',
            'active' => true,
        ]);
        $ownHalaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);
        $ownStudent = $this->createStudent($teacherUser, $ownHalaqa, 'STU-OWN');
        $otherStudent = $this->createStudent($teacherUser, $otherHalaqa, 'STU-OTHER');

        $visibleIds = app(StudentVisibilityService::class)->queryFor($teacherUser)->pluck('id')->all();
        $this->assertSame([$ownStudent->id], $visibleIds);
        $this->actingAs($teacherUser)->get(route('students.show', $ownStudent))->assertOk();
        $this->actingAs($teacherUser)->get(route('students.show', $otherStudent))->assertForbidden();

        $guardianUser = User::factory()->create();
        $guardianUser->assignRole('guardian');
        $guardian = Guardian::query()->create([
            'user_id' => $guardianUser->id,
            'full_name' => $guardianUser->name,
            'phone' => '0599000002',
        ]);
        $guardian->students()->attach($otherStudent->id, [
            'relationship' => 'father',
            'is_primary' => true,
            'can_receive_notifications' => true,
        ]);
        $this->actingAs($guardianUser)->get(route('students.show', $otherStudent))->assertOk();
        $this->actingAs($guardianUser)->get(route('students.show', $ownStudent))->assertForbidden();
    }

    /** @return array{Center, Branch, Halaqa, Halaqa} */
    private function organization(): array
    {
        $center = Center::query()->create(['name' => 'المركز الرئيس', 'code' => 'MAIN']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'الفرع الأول', 'code' => 'B1']);
        $first = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة الإتقان',
            'code' => 'H1',
            'capacity' => 20,
            'active' => true,
        ]);
        $second = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة التميز',
            'code' => 'H2',
            'capacity' => 20,
            'active' => true,
        ]);

        return [$center, $branch, $first, $second];
    }

    private function createStudent(User $actor, ?Halaqa $halaqa, string $number): Student
    {
        return app(CreateStudentAction::class)->execute([
            'student_number' => $number,
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'علي',
            'family_name' => $number,
            'identity_number' => null,
            'birth_date' => '2014-01-01',
            'contact_phone' => null,
            'registration_date' => today()->toDateString(),
            'status' => 'active',
            'halaqa_id' => $halaqa?->id,
            'notes' => null,
        ], $actor);
    }
}
