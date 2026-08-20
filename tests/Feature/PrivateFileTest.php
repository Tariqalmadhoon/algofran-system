<?php

namespace Tests\Feature;

use App\Models\PrivateFile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_file_requires_permission_and_never_exposes_storage_path(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('private');
        Storage::disk('private')->put('identities/document.pdf', 'private-content');

        $file = PrivateFile::query()->create([
            'disk' => 'private', 'path' => 'identities/document.pdf', 'original_name' => 'هوية.pdf',
            'mime_type' => 'application/pdf', 'size' => 15,
        ]);
        $unauthorized = User::factory()->create();
        $authorized = User::factory()->create();
        $authorized->assignRole('registrar');

        $this->actingAs($unauthorized)->get(route('private-files.show', $file))->assertForbidden();
        $response = $this->actingAs($authorized)->get(route('private-files.show', $file));

        $response->assertOk()->assertHeader('content-disposition');
        $this->assertStringNotContainsString('identities/document.pdf', $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'private-file.downloaded', 'user_id' => $authorized->id]);
    }
}
