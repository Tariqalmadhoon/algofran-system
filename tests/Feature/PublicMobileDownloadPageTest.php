<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AndroidReleaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMobileDownloadPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_app_download_page_is_not_available(): void
    {
        $this->get('/app')->assertNotFound();
    }

    public function test_active_teacher_sees_the_private_download_page_and_approved_release_only(): void
    {
        $teacher = $this->teacher();
        $this->mock(AndroidReleaseService::class)->shouldReceive('current')->once()->andReturn([
            'version_name' => '1.3.0',
            'version_code' => 4,
            'minimum_version_code' => 1,
            'path' => 'releases/gofran-mobile-1.3.0-4.apk',
            'size_bytes' => 10485760,
            'sha256' => str_repeat('a', 64),
            'release_notes' => '<script>alert(1)</script>',
            'published_at' => null,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.mobile.app'))
            ->assertOk()
            ->assertSee('مساحة المحفّظ الخاصة')
            ->assertSee(route('teacher.mobile.app.download', ['versionCode' => 4]), false)
            ->assertSee('1.3.0')
            ->assertSee('10.0 MB')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('releases/gofran-mobile-1.3.0-4.apk', false);
    }

    public function test_teacher_role_without_an_active_teacher_profile_cannot_download_the_app(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user)
            ->get(route('teacher.mobile.app'))
            ->assertForbidden();
    }

    private function teacher(): User
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $center = Center::query()->create(['name' => 'مركز الاختبار', 'code' => 'TEST-CENTER']);
        TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'center_id' => $center->id,
            'employee_number' => 'TEST-TEACHER-'.$teacher->id,
            'active' => true,
        ]);

        return $teacher;
    }
}
