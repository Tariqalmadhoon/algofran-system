<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApplicationReadinessService;
use App\Services\ProductionReadinessService;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSevenOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_null_broadcast_environment_resolves_a_real_null_driver(): void
    {
        $environment = Env::getRepository();
        $previous = $environment->get('BROADCAST_CONNECTION');
        try {
            $environment->set('BROADCAST_CONNECTION', 'null');
            $broadcasting = require config_path('broadcasting.php');
            $this->assertSame('null', $broadcasting['default']);
            config(['broadcasting.default' => $broadcasting['default']]);
            $this->assertInstanceOf(
                NullBroadcaster::class,
                app(Factory::class)->connection(),
            );
        } finally {
            $previous === null ? $environment->clear('BROADCAST_CONNECTION') : $environment->set('BROADCAST_CONNECTION', $previous);
        }
    }

    public function test_preflight_blocks_known_demo_password_but_allows_the_account_after_password_change(): void
    {
        $user = User::factory()->create(['email' => 'admin1@gofran.com', 'password' => '123456789']);
        $checks = collect(app(ProductionReadinessService::class)->checks('shared'))->keyBy('name');
        $this->assertFalse($checks['demo_accounts']['passed']);
        $user->update(['password' => 'StrongProductionTestPassword!']);
        $checks = collect(app(ProductionReadinessService::class)->checks('shared'))->keyBy('name');
        $this->assertTrue($checks['demo_accounts']['passed']);
    }

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

    public function test_shared_profile_requires_polling_and_keeps_security_checks_required(): void
    {
        config(['broadcasting.default' => 'null']);

        $checks = collect(app(ProductionReadinessService::class)->checks('shared'))->keyBy('name');
        $this->assertTrue($checks['realtime']['passed']);
        foreach (['environment', 'debug_disabled', 'https_url', 'app_key', 'database', 'migrations', 'private_storage', 'secure_cookie'] as $name) {
            $this->assertTrue($checks[$name]['required']);
        }

        config(['broadcasting.default' => 'reverb']);
        $checks = collect(app(ProductionReadinessService::class)->checks('shared'))->keyBy('name');
        $this->assertFalse($checks['realtime']['passed']);
        $this->assertSame(2, Artisan::call('system:production-check', ['--profile' => 'unknown']));
    }

    public function test_preflight_uses_the_actual_shared_document_root_and_rejects_exposed_secrets(): void
    {
        $directory = storage_path('framework/testing/shared-public-'.Str::uuid());
        File::ensureDirectoryExists($directory);

        try {
            $readiness = app(ProductionReadinessService::class);
            $checks = collect($readiness->checks('shared', $directory))->keyBy('name');
            $this->assertTrue($checks['document_root']['passed']);
            $this->assertFalse($checks['public_storage_link']['passed']);

            File::put($directory.'/.env', 'hidden');
            $checks = collect($readiness->checks('shared', $directory))->keyBy('name');
            $this->assertFalse($checks['document_root']['passed']);

            config(['filesystems.disks.private.root' => $directory]);
            $checks = collect($readiness->checks('shared', $directory))->keyBy('name');
            $this->assertFalse($checks['private_storage']['passed']);

            $checks = collect($readiness->checks('shared', base_path()))->keyBy('name');
            $this->assertFalse($checks['document_root']['passed']);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
