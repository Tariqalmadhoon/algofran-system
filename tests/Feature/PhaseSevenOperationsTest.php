<?php

namespace Tests\Feature;

use App\Services\ApplicationReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSevenOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_reports_only_the_operational_state(): void
    {
        $this->getJson(route('system.ready'))
            ->assertOk()
            ->assertExactJson(['status' => 'ready'])
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->mock(ApplicationReadinessService::class)
            ->shouldReceive('isReady')
            ->once()
            ->andReturnFalse();

        $this->getJson(route('system.ready'))
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'not_ready']);
    }

    public function test_production_check_rejects_an_unsafe_environment_without_exposing_secrets(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'database.connections.sqlite.password' => 'Phase7HiddenSecret!',
        ]);

        $exitCode = Artisan::call('system:production-check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertSame('not_ready', json_decode($output, true, flags: JSON_THROW_ON_ERROR)['status']);
        $this->assertStringNotContainsString('Phase7HiddenSecret!', $output);
        $this->assertStringNotContainsString((string) config('app.key'), $output);
    }

    public function test_production_check_accepts_a_hardened_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://alquran.example',
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'production-key',
            'broadcasting.connections.reverb.secret' => 'production-secret',
            'broadcasting.connections.reverb.app_id' => 'alquran-production',
            'broadcasting.connections.reverb.options.host' => 'realtime.alquran.example',
            'cache.default' => 'database',
            'cors.allowed_origins' => ['https://alquran.example'],
            'logging.default' => 'daily',
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 960,
            'sanctum.expiration' => 43200,
            'session.driver' => 'database',
            'session.secure' => true,
            'reverb.apps.apps.0.allowed_origins' => ['alquran.example'],
        ]);

        $exitCode = Artisan::call('system:production-check', ['--json' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode, json_encode($result, JSON_PRETTY_PRINT));
        $this->assertSame('ready', $result['status']);
        $this->assertNotEmpty($result['checks']);
    }

    public function test_backup_manifest_detects_artifact_tampering(): void
    {
        $directory = storage_path('framework/testing/phase-seven-'.Str::uuid());
        $databaseDump = $directory.DIRECTORY_SEPARATOR.'database.sql';
        $privateArchive = $directory.DIRECTORY_SEPARATOR.'private-storage.tar';
        $manifest = $directory.DIRECTORY_SEPARATOR.'manifest.json';

        File::ensureDirectoryExists($directory);

        try {
            File::put($databaseDump, 'verified database backup');
            File::put($privateArchive, 'verified private storage backup');

            $this->assertSame(0, Artisan::call('system:backup-manifest', [
                'artifacts' => [$databaseDump, $privateArchive],
                '--output' => $manifest,
            ]));
            $this->assertFileExists($manifest);

            $this->assertSame(0, Artisan::call('system:backup-verify', [
                'manifest' => $manifest,
                '--directory' => $directory,
            ]));

            File::append($databaseDump, 'tampered');

            $this->assertSame(1, Artisan::call('system:backup-verify', [
                'manifest' => $manifest,
                '--directory' => $directory,
            ]));
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
