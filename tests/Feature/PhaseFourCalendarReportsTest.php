<?php

namespace Tests\Feature;

use App\Livewire\NotificationCenter;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\HalaqaSchedule;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\CalendarFeedService;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFourCalendarReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_calendar_combines_recurring_halaqa_schedule_and_custom_events(): void
    {
        [$manager, , , $halaqa] = $this->organization();
        HalaqaSchedule::query()->create(['halaqa_id' => $halaqa->id, 'weekday' => today()->dayOfWeek, 'starts_at' => '08:00', 'ends_at' => '10:00', 'room' => 'A1']);
        $halaqa->calendarEvents()->create([
            'center_id' => $halaqa->center_id, 'branch_id' => $halaqa->branch_id, 'type' => 'exam', 'title' => 'اختبار الحفظ',
            'starts_at' => today()->setTime(11, 0), 'ends_at' => today()->setTime(12, 0), 'privacy' => 'internal', 'created_by' => $manager->id,
        ]);

        $events = app(CalendarFeedService::class)->events($manager, today()->startOfDay(), today()->endOfDay());

        $this->assertTrue($events->contains(fn (array $event) => $event['type'] === 'halaqa' && $event['title'] === 'حلقة حلقة الإتقان'));
        $this->assertTrue($events->contains(fn (array $event) => $event['type'] === 'exam' && $event['title'] === 'اختبار الحفظ'));
        $this->actingAs($manager)->get(route('calendar.index'))->assertOk()->assertSee('التقويم الموحّد');
    }

    public function test_database_notification_center_tracks_read_state(): void
    {
        [$manager] = $this->organization();
        $manager->notify(new SystemNotification('تقرير جاهز', 'اكتمل إعداد التقرير.', route('reports.index')));
        $notification = $manager->unreadNotifications()->firstOrFail();

        Livewire::actingAs($manager)->test(NotificationCenter::class)
            ->assertSee('تقرير جاهز')
            ->call('markRead', $notification->id)
            ->assertHasNoErrors();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($manager)->get(route('notifications.index'))->assertOk();
    }

    public function test_filtered_report_exports_real_secure_xlsx_file(): void
    {
        Storage::fake('private');
        [$manager, , , $halaqa] = $this->organization();
        Student::query()->create([
            'student_number' => 'S-001', 'first_name' => 'أحمد', 'father_name' => 'محمد', 'grandfather_name' => 'علي',
            'family_name' => 'الخطيب', 'full_name' => 'أحمد محمد علي الخطيب', 'registration_date' => today(), 'status' => 'active',
            'current_halaqa_id' => $halaqa->id, 'created_by' => $manager->id, 'updated_by' => $manager->id,
        ]);
        $this->actingAs($manager);

        $report = app(ReportDataService::class)->build('students', ['halaqa_id' => $halaqa->id], $manager);
        $this->assertCount(1, $report['rows']);
        $this->assertSame('S-001', $report['rows'][0][0]);

        $export = app(ReportExportService::class)->request('management_summary', ['halaqa_id' => $halaqa->id], $manager);
        $this->assertSame('ready', $export->status);
        $this->assertNotNull($export->private_file_id);
        Storage::disk('private')->assertExists($export->privateFile->getRawOriginal('path'));
        $this->get(route('private-files.show', $export->privateFile))->assertOk();

        $other = User::factory()->create();
        $other->assignRole('report-viewer');
        $this->actingAs($other)->get(route('private-files.show', $export->privateFile))->assertForbidden();
    }

    /** @return array{User, Center, Branch, Halaqa} */
    private function organization(): array
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $center = Center::query()->create(['name' => 'المركز الرئيسي', 'code' => 'MAIN']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'الفرع الأول', 'code' => 'B1']);
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-CALENDAR-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create(['user_id' => $teacherUser->id, 'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-001', 'active' => true]);
        $halaqa = Halaqa::query()->create(['center_id' => $center->id, 'branch_id' => $branch->id, 'primary_teacher_id' => $teacher->id, 'name' => 'حلقة الإتقان', 'code' => 'H1', 'capacity' => 20, 'active' => true]);

        return [$manager, $center, $branch, $halaqa];
    }
}
