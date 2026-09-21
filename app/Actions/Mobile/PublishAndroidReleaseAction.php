<?php

namespace App\Actions\Mobile;

use App\Models\User;
use App\Services\AndroidReleaseService;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublishAndroidReleaseAction
{
    public function __construct(
        private readonly AndroidReleaseService $releases,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{version: string, version_code: int, minimum_version_code: int, release_notes: string|null}  $data
     */
    public function execute(User $actor, array $data, UploadedFile $apk, UploadedFile $checksum, UploadedFile $manifest): array
    {
        $current = $this->releases->current();
        if ($current !== null && $data['version_code'] <= $current['version_code']) {
            throw ValidationException::withMessages(['versionCode' => 'رقم البناء يجب أن يكون أكبر من الإصدار المنشور حاليًا.']);
        }

        $disk = Storage::disk('private');
        $version = $data['version'];
        $versionCode = $data['version_code'];
        $finalPath = "releases/gofran-mobile-{$version}-{$versionCode}.apk";
        $staging = 'releases/.staging/'.Str::uuid();

        if ($disk->exists($finalPath) || $disk->exists($finalPath.'.sha256') || $disk->exists($finalPath.'.json')) {
            throw ValidationException::withMessages(['versionCode' => 'توجد ملفات لهذا الإصدار بالفعل. اختر رقم بناء جديدًا.']);
        }

        $storedPaths = [];
        try {
            $storedPaths = [
                $apk->storeAs($staging, 'release.apk', 'private'),
                $checksum->storeAs($staging, 'release.apk.sha256', 'private'),
                $manifest->storeAs($staging, 'release.apk.json', 'private'),
            ];

            $disk->move($storedPaths[0], $finalPath);
            $disk->move($storedPaths[1], $finalPath.'.sha256');
            $disk->move($storedPaths[2], $finalPath.'.json');

            $configuration = [
                'release_enabled' => true,
                'version' => $version,
                'version_code' => $versionCode,
                'minimum_version_code' => min($versionCode, max(1, $data['minimum_version_code'])),
                'android_apk_path' => $finalPath,
                'api_base_url' => $this->releases->configuration()['api_base_url'],
                'release_notes' => $data['release_notes'],
                'published_at' => now()->toIso8601String(),
            ];
            $release = $this->releases->verifyCandidate($configuration);
            if ($release === null) {
                throw ValidationException::withMessages(['apk' => 'لم تجتز الحزمة التحقق: تأكد من APK الموقّع وملفي SHA-256 وJSON المطابقين له.']);
            }

            DB::transaction(function () use ($configuration, $release, $actor): void {
                $this->settings->set('release_enabled', true, 'boolean', 'mobile_app');
                $this->settings->set('version', $configuration['version'], 'string', 'mobile_app');
                $this->settings->set('version_code', $configuration['version_code'], 'integer', 'mobile_app');
                $this->settings->set('minimum_version_code', $configuration['minimum_version_code'], 'integer', 'mobile_app');
                $this->settings->set('android_apk_path', $configuration['android_apk_path'], 'string', 'mobile_app');
                $this->settings->set('api_base_url', $configuration['api_base_url'], 'string', 'mobile_app');
                $this->settings->set('release_notes', $configuration['release_notes'] ?? '', 'string', 'mobile_app');
                $this->settings->set('published_at', $configuration['published_at'], 'string', 'mobile_app');
                $this->audit->record('mobile.release_published', null, [], [
                    'version' => $release['version_name'],
                    'version_code' => $release['version_code'],
                    'minimum_version_code' => $release['minimum_version_code'],
                    'size_bytes' => $release['size_bytes'],
                    'sha256' => $release['sha256'],
                ], $actor);
            });

            return $release;
        } catch (Throwable $exception) {
            $disk->delete([$finalPath, $finalPath.'.sha256', $finalPath.'.json']);

            throw $exception;
        } finally {
            $disk->deleteDirectory($staging);
        }
    }
}
