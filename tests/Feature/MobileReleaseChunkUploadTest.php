<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileReleaseChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_administrator_can_stage_an_apk_in_small_chunks(): void
    {
        Storage::fake('private');
        $administrator = User::factory()->create();
        $administrator->assignRole('super-admin');
        $uploadId = 'be4ff52d-ecb4-4f2c-b309-4ae783aad3cd';

        $this->actingAs($administrator)
            ->post(route('mobile.distribution.apk-chunks'), [
                'upload_id' => $uploadId,
                'index' => 0,
                'total' => 2,
                'total_size' => 10,
                'chunk' => UploadedFile::fake()->createWithContent('release.apk', '12345'),
            ])
            ->assertOk()
            ->assertJsonPath('complete', false)
            ->assertJsonPath('upload_token', null);

        $this->actingAs($administrator)
            ->post(route('mobile.distribution.apk-chunks'), [
                'upload_id' => $uploadId,
                'index' => 1,
                'total' => 2,
                'total_size' => 10,
                'chunk' => UploadedFile::fake()->createWithContent('release.apk', '67890'),
            ])
            ->assertOk()
            ->assertJsonPath('complete', true)
            ->assertJsonPath('upload_token', $uploadId);

        Storage::disk('private')->assertExists("releases/.uploads/{$administrator->id}/{$uploadId}/release.apk");
        $this->assertSame('1234567890', Storage::disk('private')->get("releases/.uploads/{$administrator->id}/{$uploadId}/release.apk"));
    }

    public function test_non_administrator_cannot_stage_an_apk(): void
    {
        Storage::fake('private');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->post(route('mobile.distribution.apk-chunks'), [
                'upload_id' => 'be4ff52d-ecb4-4f2c-b309-4ae783aad3cd',
                'index' => 0,
                'total' => 1,
                'total_size' => 5,
                'chunk' => UploadedFile::fake()->createWithContent('release.apk', '12345'),
            ])
            ->assertForbidden();
    }
}
