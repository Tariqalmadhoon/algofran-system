<?php

namespace Tests\Feature;

use App\Exports\AcademicCourseResultsExport;
use App\Livewire\AcademicManager;
use App\Models\Branch;
use App\Models\Center;
use App\Models\CourseEnrollment;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AcademicRecordsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AcademicBulkCourseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_manager_registers_center_students_completes_course_and_downloads_structured_excel(): void
    {
        [$manager, $center, $teacher, $halaqa] = $this->organization();
        $first = $this->student($halaqa, 'ST-001', 'ID-0001', 'أحمد محمود الغفران');
        $second = $this->student($halaqa, 'ST-002', 'ID-0002', 'يوسف خالد النجار');
        $course = app(AcademicRecordsService::class)->createCourse([
            'center_id' => $center->id,
            'branch_id' => null,
            'instructor_id' => $teacher->id,
            'name' => 'دورة أحكام التجويد',
            'description' => 'دورة تطبيقية',
            'starts_at' => today()->subMonth()->toDateString(),
            'ends_at' => today()->toDateString(),
            'hours' => 20,
            'status' => 'active',
        ], $manager);

        $this->actingAs($manager)->get(route('academic.index'))
            ->assertOk();

        $component = Livewire::actingAs($manager)->test(AcademicManager::class)
            ->set('bulkCourseId', (string) $course->id)
            ->assertSeeHtml('wire:click="selectAllStudents"')
            ->assertSee($first->full_name)
            ->assertSee($second->full_name)
            ->call('selectAllStudents')
            ->set('bulkEnrollmentDate', today()->subMonth()->toDateString())
            ->call('registerSelectedStudents')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing([], $component->get('selectedStudentIds'));
        $this->assertDatabaseCount('course_enrollments', 2);
        $enrollments = CourseEnrollment::query()->where('course_id', $course->id)->orderBy('student_id')->get();
        $component
            ->set('resultsCourseId', (string) $course->id)
            ->set("courseResultRows.{$enrollments[0]->id}.result", '94')
            ->assertSet("courseResultRows.{$enrollments[0]->id}.grade", 'ممتاز')
            ->set("courseResultRows.{$enrollments[0]->id}.completed_at", today()->toDateString())
            ->set("courseResultRows.{$enrollments[0]->id}.notes", 'إتقان واضح في التطبيق')
            ->set("courseResultRows.{$enrollments[1]->id}.result", '82')
            ->assertSet("courseResultRows.{$enrollments[1]->id}.grade", 'جيد جدًا')
            ->set("courseResultRows.{$enrollments[1]->id}.completed_at", today()->toDateString())
            ->set("courseResultRows.{$enrollments[1]->id}.notes", 'يحتاج متابعة المدود')
            ->call('saveCourseResults')
            ->assertHasNoErrors()
            ->call('exportCourseResults')
            ->assertFileDownloaded();

        $this->assertSame(2, CourseEnrollment::query()->where('status', 'completed')->count());
        $this->assertDatabaseHas('course_enrollments', ['student_id' => $first->id, 'result' => 94, 'grade' => 'ممتاز']);
        $this->assertDatabaseHas('achievements', ['student_id' => $first->id, 'type' => 'course', 'title' => 'إتمام دورة أحكام التجويد']);
        $this->assertDatabaseHas('achievements', ['student_id' => $second->id, 'type' => 'course']);
        $this->assertDatabaseHas('student_timeline_events', ['student_id' => $first->id, 'event_type' => 'course.completed']);

        $exportEnrollments = CourseEnrollment::query()->where('course_id', $course->id)->with([
            'student.currentHalaqa.primaryTeacher.user',
        ])->orderBy('student_id')->get();
        $xlsx = Excel::raw(new AcademicCourseResultsExport($course->load(['center', 'instructor.user']), $exportEnrollments), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'academic-results-');
        file_put_contents($path, $xlsx);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame(['متسلسل', 'اسم الطالب', 'رقم الهوية', 'المركز', 'المحفّظ', 'الدورة المنجزة', 'الدرجة من 100', 'التقييم', 'تاريخ الإنجاز', 'الملاحظات'], $sheet->rangeToArray('A1:J1')[0]);
            $this->assertSame('ID-0001', $sheet->getCell('C2')->getValue());
            $this->assertSame($center->name, $sheet->getCell('D2')->getValue());
            $this->assertSame($teacher->user->name, $sheet->getCell('E2')->getValue());
            $this->assertSame('دورة أحكام التجويد', $sheet->getCell('F2')->getValue());
            $this->assertEquals(94, $sheet->getCell('G2')->getValue());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertTrue($sheet->getRightToLeft());
        } finally {
            @unlink($path);
        }
    }

    /** @return array{User, Center, TeacherProfile, Halaqa} */
    private function organization(): array
    {
        $center = Center::query()->create(['name' => 'مركز الغفران لتحفيظ القرآن الكريم', 'code' => 'GF']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'السجل الداخلي', 'code' => 'SYSTEM']);
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        StaffProfile::query()->create(['user_id' => $manager->id, 'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'M-001', 'job_title' => 'مدير المركز', 'active' => true]);
        $teacherUser = User::factory()->create(['name' => 'المحفّظ محمد أحمد']);
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create(['user_id' => $teacherUser->id, 'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-001', 'active' => true]);
        $halaqa = Halaqa::query()->create(['center_id' => $center->id, 'branch_id' => $branch->id, 'primary_teacher_id' => $teacher->id, 'name' => 'حلقة الإتقان', 'code' => 'H-001', 'capacity' => 30, 'active' => true]);

        return [$manager, $center, $teacher, $halaqa];
    }

    private function student(Halaqa $halaqa, string $number, string $identity, string $name): Student
    {
        return Student::query()->create([
            'student_number' => $number,
            'first_name' => str($name)->before(' '),
            'father_name' => 'محمود',
            'grandfather_name' => 'عبدالله',
            'family_name' => str($name)->afterLast(' '),
            'full_name' => $name,
            'identity_number' => $identity,
            'registration_date' => today()->subYear(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
    }
}
