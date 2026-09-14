<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AndroidReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileReleaseController extends Controller
{
    public function latest(Request $request, AndroidReleaseService $releases): JsonResponse
    {
        $this->authorizeTeacher($request);
        $validated = $request->validate([
            'platform' => ['nullable', 'in:android'],
            'current_version_code' => ['nullable', 'integer', 'min:1'],
        ]);
        $release = $releases->current();

        if ($release === null) {
            return response()->json([
                'data' => [
                    'platform' => 'android',
                    'release_available' => false,
                    'update_available' => false,
                ],
            ]);
        }

        $currentVersionCode = isset($validated['current_version_code'])
            ? (int) $validated['current_version_code']
            : null;

        return response()->json([
            'data' => [
                'platform' => 'android',
                'release_available' => true,
                'update_available' => $currentVersionCode === null
                    || $currentVersionCode < $release['version_code'],
                'required' => $currentVersionCode !== null
                    && $currentVersionCode < $release['minimum_version_code'],
                'version_name' => $release['version_name'],
                'version_code' => $release['version_code'],
                'minimum_version_code' => $release['minimum_version_code'],
                'download_url' => rtrim((string) config('app.url'), '/').route('api.v1.mobile-releases.android.download', [
                    'versionCode' => $release['version_code'],
                ], absolute: false),
                'size_bytes' => $release['size_bytes'],
                'sha256' => $release['sha256'],
                'release_notes' => $release['release_notes'],
                'published_at' => $release['published_at'],
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function download(Request $request, int $versionCode, AndroidReleaseService $releases): StreamedResponse
    {
        $this->authorizeTeacher($request);
        $release = $releases->current();

        abort_unless($release !== null && $release['version_code'] === $versionCode, 404);

        return $releases->disk()->download(
            $release['path'],
            "gofran-mobile-{$release['version_name']}-{$release['version_code']}.apk",
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'X-Checksum-SHA256' => $release['sha256'],
            ],
        );
    }

    private function authorizeTeacher(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole('super-admin')
                || $request->user()?->teacherProfile()->where('active', true)->exists(),
            403,
            'لا يوجد ملف محفظ فعال لهذا الحساب.',
        );
    }
}
