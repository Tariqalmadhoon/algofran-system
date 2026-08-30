<?php

namespace App\Services;

use App\Jobs\GenerateReportExport;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionReadinessService
{
    public function __construct(private readonly Migrator $migrator) {}

    /** @return array<int, array{name:string, passed:bool, required:bool, message:string}> */
    public function checks(): array
    {
        return [
            $this->check('environment', config('app.env') === 'production', true, 'Application environment is production.'),
            $this->check('debug_disabled', config('app.debug') === false, true, 'Debug mode is disabled.'),
            $this->check('https_url', $this->applicationUrlUsesHttps(), true, 'Application URL uses HTTPS.'),
            $this->check('app_key', $this->applicationKeyIsValid(), true, 'Application encryption key is configured.'),
            $this->attempt('database', true, 'Database connection is available.', fn () => DB::select('SELECT 1') !== []),
            $this->attempt('migrations', true, 'All application migrations are applied.', fn () => $this->pendingMigrations() === []),
            $this->check('queue', ! in_array(config('queue.default'), ['sync', 'null'], true), true, 'An asynchronous queue connection is configured.'),
            $this->check('queue_retry_window', (int) config('queue.connections.database.retry_after') > (new GenerateReportExport(1))->timeout, true, 'Queue retry window exceeds the longest job timeout.'),
            $this->check('cache', ! in_array(config('cache.default'), ['array', 'null'], true), true, 'A persistent cache store is configured.'),
            $this->check('session', config('session.driver') === 'database', true, 'The database session driver required for device management is configured.'),
            $this->check('secure_cookie', config('session.secure') === true, true, 'Secure session cookies are enabled.'),
            $this->check('mail', ! in_array(config('mail.default'), ['array', 'log'], true), true, 'A delivery mailer is configured.'),
            $this->check('logging', in_array(config('logging.default'), ['daily', 'stack'], true), true, 'A rotating or managed log channel is configured.'),
            $this->check('private_storage', $this->privateStorageIsSafe(), true, 'Private storage is writable and outside the public directory.'),
            $this->check('public_storage_link', $this->publicStorageLinkIsReady(), true, 'Public CMS storage link is configured.'),
            $this->check('api_token_expiration', (int) config('sanctum.expiration') > 0, true, 'API token expiration is enabled.'),
            $this->check('realtime', $this->realtimeIsReady(), true, 'Private Reverb broadcasting is configured.'),
            $this->check('cors', $this->corsIsRestricted(), true, 'CORS is restricted to the application origin.'),
            config('system.identity.two_factor_enabled', false)
                ? $this->attempt('strong_identity', true, 'All sensitive accounts have confirmed two-factor authentication.', fn () => $this->sensitiveAccountsUseTwoFactor())
                : $this->check('strong_identity', true, false, 'Two-factor authentication is temporarily disabled.'),
            $this->attempt('demo_accounts', true, 'Demo accounts are absent.', fn () => ! User::query()->whereIn('email', ['admin@alquran.local', 'teacher@alquran.local'])->exists()),
            $this->check('bootstrap_writable', is_writable(base_path('bootstrap/cache')), true, 'Bootstrap cache directory is writable.'),
            $this->check('scheduler', count(app(Schedule::class)->events()) >= 5, true, 'Scheduled operational tasks are registered.'),
        ];
    }

    /** @param array<int, array{name:string, passed:bool, required:bool, message:string}> $checks */
    public function passed(array $checks): bool
    {
        return collect($checks)->every(fn (array $check) => $check['passed'] || ! $check['required']);
    }

    /** @return array<int, string> */
    private function pendingMigrations(): array
    {
        if (! $this->migrator->repositoryExists()) {
            return ['migration_repository'];
        }

        $files = $this->migrator->getMigrationFiles(database_path('migrations'));

        return array_values(array_diff(array_keys($files), $this->migrator->getRepository()->getRan()));
    }

    private function privateStorageIsSafe(): bool
    {
        $privateRoot = realpath((string) config('filesystems.disks.private.root'));
        $publicRoot = realpath(public_path());

        return $privateRoot !== false
            && $publicRoot !== false
            && is_writable($privateRoot)
            && ! str_starts_with(strtolower($privateRoot), strtolower($publicRoot.DIRECTORY_SEPARATOR));
    }

    private function applicationUrlUsesHttps(): bool
    {
        $url = (string) config('app.url');

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled(parse_url($url, PHP_URL_HOST));
    }

    private function applicationKeyIsValid(): bool
    {
        $key = config('app.key');
        if (! is_string($key) || $key === '') {
            return false;
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        return is_string($key) && Encrypter::supported($key, (string) config('app.cipher'));
    }

    private function publicStorageLinkIsReady(): bool
    {
        $link = public_path('storage');

        return file_exists($link)
            && realpath($link) === realpath(storage_path('app/public'));
    }

    private function realtimeIsReady(): bool
    {
        $origins = config('reverb.apps.apps.0.allowed_origins', []);

        return config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'))
            && filled(config('broadcasting.connections.reverb.secret'))
            && filled(config('broadcasting.connections.reverb.app_id'))
            && filled(config('broadcasting.connections.reverb.options.host'))
            && is_array($origins)
            && $origins !== []
            && ! in_array('*', $origins, true);
    }

    private function sensitiveAccountsUseTwoFactor(): bool
    {
        return ! User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', config('system.identity.two_factor_required_roles', [])))
            ->where(function ($query): void {
                $query->whereNull('two_factor_secret')->orWhereNull('two_factor_confirmed_at');
            })->exists();
    }

    private function corsIsRestricted(): bool
    {
        $origins = config('cors.allowed_origins', []);

        return is_array($origins)
            && in_array(config('app.url'), $origins, true)
            && ! in_array('*', $origins, true);
    }

    /** @return array{name:string, passed:bool, required:bool, message:string} */
    private function attempt(string $name, bool $required, string $message, callable $callback): array
    {
        try {
            return $this->check($name, (bool) $callback(), $required, $message);
        } catch (Throwable) {
            return $this->check($name, false, $required, $message);
        }
    }

    /** @return array{name:string, passed:bool, required:bool, message:string} */
    private function check(string $name, bool $passed, bool $required, string $message): array
    {
        return compact('name', 'passed', 'required', 'message');
    }
}
