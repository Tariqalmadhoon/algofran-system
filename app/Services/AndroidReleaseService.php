<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AndroidReleaseService
{
    /**
     * @return array{
     *     version_name: string,
     *     version_code: int,
     *     minimum_version_code: int,
     *     path: string,
     *     size_bytes: int,
     *     sha256: string,
     *     release_notes: string|null,
     *     published_at: string|null
     * }|null
     */
    public function current(): ?array
    {
        if (! config('system.mobile_app.release_enabled', false)) {
            return null;
        }

        try {
            return $this->verifiedRelease();
        } catch (Throwable $exception) {
            report($exception);

            // A missing or partially uploaded release must not take down the site.
            return null;
        }
    }

    private function verifiedRelease(): ?array
    {
        $path = trim((string) config('system.mobile_app.android_apk_path'));
        $disk = $this->disk();

        if (! preg_match('/\Areleases\/gofran-mobile-\d+\.\d+\.\d+-[1-9]\d*\.apk\z/', $path)
            || ! $disk->exists($path) || ! $disk->exists($path.'.sha256')
            || ! $disk->exists($path.'.json')) {
            return null;
        }

        $manifest = json_decode((string) $disk->get($path.'.json'), true, flags: JSON_THROW_ON_ERROR);
        $versionCode = (int) config('system.mobile_app.version_code');
        $versionName = (string) config('system.mobile_app.version');
        $appUrl = rtrim((string) config('app.url'), '/');
        $apiBaseUrl = rtrim((string) config('system.mobile_app.api_base_url'), '/');
        $apiBaseUrl = $apiBaseUrl !== '' ? $apiBaseUrl : $appUrl.'/api/v1';
        if (! is_array($manifest)
            || ($manifest['application_id'] ?? null) !== 'com.gofran.gofran_mobile'
            || ($manifest['version_code'] ?? null) !== $versionCode
            || ($manifest['version_name'] ?? null) !== $versionName
            || ($manifest['debuggable'] ?? null) !== false
            || ($manifest['api_base_url'] ?? null) !== $apiBaseUrl
            || parse_url($apiBaseUrl, PHP_URL_SCHEME) !== 'https'
            || ! preg_match('/\A[a-f0-9]{64}\z/', $manifest['signing_certificate_sha256'] ?? '')
            || $path !== "releases/gofran-mobile-{$versionName}-{$versionCode}.apk") {
            return null;
        }

        $expectedChecksum = strtolower(trim((string) $disk->get($path.'.sha256')));
        if (! preg_match('/\A[a-f0-9]{64}\z/', $expectedChecksum)
            || ($manifest['sha256'] ?? null) !== $expectedChecksum) {
            return null;
        }

        $size = $disk->size($path);
        if ($size < 1 || $size > 536870912 || ($manifest['size_bytes'] ?? null) !== $size) {
            return null;
        }
        $actualChecksum = $this->checksum($disk, $path);

        if (! hash_equals($expectedChecksum, $actualChecksum)) {
            return null;
        }

        $minimumVersionCode = min(
            $versionCode,
            max(1, (int) config('system.mobile_app.minimum_version_code', 1)),
        );

        return [
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'minimum_version_code' => $minimumVersionCode,
            'path' => $path,
            'size_bytes' => $size,
            'sha256' => $actualChecksum,
            'release_notes' => $this->nullableConfig('system.mobile_app.release_notes'),
            'published_at' => $this->nullableConfig('system.mobile_app.published_at'),
        ];
    }

    public function disk(): Filesystem
    {
        return Storage::disk('private');
    }

    private function checksum(Filesystem $disk, string $path): string
    {
        $stream = $disk->readStream($path);

        if ($stream === false) {
            return '';
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    private function nullableConfig(string $key): ?string
    {
        $value = trim((string) config($key));

        return $value === '' ? null : $value;
    }
}
