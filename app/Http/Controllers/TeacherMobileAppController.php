<?php

namespace App\Http\Controllers;

use App\Services\AndroidReleaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherMobileAppController extends Controller
{
    public function __invoke(Request $request, AndroidReleaseService $androidReleases): View
    {
        $this->authorizeTeacher($request);

        return view('teacher.mobile-app', ['release' => $androidReleases->current()]);
    }

    public function download(Request $request, int $versionCode, AndroidReleaseService $androidReleases): StreamedResponse
    {
        $this->authorizeTeacher($request);
        $release = $androidReleases->current();

        abort_unless($release !== null && $release['version_code'] === $versionCode, 404);

        return $androidReleases->disk()->download(
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
        Gate::authorize('recitations.create');

        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->teacherProfile()->where('active', true)->exists(),
            403,
            'لا يوجد ملف محفظ فعال لهذا الحساب.',
        );
    }
}
