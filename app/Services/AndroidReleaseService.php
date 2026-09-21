<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AndroidReleaseService
{
    public function __construct(private readonly SettingsService $settings) {}

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
        $configuration = $this->configuration();

        if (! $configuration['release_enabled']) {
            return null;
        }

        try {
            return $this->verifiedRelease($configuration);
        } catch (Throwable $exception) {
            report($exception);

            // A missing or partially uploaded release must not take down the site.
            return null;
        }
    }

    /**
     * Validate a candidate package before it becomes visible to teachers.
     *
     * @param  array{release_enabled: bool, version: string, version_code: int, minimum_version_code: int, android_apk_path: string, api_base_url: string, release_notes: string|null, published_at: string|null}  $configuration
     */
    public function verifyCandidate(array $configuration): ?array
    {
        return $configuration['release_enabled'] ? $this->verifiedRelease($configuration) : null;
    }

    /**
     * @param  array{release_enabled: bool, version: string, version_code: int, minimum_version_code: int, android_apk_path: string, api_base_url: string, release_notes: string|null, published_at: string|null}  $configuration
     */
    private function verifiedRelease(array $configuration): ?array
    {
        $path = trim($configuration['android_apk_path']);
        $disk = $this->disk();

        if (! preg_match('/\Areleases\/gofran-mobile-\d+\.\d+\.\d+-[1-9]\d*\.apk\z/', $path)
            || ! $disk->exists($path) || ! $disk->exists($path.'.sha256')
            || ! $disk->exists($path.'.json')) {
            return null;
        }

        $manifest = json_decode((string) $disk->get($path.'.json'), true, flags: JSON_THROW_ON_ERROR);
        $versionCode = $configuration['version_code'];
        $versionName = $configuration['version'];
        $appUrl = rtrim((string) config('app.url'), '/');
        $apiBaseUrl = rtrim($configuration['api_base_url'], '/');
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

        $minimumVersionCode = min($versionCode, max(1, $configuration['minimum_version_code']));

        return [
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'minimum_version_code' => $minimumVersionCode,
            'path' => $path,
            'size_bytes' => $size,
            'sha256' => $actualChecksum,
            'release_notes' => $configuration['release_notes'],
            'published_at' => $configuration['published_at'],
        ];
    }

    /** @return array{release_enabled: bool, version: string, version_code: int, minimum_version_code: int, android_apk_path: string, api_base_url: string, release_notes: string|null, published_at: string|null} */
    public function configuration(): array
    {
        $apiBaseUrl = $this->setting('api_base_url', config('system.mobile_app.api_base_url'));
        $apiBaseUrl = trim((string) $apiBaseUrl);
        if ($apiBaseUrl === '') {
            $apiBaseUrl = rtrim((string) config('app.url'), '/').'/api/v1';
        }

        return [
            'release_enabled' => (bool) $this->setting('release_enabled', config('system.mobile_app.release_enabled'), 'boolean'),
            'version' => trim((string) $this->setting('version', config('system.mobile_app.version'))),
            'version_code' => (int) $this->setting('version_code', config('system.mobile_app.version_code'), 'integer'),
            'minimum_version_code' => (int) $this->setting('minimum_version_code', config('system.mobile_app.minimum_version_code'), 'integer'),
            'android_apk_path' => trim((string) $this->setting('android_apk_path', config('system.mobile_app.android_apk_path'))),
            'api_base_url' => $apiBaseUrl,
            'release_notes' => $this->nullable($this->setting('release_notes', config('system.mobile_app.release_notes'))),
            'published_at' => $this->nullable($this->setting('published_at', config('system.mobile_app.published_at'))),
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

    private function setting(string $key, mixed $default, string $type = 'string'): mixed
    {
        return $this->settings->get($key, $default, 'mobile_app');
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
