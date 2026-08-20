<?php

namespace App\Console\Commands;

use App\Services\BackupIntegrityService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class BackupManifestCommand extends Command
{
    protected $signature = 'system:backup-manifest
                            {artifacts* : Database dump and storage archive files}
                            {--output= : New manifest path; defaults to private storage}';

    protected $description = 'Create a SHA-256 integrity manifest for completed backup artifacts';

    public function handle(BackupIntegrityService $integrity): int
    {
        $artifacts = array_map($this->resolvePath(...), $this->argument('artifacts'));
        $output = $this->option('output')
            ? $this->resolvePath((string) $this->option('output'))
            : storage_path('app/backups/manifests/backup-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json');

        try {
            $manifest = $integrity->createManifest($artifacts, $output);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup manifest created successfully.');
        $this->line('Artifacts: '.count($manifest['artifacts']));
        $this->line('Manifest: '.$output);

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\]{2}|\/)/', $path) === 1 ? $path : base_path($path);
    }
}
