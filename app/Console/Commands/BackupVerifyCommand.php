<?php

namespace App\Console\Commands;

use App\Services\BackupIntegrityService;
use Illuminate\Console\Command;
use Throwable;

class BackupVerifyCommand extends Command
{
    protected $signature = 'system:backup-verify
                            {manifest : Backup manifest JSON file}
                            {--directory= : Directory containing the backup artifacts}';

    protected $description = 'Verify backup artifact sizes and SHA-256 checksums before restoration';

    public function handle(BackupIntegrityService $integrity): int
    {
        $manifest = $this->resolvePath((string) $this->argument('manifest'));
        $directory = $this->option('directory')
            ? $this->resolvePath((string) $this->option('directory'))
            : dirname($manifest);

        try {
            $result = $integrity->verifyManifest($manifest, $directory);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result['checks'] as $check) {
            $this->line('['.($check['valid'] ? 'PASS' : 'FAIL').'] '.$check['name']);
        }

        if (! $result['valid']) {
            $this->error('Backup verification failed. Do not restore these artifacts.');

            return self::FAILURE;
        }

        $this->info('Backup verification passed.');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\]{2}|\/)/', $path) === 1 ? $path : base_path($path);
    }
}
