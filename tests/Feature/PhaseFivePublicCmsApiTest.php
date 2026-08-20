<?php

namespace Tests\Feature;

use App\Livewire\CmsManager;
use App\Models\Branch;
use App\Models\Center;
use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFivePublicCmsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_website_only_displays_published_content_with_seo_foundation(): void
    {
        CmsContent::query()->create(['type' => 'news', 'slug' => 'published-news', 'title' => 'خبر منشور', 'excerpt' => 'ملخص الخبر', 'body' => 'تفاصيل الخبر', 'status' => 'published', 'published_at' => now()->subMinute(), 'meta_title' => 'عنوان محرك البحث', 'meta_description' => 'وصف محرك البحث']);
        CmsContent::query()->create(['type' => 'news', 'slug' => 'draft-news', 'title' => 'خبر مسودة', 'status' => 'draft']);

        $this->get(route('public.home'))->assertOk()->assertSee('نبني جيلًا')->assertSee('canonical');
        $this->get(route('news.index'))->assertOk()->assertSee('خبر منشور')->assertDontSee('خبر مسودة');
        $this->get('/news/published-news')->assertOk()->assertSee('عنوان محرك البحث')->assertSee('وصف محرك البحث');
        $this->get('/news/draft-news')->assertNotFound();
    }

    public function test_website_editor_can_publish_content_and_upload_gallery_media(): void
    {
        Storage::fake('public');
        $editor = User::factory()->create();
        $editor->assignRole('website-editor');

        Livewire::actingAs($editor)->test(CmsManager::class)
            ->call('newContent')
            ->set('contentType', 'activity')
            ->set('contentTitle', 'النشاط القرآني')
            ->set('contentSlug', 'quran-activity')
            ->set('contentExcerpt', 'نشاط تربوي تفاعلي')
            ->set('contentBody', 'تفاصيل النشاط')
            ->set('contentStatus', 'published')
            ->call('saveContent')
            ->assertHasNoErrors()
            ->set('tab', 'media')
            ->set('mediaFile', UploadedFile::fake()->image('gallery.jpg', 800, 600))
            ->set('mediaTitle', 'صورة النشاط')
            ->set('mediaAlt', 'طلاب في النشاط')
            ->set('mediaGallery', true)
            ->call('uploadMedia')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cms_contents', ['slug' => 'quran-activity', 'status' => 'published', 'created_by' => $editor->id]);
        $media = CmsMedia::query()->firstOrFail();
        Storage::disk('public')->assertExists($media->path);
        $this->get(route('activities.index'))->assertSee('النشاط القرآني');
    }

    public function test_contact_form_stores_message_for_cms_inbox(): void
    {
        $this->post(route('public.contact.store'), ['name' => 'زائر الموقع', 'phone' => '0599000000', 'email' => 'visitor@example.com', 'subject' => 'استفسار عن التسجيل', 'message' => 'أرغب بمعرفة البرامج المتاحة.', 'website' => ''])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['phone' => '0599000000', 'status' => 'new']);
    }

    public function test_sanctum_api_authenticates_and_scopes_mobile_data(): void
    {
        [$teacherUser, $ownHalaqa, $otherHalaqa] = $this->organization();
        $own = $this->student($ownHalaqa, 'OWN-001', 'الطالب المسموح');
        $this->student($otherHalaqa, 'OTHER-001', 'الطالب الآخر');

        $this->getJson('/api/v1/students')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $login = $this->postJson('/api/v1/auth/login', ['email' => $teacherUser->email, 'password' => 'TeacherPass123!', 'device_name' => 'اختبار التطبيق'])
            ->assertOk()->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        Sanctum::actingAs($teacherUser, ['mobile:read']);
        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('data.id', $teacherUser->id);
        $this->getJson('/api/v1/students?per_page=10')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)->assertJsonStructure(['data', 'links', 'meta']);
        $this->getJson('/api/v1/halaqas')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/students/'.$own->id.'/progress')->assertOk();
        $this->getJson('/api/v1/daily-records')->assertOk();
        $this->getJson('/api/v1/recitations')->assertOk();
        $this->getJson('/api/v1/attendance')->assertOk();
        $this->getJson('/api/v1/calendar')->assertOk();
        $this->getJson('/api/v1/alerts')->assertOk();
        $this->getJson('/api/v1/courses')->assertOk();
        $this->getJson('/api/v1/certificates')->assertOk();
        $this->getJson('/api/v1/students/'.Student::query()->where('student_number', 'OTHER-001')->value('id'))->assertForbidden();
    }

    /** @return array{User, Halaqa, Halaqa} */
    private function organization(): array
    {
        $center = Center::query()->create(['name' => 'المركز', 'code' => 'MAIN']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'الفرع', 'code' => 'B1']);
        $teacherUser = User::factory()->create(['password' => Hash::make('TeacherPass123!')]);
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create(['user_id' => $teacherUser->id, 'center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-001', 'active' => true]);
        $own = Halaqa::query()->create(['center_id' => $center->id, 'branch_id' => $branch->id, 'primary_teacher_id' => $teacher->id, 'name' => 'حلقة المحفّظ', 'code' => 'H1', 'capacity' => 20, 'active' => true]);
        $own->teacherAssignments()->create(['teacher_profile_id' => $teacher->id, 'role' => 'primary', 'starts_at' => today()->subMonth(), 'assigned_by' => $teacherUser->id]);
        $other = Halaqa::query()->create(['center_id' => $center->id, 'branch_id' => $branch->id, 'name' => 'حلقة أخرى', 'code' => 'H2', 'capacity' => 20, 'active' => true]);

        return [$teacherUser, $own, $other];
    }

    private function student(Halaqa $halaqa, string $number, string $name): Student
    {
        return Student::query()->create(['student_number' => $number, 'first_name' => $name, 'father_name' => 'اختبار', 'grandfather_name' => 'نظام', 'family_name' => $number, 'full_name' => $name, 'registration_date' => today(), 'status' => 'active', 'current_halaqa_id' => $halaqa->id]);
    }
}
