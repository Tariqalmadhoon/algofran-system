<?php

namespace Tests\Feature;

use App\Livewire\TeacherDailyRecorder;
use App\Models\Center;
use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\DailyRecord;
use App\Models\Guardian;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\GofranManualTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GofranManualTestingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_builds_the_exact_manual_testing_dataset_and_is_idempotent(): void
    {
        Storage::fake('public');

        $legacyAuthor = User::factory()->create();
        $preservedContent = CmsContent::query()->create([
            'type' => 'news',
            'slug' => 'preserved-before-manual-reset',
            'title' => 'محتوى عام محفوظ',
            'body' => 'يجب أن يبقى هذا المحتوى بعد تنظيف البيانات التشغيلية.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_by' => $legacyAuthor->id,
            'updated_by' => $legacyAuthor->id,
        ]);

        $this->seed(GofranManualTestingSeeder::class);

        $center = Center::query()->sole();
        $this->assertSame('مركز الغفران لتحفيظ القرآن الكريم', $center->name);
        $this->assertTrue($center->active);
        $this->assertSame(2, Halaqa::query()->count());
        $this->assertSame(2, User::query()->count());
        $this->assertSame(2, TeacherProfile::query()->count());
        $this->assertSame(1, StaffProfile::query()->count());

        $manager = User::query()->where('email', 'admin1@gofran.com')->firstOrFail();
        $teacher = User::query()->where('email', 'teacher1@gofran.com')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['super-admin', 'center-manager', 'teacher'],
            $manager->getRoleNames()->all(),
        );
        $this->assertSame(['teacher'], $teacher->getRoleNames()->all());
        $this->assertTrue($manager->active);
        $this->assertTrue($teacher->active);
        $this->assertTrue(Hash::check('123456789', $manager->password));
        $this->assertTrue(Hash::check('123456789', $teacher->password));

        $manager->load(['staffProfile', 'teacherProfile']);
        $teacher->load('teacherProfile');
        $this->assertNotNull($manager->staffProfile);
        $this->assertTrue($manager->staffProfile->active);
        $this->assertSame($center->id, $manager->staffProfile->center_id);
        $this->assertNotNull($manager->teacherProfile);
        $this->assertNotNull($teacher->teacherProfile);
        $this->assertTrue($manager->teacherProfile->active);
        $this->assertTrue($teacher->teacherProfile->active);
        $this->assertSame($center->id, $manager->teacherProfile->center_id);
        $this->assertSame($center->id, $teacher->teacherProfile->center_id);

        $managerHalaqa = Halaqa::query()
            ->where('primary_teacher_id', $manager->teacherProfile->id)
            ->firstOrFail();
        $teacherHalaqa = Halaqa::query()
            ->where('primary_teacher_id', $teacher->teacherProfile->id)
            ->firstOrFail();
        $this->assertNotSame($managerHalaqa->id, $teacherHalaqa->id);
        $this->assertDatabaseHas('halaqa_teacher_assignments', [
            'halaqa_id' => $managerHalaqa->id,
            'teacher_profile_id' => $manager->teacherProfile->id,
            'role' => 'primary',
            'ends_at' => null,
        ]);
        $this->assertDatabaseHas('halaqa_teacher_assignments', [
            'halaqa_id' => $teacherHalaqa->id,
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'role' => 'primary',
            'ends_at' => null,
        ]);
        $this->assertTrue($manager->can('recitations.create'));
        $this->assertTrue($manager->can('recitations.export'));
        $this->assertTrue($teacher->can('recitations.create'));
        $this->assertTrue($teacher->can('recitations.export'));

        $this->assertSame(4, Student::query()->count());
        $this->assertSame(4, Guardian::query()->count());
        $this->assertSame(4, DB::table('guardian_student')->count());
        $this->assertSame(4, DB::table('halaqa_enrollments')->count());
        $this->assertSame(2, Student::query()->where('current_halaqa_id', $managerHalaqa->id)->count());
        $this->assertSame(2, Student::query()->where('current_halaqa_id', $teacherHalaqa->id)->count());
        $this->assertSame(2, $this->activeEnrollmentCount($managerHalaqa));
        $this->assertSame(2, $this->activeEnrollmentCount($teacherHalaqa));
        Student::query()->withCount('guardians')->get()->each(
            fn (Student $student) => $this->assertSame(1, $student->guardians_count),
        );
        $this->assertSame(0, DailyRecord::query()->count());
        $this->assertSame(6, CmsContent::query()->where('slug', 'like', 'gofran-%')->count());
        $this->assertSame(4, CmsMedia::query()->where('path', 'like', 'cms/gofran/%')->count());
        $this->assertSame(3, CmsContent::query()->published()->ofType('activity')->where('slug', 'like', 'gofran-%')->count());
        CmsMedia::query()
            ->where('path', 'like', 'cms/gofran/%')
            ->pluck('path')
            ->each(fn (string $path) => Storage::disk('public')->assertExists($path));
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('انطلاق الفصل القرآني الجديد في مركز الغفران');
        $this->get(route('activities.index'))
            ->assertOk()
            ->assertSee('اليوم التحفيزي لطلاب حلقات القرآن');
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('انطلاق الفصل القرآني الجديد في مركز الغفران');

        $managerStudents = Student::query()
            ->where('current_halaqa_id', $managerHalaqa->id)
            ->orderBy('id')
            ->get();
        $teacherStudents = Student::query()
            ->where('current_halaqa_id', $teacherHalaqa->id)
            ->orderBy('id')
            ->get();

        $this->get(route('access.index'))->assertRedirect(route('login'));
        $this->actingAs($manager)
            ->get(route('access.index'))
            ->assertOk();
        $this->actingAs($manager)
            ->get(route('cms.index'))
            ->assertOk()
            ->assertSee('مركز إدارة الموقع')
            ->assertSee('انطلاق الفصل القرآني الجديد في مركز الغفران');
        $this->flushSession();
        $this->actingAs($teacher)
            ->get(route('access.index'))
            ->assertForbidden();

        $this->flushSession();
        $this->actingAs($manager)
            ->get(route('teacher.daily'))
            ->assertOk()
            ->assertSee($managerHalaqa->name)
            ->assertDontSee($teacherHalaqa->name);
        $managerDaily = Livewire::actingAs($manager)
            ->test(TeacherDailyRecorder::class)
            ->assertSet('teacherProfileId', $manager->teacherProfile->id)
            ->assertSet('halaqaId', (string) $managerHalaqa->id)
            ->assertSee($managerHalaqa->name)
            ->assertDontSee($teacherHalaqa->name);
        foreach ($managerStudents as $student) {
            $managerDaily->assertSee($student->full_name);
        }
        foreach ($teacherStudents as $student) {
            $managerDaily->assertDontSee($student->full_name);
        }

        $this->flushSession();
        $this->actingAs($teacher)
            ->get(route('teacher.daily'))
            ->assertOk()
            ->assertSee($teacherHalaqa->name)
            ->assertDontSee($managerHalaqa->name);
        $teacherDaily = Livewire::actingAs($teacher)
            ->test(TeacherDailyRecorder::class)
            ->assertSet('teacherProfileId', $teacher->teacherProfile->id)
            ->assertSet('halaqaId', (string) $teacherHalaqa->id)
            ->assertSee($teacherHalaqa->name)
            ->assertDontSee($managerHalaqa->name);
        foreach ($teacherStudents as $student) {
            $teacherDaily->assertSee($student->full_name);
        }
        foreach ($managerStudents as $student) {
            $teacherDaily->assertDontSee($student->full_name);
        }

        $preservedContent->refresh();
        $this->assertSame('محتوى عام محفوظ', $preservedContent->title);
        $this->assertNull($preservedContent->created_by);
        $this->assertNull($preservedContent->updated_by);
        $preservedContentId = $preservedContent->id;

        Auth::forgetUser();
        $this->seed(GofranManualTestingSeeder::class);

        $this->assertSame(1, Center::query()->count());
        $this->assertSame(2, Halaqa::query()->count());
        $this->assertSame(2, User::query()->count());
        $this->assertSame(2, TeacherProfile::query()->count());
        $this->assertSame(1, StaffProfile::query()->count());
        $this->assertSame(4, Student::query()->count());
        $this->assertSame(4, Guardian::query()->count());
        $this->assertSame(4, DB::table('guardian_student')->count());
        $this->assertSame(4, DB::table('halaqa_enrollments')->count());
        $this->assertSame(2, DB::table('halaqa_teacher_assignments')->count());
        $this->assertSame(0, DailyRecord::query()->count());
        $this->assertSame(6, CmsContent::query()->where('slug', 'like', 'gofran-%')->count());
        $this->assertSame(4, CmsMedia::query()->where('path', 'like', 'cms/gofran/%')->count());
        $this->assertDatabaseHas('cms_contents', [
            'id' => $preservedContentId,
            'slug' => 'preserved-before-manual-reset',
            'title' => 'محتوى عام محفوظ',
            'created_by' => null,
            'updated_by' => null,
        ]);
    }

    private function activeEnrollmentCount(Halaqa $halaqa): int
    {
        return DB::table('halaqa_enrollments')
            ->where('halaqa_id', $halaqa->id)
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($query) => $query
                ->whereNull('ends_at')
                ->orWhereDate('ends_at', '>=', today()))
            ->count();
    }
}
