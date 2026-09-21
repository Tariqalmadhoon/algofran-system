<?php

namespace Tests\Feature;

use App\Actions\Guardians\CreateGuardianAction;
use App\Actions\Students\ArchiveStudentAction;
use App\Actions\Students\CreateStudentAction;
use App\Actions\Students\EnrollStudentInHalaqaAction;
use App\Actions\Students\RecordInitialBaselineAction;
use App\Livewire\StudentProfile;
use App\Livewire\StudentsIndex;
use App\Livewire\StudentTrash;
use App\Livewire\TeacherDailyRecorder;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Guardian;
use App\Models\Halaqa;
use App\Models\PrivateFile;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\ReportExport;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\PrivateFileService;
use App\Services\QuranRangeService;
use App\Services\ReportDataService;
use App\Services\StudentVisibilityService;
use App\Services\WhatsAppLinkService;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        [$center, $branch, $firstHalaqa, $secondHalaqa] = $this->organization();
        $this->staffInCenter($registrar, $center, $branch, 'STF-LIFECYCLE');

        $student = $this->createStudent($registrar, $firstHalaqa, 'STU-001');
        $this->assertSame($firstHalaqa->id, $student->current_halaqa_id);
        $this->assertDatabaseHas('halaqa_enrollments', ['student_id' => $student->id, 'halaqa_id' => $firstHalaqa->id, 'ends_at' => null]);

        app(EnrollStudentInHalaqaAction::class)->execute($student, $secondHalaqa, today()->toDateString(), $registrar, 'تغيير المستوى');
        $firstEnrollment = $student->enrollments()->where('halaqa_id', $firstHalaqa->id)->firstOrFail();
        $this->assertSame(today()->subDay()->toDateString(), $firstEnrollment->ends_at->toDateString());
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
        Storage::fake('private');
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
        $student->update(['identity_number' => 'ID-STU-DAILY']);

        $component = Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->assertSee('تصدير سجلات الحفظ')
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

        $component
            ->assertSee('السجلات السابقة: 1')
            ->assertSee('الكشف السابق')
            ->call('showStudentHistory', $student->id)
            ->assertSet('historyStudentId', (string) $student->id)
            ->assertSee('الكشف السابق للطالب')
            ->assertSee('إجمالي الجلسات')
            ->assertSee('من الفاتحة آية 1 إلى الفاتحة آية 7')
            ->assertSee('حفظ جديد')
            ->assertSee('مراجعة قريبة')
            ->assertSee('التقييم العام: جيد جدًا')
            ->call('setHistoryFilter', 'revision')
            ->assertSet('historyFilter', 'revision')
            ->assertSee('مراجعة قريبة')
            ->call('closeStudentHistory')
            ->assertSet('historyStudentId', '');

        $report = app(ReportDataService::class)->build('memorization_records', [
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ], $teacherUser);
        $this->assertSame(['رقم الطالب', 'التاريخ واليوم', 'اسم الطالب', 'هوية الطالب', 'الحفظ', 'المراجعة', 'التقييم'], $report['headings']);
        $this->assertCount(1, $report['rows']);
        $this->assertSame('STU-DAILY', $report['rows'][0][0]);
        $this->assertStringContainsString(today()->toDateString(), $report['rows'][0][1]);
        $this->assertSame('ID-STU-DAILY', $report['rows'][0][3]);
        $this->assertSame('سورة الفاتحة، الآيات 1–7', $report['rows'][0][4]);
        $this->assertSame('مراجعة قريبة: سورة البقرة، الآيات 1–5', $report['rows'][0][5]);
        $this->assertStringContainsString('عام: جيد جدًا', $report['rows'][0][6]);

        $component
            ->set('exportDateFrom', today()->toDateString())
            ->set('exportDateTo', today()->toDateString())
            ->call('exportMemorizationRecords')
            ->assertHasNoErrors()
            ->assertSee('تنزيل ملف Excel');

        $export = ReportExport::query()->findOrFail($component->get('latestExportId'));
        $this->assertSame('ready', $export->status);
        $this->assertSame(1, $export->rows_count);
        $this->assertNotNull($export->private_file_id);
        Storage::disk('private')->assertExists($export->privateFile->path);

        $workbook = IOFactory::load(Storage::disk('private')->path($export->privateFile->path));
        $this->assertSame($report['headings'], $workbook->getActiveSheet()->rangeToArray('A1:G1')[0]);
        $this->actingAs($teacherUser)->get(route('private-files.show', $export->privateFile))->assertOk();
    }

    public function test_assigned_teacher_can_add_students_only_to_their_halaqa_with_a_visible_photo(): void
    {
        Storage::fake('private');
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $ownHalaqa, $otherHalaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-CREATE',
            'active' => true,
        ]);
        $ownHalaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);

        Livewire::actingAs($teacherUser)
            ->test(StudentsIndex::class)
            ->assertSet('halaqaId', (string) $ownHalaqa->id)
            ->assertSee($ownHalaqa->name)
            ->assertDontSee($otherHalaqa->name)
            ->assertDontSee('نوع الكفالة')
            ->assertDontSee('جهة الكفالة')
            ->set('studentNumber', 'STU-TEACHER-001')
            ->set('firstName', 'محمد')
            ->set('fatherName', 'أحمد')
            ->set('grandfatherName', 'علي')
            ->set('familyName', 'الغفران')
            ->set('contactPhone', '0599000055')
            ->set('photo', UploadedFile::fake()->image('student-photo.jpg', 400, 400))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('محمد أحمد علي الغفران');

        $student = Student::query()->where('student_number', 'STU-TEACHER-001')->firstOrFail();
        $this->assertSame($ownHalaqa->id, $student->current_halaqa_id);
        $this->assertSame('0599000055', $student->contact_phone);
        $this->assertNotNull($student->photo_private_file_id);
        $this->assertDatabaseHas('private_files', [
            'id' => $student->photo_private_file_id,
            'owner_type' => $student->getMorphClass(),
            'owner_id' => $student->id,
        ]);
        $photo = PrivateFile::query()->findOrFail($student->photo_private_file_id);
        Livewire::actingAs($teacherUser)
            ->test(StudentsIndex::class)
            ->assertSee(route('private-files.preview', $photo), false)
            ->assertSee('https://wa.me/972599000055', false);

        Livewire::actingAs($teacherUser)
            ->test(StudentsIndex::class)
            ->set('studentNumber', 'STU-TEACHER-002')
            ->set('firstName', 'طالب')
            ->set('fatherName', 'غير')
            ->set('grandfatherName', 'مصرح')
            ->set('familyName', 'له')
            ->set('halaqaId', (string) $otherHalaqa->id)
            ->call('save')
            ->assertHasErrors(['halaqaId']);

        $this->assertDatabaseMissing('students', ['student_number' => 'STU-TEACHER-002']);
    }

    public function test_assigned_teacher_can_complete_the_full_profile_of_their_student_only(): void
    {
        $this->seed(QuranReferenceSeeder::class);
        Storage::fake('private');

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $ownHalaqa, $otherHalaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-FULL-PROFILE',
            'active' => true,
        ]);
        $ownHalaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);

        $ownStudent = $this->createStudent($teacherUser, $ownHalaqa, 'STU-FULL-PROFILE');
        $registrar = User::factory()->create();
        $registrar->assignRole('registrar');
        $otherStudent = $this->createStudent($registrar, $otherHalaqa, 'STU-NOT-ASSIGNED');

        $component = Livewire::actingAs($teacherUser)
            ->test(StudentProfile::class, ['student' => $ownStudent])
            ->assertSee('لا يحتاج ولي الأمر إلى حساب مستقل')
            ->assertSee($ownHalaqa->name)
            ->assertDontSee($otherHalaqa->name)
            ->assertDontSee('نوع الكفالة')
            ->assertDontSee('جهة الكفالة')
            ->set('profileIdentityNumber', 'TEACHER-STUDENT-ID')
            ->set('profileContactPhone', '0599000011')
            ->set('profileNotes', 'استكمل المحفّظ ملف الطالب')
            ->set('profilePhoto', UploadedFile::fake()->image('student-profile.jpg', 500, 500))
            ->set('profileIdentityDocument', UploadedFile::fake()->create('student-id.pdf', 100, 'application/pdf'))
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSee('https://wa.me/972599000011', false)
            ->set('guardianName', 'خالد أحمد')
            ->set('guardianPhone', '0599000022')
            ->set('guardianRelationship', 'father')
            ->set('guardianIdentityDocument', UploadedFile::fake()->create('guardian-id.pdf', 100, 'application/pdf'))
            ->call('saveGuardian')
            ->assertHasNoErrors()
            ->assertSee('https://wa.me/972599000022', false)
            ->set('baselineStartSurahId', '1')
            ->set('baselineStartAyahNumber', '1')
            ->set('baselineEndAyahNumber', '7')
            ->call('saveBaseline')
            ->assertHasNoErrors();

        $student = $ownStudent->fresh();
        $guardian = $student->guardians()->firstOrFail();
        $this->assertSame('TEACHER-STUDENT-ID', $student->identity_number);
        $this->assertSame('0599000011', $student->contact_phone);
        $this->assertNotNull($student->photo_private_file_id);
        $this->assertNotNull($student->identity_private_file_id);
        $this->assertSame('خالد أحمد', $guardian->full_name);
        $this->assertNotNull($guardian->identity_private_file_id);
        $this->assertDatabaseHas('student_memorization_baselines', [
            'student_id' => $student->id,
            'start_ayah_id' => 1,
            'end_ayah_id' => 7,
        ]);

        $component
            ->call('editGuardian', $guardian->id)
            ->assertSet('editingGuardianId', $guardian->id)
            ->assertSet('guardianName', 'خالد أحمد')
            ->set('guardianPhone', '0599000033')
            ->call('saveGuardian')
            ->assertHasNoErrors()
            ->assertSet('editingGuardianId', null)
            ->assertSee('تم تحديث بيانات ولي الأمر.');
        $this->assertSame('0599000033', $guardian->fresh()->phone);
        $this->assertSame(1, $student->guardians()->count());
        $this->assertDatabaseHas('student_timeline_events', [
            'student_id' => $student->id,
            'event_type' => 'guardian.updated',
        ]);

        $this->assertTrue($teacherUser->can('update', $student));
        $this->assertFalse($teacherUser->can('update', $otherStudent));
        $this->actingAs($teacherUser)
            ->get(route('private-files.show', $guardian->identityDocument))
            ->assertOk();

        $component
            ->set('enrollmentHalaqaId', (string) $otherHalaqa->id)
            ->call('saveEnrollment')
            ->assertHasErrors(['enrollmentHalaqaId']);
    }

    public function test_student_photo_can_be_previewed_replaced_and_removed(): void
    {
        Storage::fake('private');
        $registrar = User::factory()->create();
        $registrar->assignRole('registrar');
        $this->actingAs($registrar);
        [$center, $branch, $halaqa] = $this->organization();
        $this->staffInCenter($registrar, $center, $branch, 'STF-PHOTO');
        $student = $this->createStudent($registrar, $halaqa, 'STU-PHOTO');

        Livewire::actingAs($registrar)
            ->test(StudentProfile::class, ['student' => $student])
            ->set('profilePhoto', UploadedFile::fake()->image('student.jpg', 500, 500))
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSee('تم تحديث بيانات الطالب.');

        $photo = PrivateFile::query()->findOrFail($student->fresh()->photo_private_file_id);
        Storage::disk('private')->assertExists($photo->path);
        $this->get(route('private-files.preview', $photo))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        Livewire::actingAs($registrar)
            ->test(StudentProfile::class, ['student' => $student->fresh()])
            ->call('removeProfilePhotoSelection')
            ->assertSet('removeProfilePhoto', true)
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertNull($student->fresh()->photo_private_file_id);
        $this->assertSoftDeleted('private_files', ['id' => $photo->id]);
        Storage::disk('private')->assertMissing($photo->path);
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

    public function test_assigned_teacher_can_archive_a_student_without_deleting_his_history(): void
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $halaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-ARCHIVE',
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subDay()->toDateString(),
        ]);
        $student = $this->createStudent($teacherUser, $halaqa, 'STU-ARCHIVE');

        Livewire::actingAs($teacherUser)
            ->test(StudentsIndex::class)
            ->assertSee('حذف')
            ->call('archiveStudent', $student->id)
            ->assertHasNoErrors()
            ->assertSee('نُقل ملف الطالب إلى سلة المهملات مع الاحتفاظ بسجلاته كاملة.');

        $student = Student::withTrashed()->findOrFail($student->id);
        $this->assertSame('archived', $student->status->value);
        $this->assertNull($student->current_halaqa_id);
        $this->assertNotNull($student->deleted_at);
        $this->assertDatabaseHas('halaqa_enrollments', [
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'ends_at' => today()->toDateString(),
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.archived', 'auditable_id' => $student->id]);
    }

    public function test_administrator_can_restore_or_permanently_delete_students_from_the_trash(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('super-admin');
        [$center, $branch, $halaqa] = $this->organization();
        $student = $this->createStudent($administrator, $halaqa, 'STU-TRASH');

        app(ArchiveStudentAction::class)->execute($student, $administrator);

        Livewire::actingAs($administrator)
            ->test(StudentTrash::class)
            ->assertSee('سلة مهملات الطلاب')
            ->assertSee($student->full_name)
            ->call('restoreStudent', $student->id)
            ->assertHasNoErrors()
            ->assertSee('استُعيد ملف الطالب إلى قائمة الطلاب. يلزم إلحاقه بحلقة من ملفه عند الحاجة.');

        $restored = Student::query()->findOrFail($student->id);
        $this->assertSame('active', $restored->status->value);
        $this->assertNull($restored->pre_archive_status);
        $this->assertNull($restored->current_halaqa_id);

        app(ArchiveStudentAction::class)->execute($restored, $administrator);

        Livewire::actingAs($administrator)
            ->test(StudentTrash::class)
            ->call('permanentlyDeleteStudent', $student->id)
            ->assertHasNoErrors()
            ->assertSee('حُذف ملف الطالب نهائيًا مع البيانات المرتبطة به.');

        $this->assertNull(Student::withTrashed()->find($student->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.permanently_deleted', 'auditable_id' => $student->id]);
    }

    public function test_whatsapp_contact_links_normalize_local_and_international_numbers(): void
    {
        $links = app(WhatsAppLinkService::class);

        $this->assertSame('https://wa.me/972599000011', $links->conversationUrl('0599 000 011'));
        $this->assertSame('https://wa.me/972567973076', $links->conversationUrl('+972 56 797 3076'));
        $this->assertSame('https://wa.me/972599000011', $links->conversationUrl('00972 599 000 011'));
        $this->assertNull($links->conversationUrl('not a phone number'));
    }

    public function test_excused_absence_disables_recitation_and_saves_attendance_without_evaluation(): void
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        [$center, $branch, $halaqa] = $this->organization();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-EXCUSED',
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth()->toDateString(),
        ]);
        $student = $this->createStudent($teacherUser, $halaqa, 'STU-EXCUSED');

        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->call('selectStudent', $student->id)
            ->assertSet('studentId', (string) $student->id)
            ->assertSet('items.0.enabled', true)
            ->set('generalEvaluation', 'excellent')
            ->set('attendanceStatus', 'excused')
            ->assertSet('generalEvaluation', '')
            ->assertSet('items.0.enabled', false)
            ->assertSet('items.1.enabled', false)
            ->assertSet('items.2.enabled', false)
            ->assertSet('items.3.enabled', false)
            ->assertSet('items.4.enabled', false)
            ->assertSet('items.5.enabled', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('studentId', '');

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
        $registrar = User::factory()->create();
        $registrar->assignRole('registrar');
        $otherStudent = $this->createStudent($registrar, $otherHalaqa, 'STU-OTHER');

        $visibleIds = app(StudentVisibilityService::class)->queryFor($teacherUser)->pluck('id')->all();
        $this->assertSame([$ownStudent->id], $visibleIds);
        $this->actingAs($teacherUser)->get(route('students.show', $ownStudent))->assertOk();
        $this->actingAs($teacherUser)->get(route('students.show', $otherStudent))->assertForbidden();
        Livewire::actingAs($teacherUser)
            ->test(TeacherDailyRecorder::class)
            ->set('halaqaId', (string) $otherHalaqa->id)
            ->call('showStudentHistory', $otherStudent->id)
            ->assertSet('historyStudentId', '')
            ->assertHasErrors(['historyStudentId']);

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
            'registration_date' => today()->subDay()->toDateString(),
            'status' => 'active',
            'halaqa_id' => $halaqa?->id,
            'notes' => null,
        ], $actor);
    }

    private function staffInCenter(User $user, Center $center, Branch $branch, string $number): StaffProfile
    {
        return StaffProfile::query()->create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => $number,
            'job_title' => 'مسجل',
            'active' => true,
        ]);
    }
}
