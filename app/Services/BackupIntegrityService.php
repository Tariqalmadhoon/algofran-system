<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class BackupIntegrityService
{
    /** @return array{version:int,algorithm:string,created_at:string,artifacts:array<int,array{name:string,size:int,sha256:string}>} */
    public function createManifest(array $artifactPaths, string $manifestPath): array
    {
        if ($artifactPaths === []) {
            throw new InvalidArgumentException('At least one backup artifact is required.');
        }

        $artifacts = [];
        $names = [];
        foreach ($artifactPaths as $artifactPath) {
            $realPath = realpath($artifactPath);
            if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
                throw new InvalidArgumentException('A backup artifact is missing or unreadable.');
            }

            $name = basename($realPath);
            if (isset($names[$name])) {
                throw new InvalidArgumentException('Backup artifact names must be unique.');
            }

            $size = filesize($realPath);
            $hash = hash_file('sha256', $realPath);
            if ($size === false || $size < 1 || $hash === false) {
                throw new InvalidArgumentException('A backup artifact is empty or could not be hashed.');
            }

            $names[$name] = true;
            $artifacts[] = ['name' => $name, 'size' => $size, 'sha256' => $hash];
        }

        if (file_exists($manifestPath)) {
            throw new InvalidArgumentException('The requested manifest already exists.');
        }

        File::ensureDirectoryExists(dirname($manifestPath));
        $manifest = [
            'version' => 1,
            'algorithm' => 'sha256',
            'created_at' => now()->toIso8601String(),
            'artifacts' => $artifacts,
        ];
        $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if (File::put($manifestPath, $json, true) === false) {
            throw new RuntimeException('The backup manifest could not be written.');
        }

        return $manifest;
    }

    /** @return array{valid:bool,checks:array<int,array{name:string,valid:bool}>} */
    public function verifyManifest(string $manifestPath, string $artifactDirectory): array
    {
        $manifestRealPath = realpath($manifestPath);
        $directoryRealPath = realpath($artifactDirectory);
        if ($manifestRealPath === false || ! is_file($manifestRealPath) || ! is_readable($manifestRealPath)) {
            throw new InvalidArgumentException('The backup manifest is missing or unreadable.');
        }
        if ($directoryRealPath === false || ! is_dir($directoryRealPath)) {
            throw new InvalidArgumentException('The backup artifact directory is missing.');
        }

        try {
            $manifest = json_decode((string) File::get($manifestRealPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('The backup manifest is not valid JSON.');
        }

        if (($manifest['version'] ?? null) !== 1 || ($manifest['algorithm'] ?? null) !== 'sha256' || empty($manifest['artifacts']) || ! is_array($manifest['artifacts'])) {
            throw new InvalidArgumentException('The backup manifest format is unsupported.');
        }

        $checks = [];
        foreach ($manifest['artifacts'] as $artifact) {
            $name = $artifact['name'] ?? null;
            if (! is_string($name) || basename($name) !== $name) {
                throw new InvalidArgumentException('The backup manifest contains an unsafe artifact name.');
            }

            $path = $directoryRealPath.DIRECTORY_SEPARATOR.$name;
            $expectedSize = filter_var($artifact['size'] ?? null, FILTER_VALIDATE_INT);
            $expectedHash = $artifact['sha256'] ?? null;
            $valid = is_file($path)
                && $expectedSize !== false
                && $expectedSize > 0
                && is_string($expectedHash)
                && preg_match('/^[a-f0-9]{64}$/', $expectedHash) === 1
                && filesize($path) === $expectedSize
                && hash_equals($expectedHash, (string) hash_file('sha256', $path));
            $checks[] = ['name' => $name, 'valid' => $valid];
        }

        return [
            'valid' => collect($checks)->every(fn (array $check) => $check['valid']),
            'checks' => $checks,
        ];
    }
}
