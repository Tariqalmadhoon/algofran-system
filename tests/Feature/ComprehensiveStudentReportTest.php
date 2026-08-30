<?php

namespace Tests\Feature;

use App\Exports\ReportWorkbookExport;
use App\Livewire\ReportCenter;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DailyRecord;
use App\Models\Guardian;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Tests\TestCase;

class ComprehensiveStudentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QuranReferenceSeeder::class);
    }

    public function test_center_and_teacher_exports_are_scoped_and_match_the_arabic_rtl_form(): void
    {
        Storage::fake('private');
        [$manager, $center, $branch] = $this->managementContext();
        [$teacherUser, $teacher, $halaqa] = $this->teacherContext($center, $branch, 'T-001', 'المحفّظ محمد أحمد', '900100001');
        $student = $this->student($halaqa, 'ST-001', 'أحمد محمود عبد الله الغفران', '401000001');
        $guardian = Guardian::query()->create([
            'full_name' => 'محمود عبد الله الغفران',
            'identity_number' => '501000001',
            'phone' => '0599000001',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);
        $student->guardians()->attach($guardian->id, [
            'relationship' => 'father',
            'is_primary' => true,
            'can_receive_notifications' => true,
        ]);
        $this->recordProgress($student, $halaqa, $teacher, $teacherUser);

        [, $secondTeacher, $secondHalaqa] = $this->teacherContext($center, $branch, 'T-002', 'المحفّظ يوسف خالد', '900100002');
        $this->student($secondHalaqa, 'ST-002', 'يوسف خالد عبد الرحمن النجار', '401000002');

        $reports = app(ReportDataService::class);
        $centerReport = $reports->build('student_comprehensive', ['center_id' => $center->id], $manager);
        $teacherReport = $reports->build('student_comprehensive', [
            'center_id' => $center->id,
            'teacher_profile_id' => $teacher->id,
        ], $manager);

        $this->assertCount(2, $centerReport['rows']);
        $this->assertCount(1, $teacherReport['rows']);
        $this->assertSame('comprehensive_students', $teacherReport['layout']);
        $this->assertSame([
            'متسلسل', 'المركز', 'اسم الطالب رباعيًا', 'رقم هوية الطالب', 'تاريخ الميلاد',
            'رقم هوية ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة لولي الأمر', 'نوع الكفالة', 'جهة الكفالة',
            'اسم المعلم رباعيًا', 'رقم هوية المعلم', 'السرد من', 'السرد إلى', 'الحفظ من', 'الحفظ إلى', 'السورة', 'الآية',
        ], $teacherReport['headings']);
        $this->assertSame([
            1, $center->name, $student->full_name, '401000001', '2013-05-10', '501000001', 'محمود عبد الله الغفران', 'الأب',
            'كفالة تعليمية', 'صندوق الغفران', 'المحفّظ محمد أحمد', '900100001', 30, 28, 30, 28, 'المجادلة', 1,
        ], $teacherReport['rows'][0]);

        Livewire::actingAs($manager)
            ->test(ReportCenter::class)
            ->set('reportType', 'student_comprehensive')
            ->set('centerId', (string) $center->id)
            ->set('comprehensiveScope', 'teacher')
            ->set('teacherProfileId', (string) $teacher->id)
            ->assertSee('المركز كاملًا')
            ->assertSee('محفّظ محدد')
            ->assertSee($student->full_name)
            ->call('requestExport')
            ->assertHasNoErrors()
            ->assertSet('tab', 'exports');

        $storedExport = app(ReportExportService::class)->request('student_comprehensive', [
            'center_id' => $center->id,
            'teacher_profile_id' => $teacher->id,
        ], $manager);
        $this->assertSame('ready', $storedExport->status);
        Storage::disk('private')->assertExists($storedExport->privateFile->getRawOriginal('path'));

        $this->assertTrue($reports->canBuildComprehensive($teacherUser));
        Livewire::actingAs($teacherUser)
            ->test(ReportCenter::class)
            ->set('reportType', 'student_comprehensive')
            ->assertSet('centerId', (string) $center->id)
            ->assertSet('comprehensiveScope', 'teacher')
            ->assertSet('teacherProfileId', (string) $teacher->id)
            ->call('requestExport')
            ->assertHasNoErrors()
            ->assertSet('tab', 'exports');
        $teacherOwnReport = $reports->build('student_comprehensive', [
            'center_id' => $center->id,
            'teacher_profile_id' => $secondTeacher->id,
        ], $teacherUser);
        $this->assertCount(1, $teacherOwnReport['rows']);
        $this->assertSame($student->full_name, $teacherOwnReport['rows'][0][2]);

        $xlsx = Excel::raw(new ReportWorkbookExport($teacherReport), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'gofran-comprehensive-');
        file_put_contents($path, $xlsx);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertTrue($sheet->getRightToLeft());
            $this->assertSame('A3', $sheet->getFreezePane());
            $this->assertTrue($sheet->getMergeCells()['M1:N1'] === 'M1:N1');
            $this->assertTrue($sheet->getMergeCells()['O1:P1'] === 'O1:P1');
            $this->assertTrue($sheet->getMergeCells()['Q1:R1'] === 'Q1:R1');
            $this->assertSame('A2:R3', $sheet->getAutoFilter()->getRange());
            $this->assertSame('آخر سرد أتمه الطالب (بالأجزاء)', $sheet->getCell('M1')->getValue());
            $this->assertSame('متسلسل', $sheet->getCell('A2')->getValue());
            $this->assertSame('401000001', $sheet->getCell('D3')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D3')->getDataType());
            $this->assertSame('yyyy-mm-dd', $sheet->getStyle('E3')->getNumberFormat()->getFormatCode());
            $this->assertSame(30, $sheet->getCell('O3')->getValue());
            $this->assertSame(28, $sheet->getCell('P3')->getValue());
            $this->assertSame('المجادلة', $sheet->getCell('Q3')->getValue());
            $this->assertSame(1, $sheet->getCell('R3')->getValue());
            $this->assertSame(PageSetup::ORIENTATION_LANDSCAPE, $sheet->getPageSetup()->getOrientation());
            $this->assertSame(PageSetup::PAPERSIZE_A3, $sheet->getPageSetup()->getPaperSize());
            $this->assertSame(Fill::FILL_SOLID, $sheet->getStyle('A2')->getFill()->getFillType());
            $this->assertSame('FFF2C40F', $sheet->getStyle('A2')->getFill()->getStartColor()->getARGB());
        } finally {
            @unlink($path);
        }
    }

    /** @return array{User, Center, Branch} */
    private function managementContext(): array
    {
        $manager = User::factory()->create(['name' => 'مدير مركز الغفران']);
        $manager->assignRole('center-manager');
        $center = Center::query()->create(['name' => 'مركز الغفران لتحفيظ القرآن الكريم', 'code' => 'GF']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'السجل الداخلي', 'code' => 'SYSTEM']);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'M-001',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);

        return [$manager, $center, $branch];
    }

    /** @return array{User, TeacherProfile, Halaqa} */
    private function teacherContext(Center $center, Branch $branch, string $number, string $name, string $identity): array
    {
        $teacherUser = User::factory()->create(['name' => $name]);
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => $number,
            'identity_number' => $identity,
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => "حلقة {$number}",
            'code' => "H-{$number}",
            'capacity' => 30,
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subYear()->toDateString(),
            'assigned_by' => $teacherUser->id,
        ]);

        return [$teacherUser, $teacher, $halaqa];
    }

    private function student(Halaqa $halaqa, string $number, string $name, string $identity): Student
    {
        return Student::query()->create([
            'student_number' => $number,
            'first_name' => str($name)->before(' '),
            'father_name' => 'محمود',
            'grandfather_name' => 'عبد الله',
            'family_name' => str($name)->afterLast(' '),
            'full_name' => $name,
            'identity_number' => $identity,
            'birth_date' => '2013-05-10',
            'sponsorship_type' => 'كفالة تعليمية',
            'sponsorship_organization' => 'صندوق الغفران',
            'registration_date' => today()->subYear()->toDateString(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
    }

    private function recordProgress(Student $student, Halaqa $halaqa, TeacherProfile $teacher, User $actor): void
    {
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'halaqa_id' => $halaqa->id,
            'record_date' => today()->toDateString(),
            'status' => 'present',
            'recorded_by' => $actor->id,
        ]);
        $record = DailyRecord::query()->create([
            'student_id' => $student->id,
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $halaqa->id,
            'attendance_id' => $attendance->id,
            'record_date' => today()->toDateString(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $mujadilaStart = QuranAyah::query()->where('surah_id', 58)->where('ayah_number', 1)->firstOrFail();
        $mujadilaEnd = QuranAyah::query()->where('surah_id', 58)->orderByDesc('ayah_number')->firstOrFail();
        $quranEnd = QuranAyah::query()->orderByDesc('global_order')->firstOrFail();
        $record->recitationItems()->create([
            'type' => 'new_memorization',
            'start_ayah_id' => $mujadilaStart->id,
            'end_ayah_id' => $mujadilaEnd->id,
            'evaluation' => 'excellent',
        ]);
        $record->recitationItems()->create([
            'type' => 'recitation',
            'start_ayah_id' => $mujadilaStart->id,
            'end_ayah_id' => $quranEnd->id,
            'evaluation' => 'excellent',
        ]);
    }
}
