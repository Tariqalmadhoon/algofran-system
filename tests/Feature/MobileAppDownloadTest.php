<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_homepage_never_shows_the_private_android_download(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('تحميل التطبيق');

        $this->publishFixture();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('تحميل التطبيق')
            ->assertDontSee('/api/v1/mobile/releases/android/', false);
    }

    public function test_mobile_release_api_publishes_a_verified_versioned_apk(): void
    {
        $checksum = $this->publishFixture();

        $this->getJson('/api/v1/mobile/releases/latest?platform=android&current_version_code=1')
            ->assertUnauthorized();

        $this->asTeacher();

        $response = $this->getJson('/api/v1/mobile/releases/latest?platform=android&current_version_code=1')
            ->assertOk()
            ->assertJsonPath('data.release_available', true)
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.version_name', '1.3.0')
            ->assertJsonPath('data.version_code', 4)
            ->assertJsonPath('data.download_url', 'https://algofran-center.tech/api/v1/mobile/releases/android/4/download')
            ->assertJsonPath('data.sha256', $checksum)
            ->assertJsonPath('data.release_notes', 'تحسين المزامنة والاستقرار.');

        $downloadUrl = $response->json('data.download_url');
        $this->get($downloadUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('X-Checksum-SHA256', $checksum)
            ->assertDownload('gofran-mobile-1.3.0-4.apk');

        $this->getJson('/api/v1/mobile/releases/latest?current_version_code=4')
            ->assertOk()
            ->assertJsonPath('data.update_available', false)
            ->assertJsonPath('data.required', false);
    }

    public function test_mobile_release_api_hides_missing_or_unverified_artifacts(): void
    {
        $this->publishFixture();
        $this->asTeacher();
        $path = config('system.mobile_app.android_apk_path');
        Storage::disk('private')->delete($path.'.json');

        $this->getJson('/api/v1/mobile/releases/latest')
            ->assertOk()
            ->assertJsonPath('data.release_available', false)
            ->assertJsonPath('data.update_available', false);

        $this->publishFixture();
        Storage::disk('private')->put($path, 'tampered-release!!');

        $this->getJson('/api/v1/mobile/releases/latest')
            ->assertOk()
            ->assertJsonPath('data.release_available', false);
        $this->get('/api/v1/mobile/releases/android/4/download')->assertNotFound();
    }

    public function test_release_gate_rejects_debug_wrong_domain_and_mismatched_metadata(): void
    {
        $this->asTeacher();
        foreach ([
            ['debuggable' => true],
            ['application_id' => 'com.other.app'],
            ['api_base_url' => 'http://127.0.0.1:8000/api/v1'],
            ['api_base_url' => 'https://another.example/api/v1'],
            ['version_code' => 5],
            ['size_bytes' => 1],
            ['sha256' => str_repeat('b', 64)],
            ['signing_certificate_sha256' => 'invalid'],
        ] as $invalidMetadata) {
            $this->publishFixture($invalidMetadata);
            $this->getJson('/api/v1/mobile/releases/latest')
                ->assertOk()->assertJsonPath('data.release_available', false);
            $this->get('/api/v1/mobile/releases/android/4/download')->assertNotFound();
        }
    }

    public function test_disabled_release_and_stale_download_are_not_available(): void
    {
        $this->publishFixture();
        $this->asTeacher();
        $this->get('/api/v1/mobile/releases/android/3/download')->assertNotFound();
        config(['system.mobile_app.release_enabled' => false]);
        $this->getJson('/api/v1/mobile/releases/latest')->assertJsonPath('data.release_available', false);
        $this->get('/api/v1/mobile/releases/android/4/download')->assertNotFound();
    }

    private function publishFixture(array $overrides = []): string
    {
        Storage::fake('private');
        $path = 'releases/gofran-mobile-1.3.0-4.apk';
        $contents = 'signed-release-apk';
        $checksum = hash('sha256', $contents);
        config([
            'app.url' => 'https://algofran-center.tech',
            'system.mobile_app.version' => '1.3.0',
            'system.mobile_app.version_code' => 4,
            'system.mobile_app.release_enabled' => true,
            'system.mobile_app.minimum_version_code' => 2,
            'system.mobile_app.android_apk_path' => $path,
            'system.mobile_app.release_notes' => 'تحسين المزامنة والاستقرار.',
        ]);
        Storage::disk('private')->put($path, $contents);
        Storage::disk('private')->put($path.'.sha256', $checksum);
        Storage::disk('private')->put($path.'.json', json_encode(array_replace([
            'application_id' => 'com.gofran.gofran_mobile',
            'version_name' => '1.3.0',
            'version_code' => 4,
            'debuggable' => false,
            'api_base_url' => 'https://algofran-center.tech/api/v1',
            'signing_certificate_sha256' => str_repeat('c', 64),
            'size_bytes' => strlen($contents),
            'sha256' => $checksum,
        ], $overrides), JSON_THROW_ON_ERROR));

        return $checksum;
    }

    private function asTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $center = Center::query()->create(['name' => 'مركز اختبار التطبيق', 'code' => 'MOBILE-TEST']);
        TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'center_id' => $center->id,
            'employee_number' => 'MOBILE-TEACHER-'.$teacher->id,
            'active' => true,
        ]);

        Sanctum::actingAs($teacher, ['mobile:read']);
    }
}
